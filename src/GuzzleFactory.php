<?php
namespace Germania\DownloadsApiClient;

use GuzzleHttp\Client;

/**
 * This callable factory creates a Guzzle Client
 * for usage with Germania KG's Downloads API.
 */
class GuzzleFactory
{

	/**
	 * @param  string $api   The DownloadsApi endpoint
	 * @param  string $token The AuthAPI Access token string
	 * @return Client        Guzzle Client
	 */
	public function __invoke( string $api, string $token )
	{
		$auth_header = sprintf("Bearer %s", $token);

		$headers = array(
			'Authorization' => $auth_header
		);

		return new Client([
		    'base_uri' => $api,
		    'headers'  => $headers
		]);

	}
}