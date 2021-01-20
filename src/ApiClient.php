<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ResponseDecoderTrait;


/**
 * The Downloads API Client
 */
class ApiClient extends ApiClientAbstract
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
	 * @param LoggerInterface|null   $logger            Optional PSR-3 Logger.
	 */
	public function __construct(ClientInterface $client, RequestInterface $request, LoggerInterface $logger = null )
	{
		$this->setClient( $client );
		$this->setRequest( $request );
        $this->setLogger( $logger ?: new NullLogger);
        $this->setResponseDecoder( new JsonApiResponseDecoder );
	}






	/**
	 * @param  string $path    Request URL path
	 * @param  array  $filters Filters array
	 *
	 * @return iterable
	 */
	public function __invoke( string $path, array $filters = array() ) : iterable
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



            // Response validation
            switch($response->getStatusCode()):
                case "200":
                    // noop
                    break;

                default:
                    $error = $this->getErrorResponseInformation( $response );
                    $msg = sprintf("Response ended up with status '%s'. %s: %s", $error['status'], $error['title'], $error['detail']);
                    throw new ApiClientRuntimeException($msg, $error['status']);
                    break;
            endswitch;

            // Response decoding
            $downloads = $this->getResponseDecoder()->getResourceCollection($response);

            $this->logger->log( $this->success_loglevel, "Retrieved documents list", [
                'path' => $path,
                'count' => count($downloads),
                'time' => ((microtime("float") - $start_time) * 1000) . "ms"
            ]);

            return $downloads;

		}

		catch (\Throwable $e) {
            $this->handleException( $e );
            throw $e;
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
     * Sends exception or error to logger
     * @param  \Throwable $e
     * @return void
     */
    protected function handleException( \Throwable $e) : void
    {
        $location = sprintf("%s:%s", $e->getFile(), $e->getLine());
        $location = str_replace(getcwd() . "/", "", $location);

        $msg = sprintf("DocumentsApi caught exception: %s", $e->getMessage());
        $this->logger->log( $this->error_loglevel, $msg, [
            'exception' => get_class($e),
            'code' => $e->getCode(),
            'location' => $location
        ]);
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
	public function setClient( ClientInterface $client )
	{
		$this->client = $client;
		return $this;
	}


    /**
     * Sets the PSR-7 Request to work with
     *
     * @param Psr\Http\Message\RequestInterface $request
     */
    public function setRequest( RequestInterface $request )
    {
        $this->request = $request;
        return $this;
    }



}

