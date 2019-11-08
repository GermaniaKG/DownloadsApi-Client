<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Cache\CacheItemPoolInterface;
use GuzzleHttp\Psr7\Request;

class DownloadsApiHttpClient extends ApiClientAbstract
{


	use LoggerAwareTrait;

	/**
	 * @var ClientInterface
	 */
	protected $client;

	/**
	 * @var CacheItemPoolInterface
	 */
	protected $cache_itempool;

	/**
	 * @var string
	 */
	protected $loglevel = "error";


	/**
	 * @var string
	 */
	protected $cache_user_id;






	/**
	 * @param ClientInterface        $client            HTTP Client
	 * @param CacheItemPoolInterface $cache_itempool    PSR-6 Cache ItemPool
	 * @param LoggerInterface|null   $logger            Optional PSR-3 Logger.
	 * @param string                 $loglevel          Optional PSR-3 Loglevel, defaults to `error `
	 */
	public function __construct(ClientInterface $client, CacheItemPoolInterface $cache_itempool, string $cache_user_id, LoggerInterface $logger = null, string $loglevel = "error" )
	{
		$this->setClient( $client );
		$this->cache_itempool = $cache_itempool;
		$this->cache_user_id = $cache_user_id;
		$this->loglevel = $loglevel;
		$this->setLogger( $logger ?: new NullLogger);
	}






	/**
	 * @param  string $path    Request URL path
	 * @param  array  $filters Filters array
	 * 
	 * @return array
	 */
	public function __invoke( string $path, array $filters = array() )
	{
		$start_time = microtime("float");

		// ---------------------------------------------------
		// Ask Cache first
		// ---------------------------------------------------

		$cache_key  = $this->getCacheKey($path, $filters);
		$cache_item = $this->cache_itempool->getItem( $cache_key );		

		if ($cache_item->isHit()):
			$downloads = $cache_item->get();

			$this->logger->info( "Documents list found in cache", [
				'path' => $path,
				'count' => count($downloads),
				'time' => ((microtime("float") - $start_time) * 1000) . "ms"
			]);

			return new \ArrayIterator( $downloads );	
		endif;


		// ---------------------------------------------------
		// Ask remote API
		// ---------------------------------------------------

		try {
			

			$request = new Request("GET", "");
			$query = http_build_query(['filter' => $filters]);

			$uri = $request->getUri()->withPath($path)->withQuery($query);

			$request = $request->withUri( $uri );

			// ResponseInterface!
			$response = $this->client->sendRequest( $request);
		}

		catch (ClientExceptionInterface $e) {
			$msg = sprintf("DocumentsApi: %s", $e->getMessage());
			$this->logger->log( $this->loglevel, $msg, [
				'exception' => get_class($e)
			]);
			// Shortcut: empty result
			return new \ArrayIterator( array() );	
		}		


		// ---------------------------------------------------
		// Response validation
		// ---------------------------------------------------

		try {
			$response_body_decoded = (new JsonDecoder)($response, "associative");
			$this->validateDecodedResponse( $response_body_decoded );	
		}
		catch (\JsonException $e) {
			throw new DownloadsApiClientUnexpectedValueException("DocumentsApi: Problems with API response", 0, $e);
		}
		catch (DownloadsApiClientExceptionInterface $e) {
			throw $e;
		}


		// ---------------------------------------------------
		// Build result and store in cache
		// ---------------------------------------------------

		$downloads = array_column($response_body_decoded['data'], "attributes");

		$cache_item->set( $downloads );	
		$lifetime = $this->getCacheLifetime( $response );
    	$cache_item->expiresAfter( $lifetime );
    	$this->cache_itempool->save($cache_item);

		$this->logger->notice( "Documents list stored in cache", [
			'path' => $path,
			'count' => count($downloads),
			'time' => ((microtime("float") - $start_time) * 1000) . "ms"
		]);

		return new \ArrayIterator( $downloads );		
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

	/**
	 * Returns a cache key for the current call.
	 * 
	 * @param  string $path
	 * @param  array $filters
	 * @return string
	 */
	protected function getCacheKey(string $path, array $filters) : string
	{
		$hash = hash("sha256", $this->cache_user_id );
		return implode("/", [
			$hash,
			$path,
			md5(serialize($filters))
		]);
	}

}

