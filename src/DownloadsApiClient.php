<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Cache\CacheItemPoolInterface;

use Stash\Interfaces\ItemInterface as StashItemInterface;
use Stash\Invalidation as StashInvalidation;

/**
 * The Guzzle Version of the Downloads API Client
 */
class DownloadsApiClient extends ApiClientAbstract
{

	use LoggerAwareTrait;

	/**
	 * @var \GuzzleHttp\Client
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
	protected $loglevel_success = "info";


	/**
	 * @param Guzzle                 $client            Readily configured Guzzle Client
	 * @param CacheItemPoolInterface $cache_itempool    PSR-6 Cache ItemPool
	 * @param LoggerInterface|null   $logger            Optional PSR-3 Logger.
	 * @param string                 $loglevel          Optional PSR-3 Loglevel, defaults to `error `
	 */
	public function __construct(Guzzle $client, CacheItemPoolInterface $cache_itempool, LoggerInterface $logger = null, string $loglevel = "error", string $loglevel_success = "info" )
	{
		$this->setGuzzleClient( $client );
		$this->cache_itempool = $cache_itempool;
		$this->loglevel = $loglevel;
		$this->loglevel_success = $loglevel_success;
		$this->setLogger( $logger ?: new NullLogger);
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
		// Ask Cache first
		// ---------------------------------------------------

		$cache_key  = $this->getCacheKey($path, $filters);
		$cache_item = $this->cache_itempool->getItem( $cache_key );

		if ($cache_item instanceOf StashItemInterface):
			$cache_item->setInvalidationMethod(StashInvalidation::PRECOMPUTE, $this->stash_precompute_time);
		endif;


		if ($cache_item->isHit()):
			$downloads = $cache_item->get();

			$this->logger->info( "Documents list found in cache", [
				'path' => $path,
				'count' => count($downloads),
				'time' => ((microtime("float") - $start_time) * 1000) . "ms"
			]);

			return new \ArrayIterator( $downloads );
		endif;


		//
		// When reaching this point, the stored documents are stale.
		//


		// From Stash Docs:
	    // Mark this instance as the one regenerating the cache. Because our
	    // protection method is Invalidation::OLD other Stash instances will
	    // use the old value and count it as a hit.
		if ($cache_item instanceOf StashItemInterface):
			$cache_item->lock();
		endif;



		// ---------------------------------------------------
		// Ask remote API
		// ---------------------------------------------------

		try {
			// ResponseInterface!
			$response = $this->client->get( $path, [
				'query' => ['filter' => $filters]
			]);
		}
		catch (RequestException $e) {
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

		$this->logger->log($this->loglevel_success, "Documents list stored in cache", [
			'path' => $path,
			'count' => count($downloads),
			'lifetime' => $lifetime,
			'runtime' => ((microtime("float") - $start_time) * 1000) . "ms"
		]);

		return new \ArrayIterator( $downloads );
	}


	/**
	 * Sets the Guzzle client to use.
	 *
	 * The client is examined if it is configured to send an Authorization header;
	 * if not, a DownloadsApiClientRuntimeException will be thrown.
	 *
	 * @inhertDoc
	 *
	 * @throws DownloadsApiClientRuntimeException
	 */
	protected function setGuzzleClient( Guzzle $client ) : DownloadsApiClient
	{
		$headers = $client->getConfig('headers') ?? array();
		if (!$auth = $headers['Authorization'] ?? false):
			throw new DownloadsApiClientRuntimeException("DocumentsApi: Guzzle HTTP Client lacks Authorization header.");
		endif;

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
		$client_headers = $this->client->getConfig('headers');

		$hash = hash("sha256", $client_headers['Authorization'] );
		return implode("/", [
			$hash,
			$path,
			md5(serialize($filters))
		]);
	}


}
