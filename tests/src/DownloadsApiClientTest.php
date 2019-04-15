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
		$token = $GLOBALS['AUTH_TOKEN'];
		$auth_header = sprintf("Bearer %s", $token);

		$this->downloads_client = new Client([
		    // Base URI is used with relative requests
		    'base_uri' => $GLOBALS['DOWNLOADS_API'],
		    'headers'  => array('Authorization' => $auth_header)
		]);

		$this->cachepool = new \Stash\Pool( new \Stash\Driver\FileSystem([
			'path' => dirname(dirname(__FILE__)) . "/cache/"
		]));
	}


	public function testSimple()
	{
		$sut = new DownloadsApiClient( $this->downloads_client );
		$this->assertTrue( is_callable( $sut ));

		$all = $sut->all([ 
			"product" => "plissee",
			"category" => "montageanleitung" 
		]);
		$this->assertInstanceOf( \Traversable::class, $all);

		$latest = $sut->latest([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $latest);


	}

} 