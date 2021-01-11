<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Cache\CacheItemPoolInterface;

use Stash\Interfaces\ItemInterface as StashItemInterface;
use Stash\Invalidation as StashInvalidation;
use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ResponseDecoderTrait;


/**
 * The Downloads API Client
 */
class ApiClient extends ApiClientAbstract
{


	use LoggerAwareTrait, ResponseDecoderTrait;

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
	protected $error_loglevel = "error";

	/**
	 * @var string
	 */
	protected $success_loglevel = "info";

	/**
	 * @var RequestInterface
	 */
	protected $request;

    /**
     * @var callable
     */
    protected $response_decoder;


    /**
     * @var string
     */
    protected $cache_key_separator = "|";

    /**
     * Custom time window for Stash's "Precompute" invalidation method.
     * Defaults to default cache lifetime divided by 4.
     *
     * @see https://www.stashphp.com/Invalidation.html#precompute
     *
     * @var integer|null
     */
    protected $stash_precompute_time;





	/**
	 * @param ClientInterface        $client            HTTP Client
     * @param RequestInterface       $request           PSR-7 request template
	 * @param CacheItemPoolInterface $cache_itempool    PSR-6 Cache ItemPool
	 * @param LoggerInterface|null   $logger            Optional PSR-3 Logger.
	 * @param string                 $error_loglevel    Optional: Loglevel name for errors, defaults to `error`
     * @param string                 $success_loglevel  Optional: Success loglevel name, defaults to `info`
	 */
	public function __construct(ClientInterface $client, RequestInterface $request, CacheItemPoolInterface $cache_itempool,LoggerInterface $logger = null, string $error_loglevel = "error", string $success_loglevel = "info"  )
	{
		$this->setClient( $client );
		$this->cache_itempool = $cache_itempool;
		$this->request = $request;
        $this->setLogger( $logger ?: new NullLogger);

		$this->error_loglevel = $error_loglevel;
		$this->success_loglevel = $success_loglevel;

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
		// Ask Cache first
		// ---------------------------------------------------

		$cache_key  = $this->getCacheKey($path, $filters);
		$cache_item = $this->cache_itempool->getItem( $cache_key );

		if ($cache_item instanceOf StashItemInterface):
            $stash_precompute_time = $this->getStashPrecomputeTime();
			$cache_item->setInvalidationMethod(StashInvalidation::PRECOMPUTE, $stash_precompute_time);
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


		// ---------------------------------------------------
		// Store in cache
		// ---------------------------------------------------

		$lifetime = $this->readCachControlMaxAge( $response );

        $cache_item->set( $downloads );
    	$cache_item->expiresAfter( $lifetime );

        $this->cache_itempool->save($cache_item);

		$this->logger->log( $this->success_loglevel, "Documents list stored in cache", [
			'path' => $path,
			'count' => count($downloads),
			'lifetime' => $lifetime,
			'time' => ((microtime("float") - $start_time) * 1000) . "ms"
		]);

		return new \ArrayIterator( $downloads );
	}




    /**
     * Grabs the TTL from the "Cache-Control" header.
     *
     * @param  \Psr\Http\Message\ResponseInterface $response [description]
     * @return int
     */
    protected function readCachControlMaxAge( $response ) : int
    {
        $cache_control = $response->getHeaderLine('Cache-Control');

        preg_match("/(max\-age=(\d+))/i", $cache_control, $matches);

        if (!empty($matches[2])):
            $max_age = $matches[2];

            $this->logger->debug( "Grabbed TTL from 'Cache-Control' header in DocumentAPI's response", [
                'maxAge' => $max_age,
                'cacheControlHeader' => $cache_control,
            ]);
        else:
            $max_age = $this->getDefaultCacheLifetime();

            $this->logger->debug( "Can not grab TTL from 'Cache-Control' header in DocumentAPI's response, use default cache lifetime", [
                'defaultCacheLifetime' => $max_age,
                'cacheControlHeader' => $cache_control,
            ]);
        endif;

        $this->logger->info( "Determined cache TTL for documents list", [
            'TTL' => $max_age,
        ]);

        return (int) $max_age;
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
        $auth_hash = hash("sha256", $this->request->getHeaderLine('Authorization'));
        $filters_hash = md5(serialize($filters));

		return implode($this->cache_key_separator, [
			$auth_hash,
			$path,
			$filters_hash
		]);
	}




    /**
     * @param int $seconds
     */
    public function setStashPrecomputeTime( int $seconds ) : ApiClientAbstract
    {
        $this->stash_precompute_time = $seconds;
        return $this;
    }



    /**
     * @return int $seconds
     */
    public function getStashPrecomputeTime() : int
    {
        return is_null($this->stash_precompute_time)
        ? ($this->getDefaultCacheLifetime() / 4)
        : $this->stash_precompute_time;
    }


}

