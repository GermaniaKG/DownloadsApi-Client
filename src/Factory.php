<?php
namespace Germania\DownloadsApiClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;


/**
 * Factory to creates a Guzzle PSR-18 HTTP Client
 * and PSR-7 Request templates
 * for usage with Germania KG's Downloads API.
 */
class Factory implements FactoryInterface
{


	/**
	 * @return ClientInterface
	 */
	public function createClient() : ClientInterface
	{
		$guzzle = new GuzzleClient();

        if ($guzzle instanceOf ClientInterface) {
            return $guzzle;
        }
        return new GuzzleAdapter($guzzle);
	}


    /**
     * @param  string $api   The DownloadsApi endpoint
     * @param  string $token The AuthAPI Access token string
     *
     * @return RequestInterface
     */
    public function createRequest( string $api, string $token ) : RequestInterface
    {
        $auth_header = sprintf("Bearer %s", $token);

        $headers = array(
            'Authorization' => $auth_header
        );

        return new GuzzleRequest('GET', $api, $headers);
    }

}
