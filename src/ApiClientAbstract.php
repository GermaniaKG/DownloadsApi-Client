<?php
namespace Germania\DownloadsApiClient;

use Psr\Log\LoggerAwareTrait;

abstract class ApiClientAbstract implements ApiClientInterface
{


	use LoggerAwareTrait;


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
     * @inheritDoc
     *
	 * @param  array  $filters
	 */
	public function all( array $filters = array() ) : iterable
	{
		return $this->__invoke("all", $filters );
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
	 * @param int $seconds
	 */
	public function setDefaultCacheLifetime( int $seconds ) : ApiClientAbstract
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


}
