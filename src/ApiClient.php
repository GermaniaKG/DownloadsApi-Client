<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
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
		$this->request = $request;
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
		}

		catch (\Throwable $e) {
			$msg = sprintf("DocumentsApi: %s", $e->getMessage());
			$this->logger->log( $this->error_loglevel, $msg, [
				'exception' => get_class($e),
                'location' => sprintf("%s:%s", $e->getFile(), $e->getLine())
			]);
			// Shortcut: empty result
			return new \ArrayIterator( array() );
		}


		// ---------------------------------------------------
		// Response validation and decoding
		// ---------------------------------------------------

		try {
            $downloads = $this->getResponseDecoder()->getResourceCollection($response);
		}
		catch (\Throwable $e) {
            $msg = sprintf("DocumentsApi: %s", $e->getMessage());
            $this->logger->log( $this->error_loglevel, $msg, [
                'exception' => get_class($e),
                'location' => sprintf("%s:%s", $e->getFile(), $e->getLine())
            ]);
			throw $e;
		}


		$this->logger->log( $this->success_loglevel, "Documents list stored in cache", [
			'path' => $path,
			'count' => count($downloads),
			'time' => ((microtime("float") - $start_time) * 1000) . "ms"
		]);

		return new \ArrayIterator( $downloads );
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
	protected function setClient( ClientInterface $client )
	{
		$this->client = $client;
		return $this;
	}



}

