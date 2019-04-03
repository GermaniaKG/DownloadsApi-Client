<?php
namespace tests;

use Germania\DownloadsApiClient\DownloadsApiClient;
use GuzzleHttp\Client;

class DownloadsApiClientTest extends \PHPUnit\Framework\TestCase
{

	public $cachepool;
	public $downloads_client;

	public function setUp()
	{
		$this->downloads_client = new Client([
		    // Base URI is used with relative requests
		    'base_uri' => $GLOBALS['DOWNLOADS_API']
		]);

		$this->cachepool = new \Stash\Pool( new \Stash\Driver\FileSystem([
			'path' => dirname(dirname(__FILE__)) . "/cache/"
		]));

	}

	public function testSimple()
	{
		$token = $GLOBALS['AUTH_TOKEN'];

		$sut = new DownloadsApiClient( $token, $this->downloads_client );
		$this->assertTrue( is_callable( $sut ));

		$all = $sut->all([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $all);

		$latest = $sut->latest([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $latest);


	}

} 