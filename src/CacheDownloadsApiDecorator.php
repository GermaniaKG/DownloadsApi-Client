<?php
namespace Germania\DownloadsApiClient;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CacheDownloadsApiDecorator extends ApiClientDecorator
{


    /**
     * Hash algorith to create a key with.
     * Defaults to "sha256" as it creates a hash of 64 chars length.
     *
     * @var string
     */
    protected $cache_key_hash_algo = "sha256";


    /**
     * The default cache TTL to use when the remote
     * API does not deliver a cache TTL itself.
     *
     * Defaults to 4 hours
     *
     * @var integer
     */
    protected $default_cache_lifetime = 14400;



    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected $cache_itempool;


    /**
     * @param \Germania\DownloadsApiClient\ApiClientInterface  $client  AuthApi client decoratee
     * @param \Psr\Cache\CacheItemPoolInterface                $cache   PSR-6 Cache
     * @param \Psr\Log\LoggerInterface|null                   $logger  Optional: PSR-3 Logger
     */
    public function __construct( ApiClientInterface $client, CacheItemPoolInterface $cache, LoggerInterface $logger = null )
    {
        parent::__construct( $client );
        $this->setCacheItemPool( $cache );
        $this->setLogger($logger ?: new NullLogger);
    }





    /**
     * @param  string $path    Request URL path
     * @param  array  $filters Filters array
     *
     * @return iterable
     */
    public function __invoke( string $path, array $filters = array() ) : iterable
    {
        $cache_key  = $this->makeCacheKey($path, $filters);
        $cache_item = $this->cache_itempool->getItem( $cache_key );

        if ($cache_item->isHit()):
            $downloads = $cache_item->get();

            $this->logger->log( $this->success_loglevel, "Documents list found in cache", [
                'path' => $path,
                'count' => count($downloads)
            ]);

            return $downloads;
        endif;

        $this->logger->debug("Documents not found or stale, delete cache item.");
        $this->cache_itempool->deleteItem($cache_key);

        $downloads = $this->client->all($filters );

        $cache_item->set( $downloads );

        $lifetime = $this->getDefaultCacheLifetime();
        $cache_item->expiresAfter( $lifetime );

        $this->cache_itempool->save($cache_item);

        $this->logger->log( $this->success_loglevel, "Documents list stored in cache", [
            'path' => $path,
            'count' => count($downloads),
            'lifetime' => $lifetime
        ]);

        return $downloads;
    }

    /**
     * @inheritDoc
     *
     * @param  array  $filters
     */
    public function all( array $filters = array() ) : iterable
    {
        return $this->__invoke("all", $filters);
    }



    /**
     * @inheritDoc
     *
     * @param  array $filters
     */
    public function latest( array $filters = array() ) : iterable
    {
        return $this->__invoke("latest", $filters );
    }




    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache PSR-6 CacheItem Pool
     */
    public function setCacheItemPool( CacheItemPoolInterface $cache  ) : self
    {
        $this->cache_itempool = $cache;
        return $this;
    }


    /**
     * @param int $seconds
     */
    public function setDefaultCacheLifetime( int $seconds ) : self
    {
        $this->default_cache_lifetime = $seconds;
        return $this;
    }



    /**
     * @return int $seconds
     */
    public function getDefaultCacheLifetime() : int
    {
        return $this->default_cache_lifetime;
    }




    /**
     * Create a PSR-6 compliant cache key (hex characters).
     *
     * @param  string $path
     * @param  array $filters
     * @return string
     */
    public function makeCacheKey(string $path, array $filters) : string
    {
        $filters_hash = md5(serialize($filters));
        $auth = $this->getAuthentication();
        return hash($this->cache_key_hash_algo, $path . $auth . $filters_hash, false );
    }


}
