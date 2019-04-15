<?php
namespace tests;

use Germania\DownloadsApiClient\DownloadsApiClient;
use Germania\DownloadsApiClient\DownloadsApiClientUnexpectedValueException;
use GuzzleHttp\Client;
use Prophecy\Argument;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

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

		parent::setUp();
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


	public function testEmptyIteratorResultOnRequestException()
	{
		$exception = $this->prophesize( ClientException::class );

		$client = $this->prophesize( Client::class );
		$client->get( Argument::type("string"), Argument::type("array"))->willThrow( $exception->reveal() );
		$client_stub = $client->reveal();

		$sut = new DownloadsApiClient( $client_stub );

		$all = $sut->all([ 
			"product" => "plissee",
			"category" => "montageanleitung" 
		]);
		$this->assertInstanceOf( \Traversable::class, $all);
		$this->assertEquals( 0, count($all));

		$latest = $sut->latest([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $latest);
		$this->assertEquals( 0, count($latest));
	}


	/**
	 * @dataProvider provideVariousInvalidResonseBodies
	 */
	public function testExceptionOnWeirdResponseBody( $body )
	{
		$response = new Response( 200, array(), $body );

		$client = $this->prophesize( Client::class );
		$client->get( Argument::type("string"), Argument::type("array"))->willReturn( $response );
		$client_stub = $client->reveal();

		$sut = new DownloadsApiClient( $client_stub );

		$this->expectException( DownloadsApiClientUnexpectedValueException::class );
		$all = $sut->all();

		$this->expectException( DownloadsApiClientUnexpectedValueException::class );
		$latest = $sut->latest([  "product" => "plissee" ]);
	}

	public function provideVariousInvalidResonseBodies()
	{
		return array(
			[ "hello!" ],
			[ json_encode( array("foo" => "bar")) ],
			[ json_encode( array("data" => "bar")) ],
			[ json_encode( array("data" => 1)) ],
			[ json_encode( array("data" => false)) ],
			[ json_encode( array("data" => true)) ],
		);
	}



} 