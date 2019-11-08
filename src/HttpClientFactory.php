<?php
namespace Germania\DownloadsApiClient;

use Psr\Http\Client\ClientInterface;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;


/**
 * This callable factory creates a HTTP Client
 * for usage with Germania KG's Downloads API.
 */
class HttpClientFactory
{


	/**
	 * @param  string $api   The DownloadsApi endpoint
	 * @param  string $token The AuthAPI Access token string
	 * 
	 * @return ClientInterface
	 */
	public function __invoke( string $api, string $token ) : ClientInterface
	{
		$auth_header = sprintf("Bearer %s", $token);

		$headers = array(
			'Authorization' => $auth_header
		);

		return GuzzleAdapter::createWithConfig([
		    'base_uri' => $api,
		    'headers'  => $headers
		]);
	}
}