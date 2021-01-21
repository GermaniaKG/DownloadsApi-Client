<?php
namespace Germania\DownloadsApi;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class CacheDownloadsApiDecorator extends DownloadsApiDecorator
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
    protected $cache_lifetime = 14400;



    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected $cache_itempool;


    /**
     * @param \Germania\DownloadsApi\DownloadsApiInterface  $client    AuthApi client decoratee
     * @param \Psr\Cache\CacheItemPoolInterface             $cache     PSR-6 Cache
     * @param int                                           $lifetime  Optional: Cache lifetime, default is `14400`
     */
    public function __construct( DownloadsApiInterface $client, CacheItemPoolInterface $cache, int $lifetime = 14400)
    {
        parent::__construct( $client );
        $this->setCacheItemPool( $cache );
        $this->setCacheLifetime( $lifetime );
    }





    /**
     * @param  string $path    Request URL path
     * @param  array  $filters Filters array
     *
     * @return iterable
     */
    public function request( string $path, array $filters = array() ) : iterable
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

        // Delegate to decoratee
        $downloads = $this->client->request($path, $filters );

        $cache_item->set( $downloads );

        $lifetime = $this->getCacheLifetime();
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
        return $this->request("all", $filters);
    }



    /**
     * @inheritDoc
     *
     * @param  array $filters
     */
    public function latest( array $filters = array() ) : iterable
    {
        return $this->request("latest", $filters );
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
    public function setCacheLifetime( int $seconds ) : self
    {
        $this->cache_lifetime = $seconds;
        return $this;
    }



    /**
     * @return int $seconds
     */
    public function getCacheLifetime() : int
    {
        return $this->cache_lifetime;
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
