<?php
namespace Germania\DownloadsApiClient;

abstract class ApiClientAbstract
{



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
	 * Time window for Stash's "Precompute" invalidation method.
	 * 
	 * @see https://www.stashphp.com/Invalidation.html#precompute
	 * 
	 * @var integer
	 */
	protected $stash_precompute_time = 3600;



	/**
	 * @param  array  $filters
	 * @return \ArrayIterator
	 */
	public function all( array $filters = array() )
	{
		return $this->__invoke("all", $filters );
	}


	/**
	 * @param  array  $filters
	 * @return \ArrayIterator
	 */
	public function latest( array $filters = array() )
	{
		return $this->__invoke("latest", $filters );
	}




	/**
	 * Grabs the TTL from the "Cache-Control" header.
	 * 
	 * @param  \Psr\Http\Message\ResponseInterface $response [description]
	 * @return int
	 */
	protected function getCacheLifetime( $response ) : int
	{
		$cache_control = $response->getHeaderLine('Cache-Control');

		preg_match("/(max\-age=(\d+))/i", $cache_control, $matches);

		$max_age = $matches[2] ?? $this->getDefaultCacheLifetime();
		return (int) $max_age;
	}




	/**
	 * @param int $seconds
	 */
	public function setStashPrecomputeTime( int $seconds )
	{
		$this->stash_precompute_time = $seconds;
		return $this;
	}



	/**
	 * @return int $seconds
	 */
	public function getStashPrecomputeTime()
	{
		return $this->stash_precompute_time;
	}





	/**
	 * @param int $seconds
	 */
	public function setDefaultCacheLifetime( int $seconds )
	{
		$this->default_cache_lifetime = $seconds;
		return $this;
	}



	/**
	 * @return int $seconds
	 */
	public function getDefaultCacheLifetime()
	{
		return $this->default_cache_lifetime;
	}




	/**
	 * Validates the decoded response, throwing things in error case.
	 * 
	 * @param  array  $response_body_decoded
	 * @return void
	 *
	 * @throws DownloadsApiClientUnexpectedValueException
	 */
	protected function validateDecodedResponse( array $response_body_decoded )
	{
		// "data" is quite common in JsonAPI responses, 
		// however, we need it as array.

		if (!isset( $response_body_decoded['data'] )):
			throw new DownloadsApiClientUnexpectedValueException("DocumentsApi response: Missing 'data' element");
		endif;


		if (!is_array( $response_body_decoded['data'] )):
			throw new DownloadsApiClientUnexpectedValueException("DocumentsApi response: Element 'data' is not array");
		endif;

	}	
}