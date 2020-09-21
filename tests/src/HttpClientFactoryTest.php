<?php
namespace tests;

use Germania\DownloadsApiClient\HttpClientFactory;
use Psr\Http\Client\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Prophecy\PhpUnit\ProphecyTrait;

class HttpClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

	public $api = "http://httpbin.org/bearer";
	public $token = "FooBar";

	public function testFactoryOnClientInterface()
	{
		$sut = new HttpClientFactory;
		$client = $sut($this->api, $this->token);

		$this->assertInstanceOf( ClientInterface::class, $client);
		return $client;
	}


	/**
	 * @depends testFactoryOnClientInterface
	 */
	public function testWorkingAuthFactory( $client)
	{
		$request = new Request("GET", "");
		$response = $client->sendRequest($request);
		$this->assertEquals(200, $response->getStatusCode());

		$response_decoded = json_decode($response->getBody()->__toString());

		$this->assertObjectHasAttribute("authenticated", $response_decoded);
		$this->assertTrue($response_decoded->authenticated);

		$this->assertObjectHasAttribute("token", $response_decoded);
		$this->assertEquals($this->token, $response_decoded->token);
	}
}
