<?php
namespace Germania\DownloadsApiClient;

use Germania\JsonDecoder\JsonDecoder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

class DownloadsApiClient
{

	use LoggerAwareTrait;

	/**
	 * @var \GuzzleHttp\Client
	 */
	protected $client;

	/**
	 * @var string
	 */
	protected $loglevel = "error";



	/**
	 * @param Client               $client   Readily configured Guzzle Client
	 * @param LoggerInterface|null $logger   Optional PSR-3 Logger.
	 * @param string               $loglevel Optional PSR-3 Loglevel, defaults to `error `
	 */
	public function __construct(Client $client, LoggerInterface $logger = null, string $loglevel = "error" )
	{
		$this->setClient( $client );
		$this->loglevel = $loglevel;
		$this->setLogger( $logger ?: new NullLogger);
	}


	protected function setClient( Client $client )
	{
		$headers = $client->getConfig('headers') ?? array();
		if (!$auth = $headers['Authorization'] ?? false):
			throw new DownloadsApiClientRuntimeException("Client lacks Authorization header.");
		endif;
		$this->client = $client;
	}



	/**
	 * @param  string $path    Request URL path
	 * @param  array  $filters Filters array
	 * @return \ArrayIterator
	 */
	public function __invoke( string $path, array $filters = array() )
	{
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
			return new \ArrayIterator( array() );	
		}		


		// ---------------------------------------------------
		// Convert Response to array
		// ---------------------------------------------------

		try {
			$response_body_decoded = (new JsonDecoder)($response, "associative");
		}
		catch (\JsonException $e) {
			throw new DownloadsApiClientUnexpectedValueException("Problems with API response", 0, $e);
		}

		// ---------------------------------------------------
		// "data" is quite common in JsonAPI responses, 
		// however, we need it as array.
		// ---------------------------------------------------

		if (!isset( $response_body_decoded['data'] )):
			throw new DownloadsApiClientUnexpectedValueException("Missing 'data' element in API response");
		endif;

		$downloads = $response_body_decoded['data'];
	
		if (!is_array( $downloads )):
			throw new DownloadsApiClientUnexpectedValueException("API response's 'data' element is not array");
		endif;


		// ---------------------------------------------------
		// "attributes" is what we are interested in here, 
		// the "type" and "id" stuff being not interesting.
		// ---------------------------------------------------
		$downloads_attr_only = array_column($downloads, "attributes");
		$this->logger->debug( sprintf("Calling '%s' yields '%s' results", $path, count($downloads_attr_only)));
		return new \ArrayIterator( $downloads_attr_only );		
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

}