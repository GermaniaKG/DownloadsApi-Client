<?php
namespace Germania\DownloadsApi;

use Germania\DownloadsApi\Exceptions\{
    DownloadsApiUnexpectedValueException,
    DownloadsApiRuntimeException,
    DownloadsApiResponseException,
};
use Germania\JsonDecoder\JsonDecoder;

use Psr\Http\{
    Client\ClientInterface,
    Client\ClientExceptionInterface,
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
	 * @var ClientInterface
	 */
	protected $client;

	/**
	 * @var RequestInterface
	 */
	protected $request;


	/**
	 * @param ClientInterface        $client            PSR-18 HTTP Client
     * @param RequestInterface       $request           PSR-7 request template
	 */
	public function __construct(ClientInterface $client, RequestInterface $request )
	{
		$this->setClient( $client );
		$this->setRequest( $request );
        $this->setResponseDecoder( new JsonApiResponseDecoder );
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
    		$query = http_build_query(['filter' => $filters]);

            $original_uri = $this->request->getUri();
            $new_path = $original_uri->getPath() . $path;

            $new_uri = $original_uri
                ->withPath( $new_path )
                ->withQuery($query);

            $new_request = $this->request->withUri( $new_uri );
			$response = $this->client->sendRequest( $new_request);


            // Response validation:
            //
            // PSR-18 client reponses ususally do not throw exceptions
            // when getting an HTTP error response.
            // We have self to check for error reasons.
            $response_status = $response->getStatusCode();

            if ($response_status == 200):
                // noop

            else:
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


    protected function getErrorResponseInformation( ResponseInterface $response ) : array
    {
        $response_body = $response->getBody()->__toString();

        $decoded = json_decode($response_body, (bool) "associative");
        $errors = $decoded['errors'] ?? array();
        $error = array_shift($errors) ?: array();

        return $error;
    }



    /**
     * @inheritDoc
     */
    public function getAuthentication() : string
    {
        return $this->request->getHeaderLine('Authorization');
    }



	/**
	 * Sets the HTTP Client to use.
	 *
	 * @param ClientInterface $client
	 */
	public function setClient( ClientInterface $client ) : self
	{
		$this->client = $client;
		return $this;
	}


    /**
     * Sets the PSR-7 Request to work with
     *
     * @param Psr\Http\Message\RequestInterface $request
     */
    public function setRequest( RequestInterface $request ) : self
    {
        $this->request = $request;
        return $this;
    }



}

