<?php
namespace Germania\DownloadsApiClient;

use Psr\Log\LoggerAwareTrait;

abstract class ApiClientAbstract implements DownloadsApiClientInterface
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

		if (!empty($matches[2])):
			$max_age = $matches[2];

			if ($this->logger):
				$this->logger->debug( "Grabbed TTL from 'Cache-Control' header in DocumentAPI's response", [
					'max_age' => $max_age,
					'Cache-Control' => $cache_control,
				]);
			endif;

		else:
			$max_age = $this->getDefaultCacheLifetime();

			if ($this->logger):
				$this->logger->debug( "Can not grab TTL from 'Cache-Control' header in DocumentAPI's response, use default cache lifetime", [
					'default_cache_lifetime' => $max_age,
					'Cache-Control' => $cache_control,
				]);
			endif;
		endif;

		if ($this->logger) {
			$this->logger->info( "Determined cache TTL for documents list", [
				'TTL' => $max_age,
			]);
		}

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
