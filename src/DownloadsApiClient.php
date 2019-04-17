<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Cache\CacheItemPoolInterface;

class DownloadsApiClient
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
	 * @var integer
	 */
	protected $default_cache_lifetime = 3600;



	/**
	 * @param Client                 $client            Readily configured Guzzle Client
	 * @param CacheItemPoolInterface $cache_itempool    PSR-6 Cache ItemPool
	 * @param LoggerInterface|null   $logger            Optional PSR-3 Logger.
	 * @param string                 $loglevel          Optional PSR-3 Loglevel, defaults to `error `
	 */
	public function __construct(Client $client, CacheItemPoolInterface $cache_itempool, LoggerInterface $logger = null, string $loglevel = "error" )
	{
		$this->setClient( $client );
		$this->cache_itempool = $cache_itempool;
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


		// ---------------------------------------------------
		// Ask Cache first
		// ---------------------------------------------------

		$cache_key  = $this->getCacheKey($path, $filters);
		$cache_item = $this->cache_itempool->getItem( $cache_key );		

		if ($cache_item->isHit()):
			$downloads = $cache_item->get();

			$msg = sprintf("Found '%s' results in cache for path '%s'", count($downloads), $path );
			$this->logger->debug( $msg );

			return new \ArrayIterator( $downloads );	
		endif;


		// ---------------------------------------------------
		// Ask remote API
		// ---------------------------------------------------

		try {
			$response = $this->client->get( $path, [
				'query' => ['filter' => $filters]
			]);
		}
		catch (RequestException $e) {
			$msg = $e->getMessage();
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
			throw new DownloadsApiClientUnexpectedValueException("Problems with API response", 0, $e);
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

		$msg = sprintf("Stored '%s' results in cache for path '%s'", count($downloads), $path );
		$this->logger->debug( $msg );

		return new \ArrayIterator( $downloads );		
	}


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
	 * Sets the Guzzle client to use.
	 *
	 * The client is examined if it is configured to send an Authorization header;
	 * if not, a DownloadsApiClientRuntimeException will be thrown.
	 * 
	 * @param Client $client [description]
	 *
	 * @throws DownloadsApiClientRuntimeException
	 */
	protected function setClient( Client $client )
	{
		$headers = $client->getConfig('headers') ?? array();
		if (!$auth = $headers['Authorization'] ?? false):
			throw new DownloadsApiClientRuntimeException("Client lacks Authorization header.");
		endif;
		$this->client = $client;
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

		$max_age = $matches[2] ?? $this->default_cache_lifetime;
		return (int) $max_age;
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

		return implode("/", [
			$client_headers['Authorization'],
			$path,
			sha1(serialize($filters))
		]);
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
		// ---------------------------------------------------
		// "data" is quite common in JsonAPI responses, 
		// however, we need it as array.
		// ---------------------------------------------------

		if (!isset( $response_body_decoded['data'] )):
			throw new DownloadsApiClientUnexpectedValueException("Missing 'data' element in API response");
		endif;


		if (!is_array( $response_body_decoded['data'] )):
			throw new DownloadsApiClientUnexpectedValueException("API response's 'data' element is not array");
		endif;

	}

}