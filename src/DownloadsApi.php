<?php
namespace Germania\DownloadsApi;

use Germania\DownloadsApi\Exceptions\{
    DownloadsApiUnexpectedValueException,
    DownloadsApiRuntimeException,
    DownloadsApiResponseException,
};


use Psr\Http\{
    Client\ClientInterface,
    Client\ClientExceptionInterface,
    Message\RequestFactoryInterface,
    Message\RequestInterface,
    Message\ResponseInterface
};

use Germania\ResponseDecoder\{
    JsonApiResponseDecoder,
    ResponseDecoderTrait,
    ReponseDecoderExceptionInterface
};


/**
 * The Downloads API Client
 */
class DownloadsApi extends DownloadsApiAbstract
{

	use ResponseDecoderTrait;

	/**
     * PSR-18 HTTP Client
	 * @var ClientInterface
	 */
	protected $client;


    /**
     * PSR-17 Request Factory
     * @var RequestFactoryInterface
     */
    protected $request_factory;



	/**
	 * @param ClientInterface          $client            PSR-18 HTTP Client
     * @param RequestFactoryInterface  $request_factory   PSR-17 Request Factory
     * @param string                   $auth_token        Auth token
     *
	 */
	public function __construct(ClientInterface $client, RequestFactoryInterface $request_factory, string $auth_token )
	{
        parent::__construct();

		$this->setClient( $client );
        $this->setRequestFactory( $request_factory );
        $this->setAuthentication( $auth_token );

        $this->setResponseDecoder( new JsonApiResponseDecoder );
	}





    /**
     * Sets the PSR-18 HTTP Client to use.
     *
     * @param ClientInterface $client
     */
    public function setClient( ClientInterface $client ) : self
    {
        $this->client = $client;
        return $this;
    }


    /**
     * Sets the PSR-17 Request factory to work with
     *
     * @param \Psr\Http\Message\RequestFactoryInterface $request_factory
     */
    public function setRequestFactory( RequestFactoryInterface $request_factory ) : self
    {
        $this->request_factory = $request_factory;
        return $this;
    }




	/**
	 * @param  string $path    Request URL path
	 * @param  array  $filters Filters array
	 *
	 * @return iterable
	 */
	public function request( string $path, array $filters = array() ) : iterable
	{
		$start_time = microtime("float");

		// ---------------------------------------------------
		// Ask remote API
		// ---------------------------------------------------

		try {

            $request = $this->createRequest( $path, $filters);

            // May throw \Psr\Http\Client\ClientExceptionInterface
			$response = $this->client->sendRequest( $request);


            // Response validation:

            // PSR-18 client reponses ususally do not throw exceptions
            // when getting an HTTP error response.
            // We have self to check for error reasons.
            if ($response->getStatusCode() != 200):
                $error = $this->getErrorResponseInformation( $response );
                $msg = sprintf("Response ended up with status '%s'. %s: %s", $error['status'], $error['title'], $error['detail']);
                throw new DownloadsApiResponseException($msg, $error['status']);
            endif;


            // Response decoding using class JsonApiResponseDecoder:
            // This may throw \Germania\ResponseDecoder\ReponseDecoderExceptionInterface
            $downloads = $this->getResponseDecoder()->getResourceCollection($response);


            $this->logger->log( $this->success_loglevel, "Retrieved documents list", [
                'path' => $path,
                'count' => count($downloads),
                'time' => ((microtime("float") - $start_time) * 1000) . "ms"
            ]);

            return $downloads;

		}

		catch (ReponseDecoderExceptionInterface $e) {
            $msg = sprintf("DocumentsApi caught exception: %s", $e->getMessage());
            throw new DownloadsApiUnexpectedValueException($msg,0, $e);
		}
        catch (\Throwable $e) {
            $msg = sprintf("DocumentsApi caught exception: %s", $e->getMessage());
            throw new DownloadsApiRuntimeException($msg,0, $e);
        }
	}



    /**
     * @param  string $path    Request URL path
     * @param  array  $filters Filters array
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function createRequest(string $path, array $filters = array()) : RequestInterface
    {
        $url = static::BASE_URL . $path;
        $auth_header = sprintf("Bearer %s", $this->getAuthentication());

        $request = $this->request_factory
                        ->createRequest("GET", $url)
                        ->withHeader('Authorization', $auth_header );

        $query = http_build_query(['filter' => $filters]);
        $filter_uri = $request->getUri()->withQuery($query);
        $request = $request->withUri( $filter_uri );

        return $request;
    }



    /**
     * @param  ResponseInterface $response
     * @return string[]
     */
    protected function getErrorResponseInformation( ResponseInterface $response ) : array
    {
        $response_body = $response->getBody()->__toString();

        $decoded = json_decode($response_body, (bool) "associative");
        $errors = $decoded['errors'] ?? array();
        $error = array_shift($errors) ?: array();

        return $error;
    }



}

