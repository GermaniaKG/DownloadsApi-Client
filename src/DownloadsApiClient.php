<?php
namespace Germania\DownloadsApiClient;

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
	protected $token;

	/**
	 * @var string
	 */
	protected $loglevel = "error";



	/**
	 * @param string               $token  Germania AuthApi Token
	 * @param Client               $client Guzzle Client
	 * @param LoggerInterface|null $logger 
	 */
	public function __construct( string $token, Client $client, LoggerInterface $logger = null )
	{
		$this->token = $token;
		$this->client = $client;
		$this->setLogger( $logger ?: new NullLogger);
	}


	/**
	 * @param  string $path    Request URL path
	 * @param  array  $filters Filters array
	 * @return \ArrayIterator
	 */
	public function __invoke( string $path, array $filters = array() )
	{
		$auth_header = sprintf("Bearer %s", $this->token);

		try {
			$response = $this->client->get( $path, [
				'query' => ['filter' => $filters],
				'headers' => array('Authorization' => $auth_header)
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

		$response_body = $response->getBody();
		$response_body_decoded = json_decode($response_body, "associative");
		if (is_null($response_body_decoded)):
			throw new DownloadsApiClientUnexpectedValueException("API response was NULL or could not be decoded properly");
		endif;


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