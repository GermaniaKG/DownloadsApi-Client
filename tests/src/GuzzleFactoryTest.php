<?php
namespace tests;

use Germania\DownloadsApiClient\GuzzleFactory;
use GuzzleHttp\Client;

class GuzzleFactoryTest extends \PHPUnit\Framework\TestCase
{

	public function testFactory()
	{
		$api = $GLOBALS['DOWNLOADS_API'];
		$token = $GLOBALS['AUTH_TOKEN'];

		$sut = new GuzzleFactory;
		$client = $sut($api, $token);

		$this->assertInstanceOf( Client::class, $client);
	}
}