<?php
namespace tests;

use Germania\DownloadsApiClient\DownloadsApiHttpClient;
use Germania\DownloadsApiClient\DownloadsApiClientInterface;
use Germania\DownloadsApiClient\DownloadsApiClientUnexpectedValueException;
use Germania\DownloadsApiClient\DownloadsApiClientRuntimeException;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

use Germania\DownloadsApiClient\HttpClientFactory;

use GuzzleHttp\Client;
use Prophecy\Argument;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class DownloadsApiHttpClientTest extends \PHPUnit\Framework\TestCase
{

    use ProphecyTrait;

	public function testSimpleWithNothingInCache()
	{
		$base_uri = $GLOBALS['DOWNLOADS_API'];
		$token = $GLOBALS['AUTH_TOKEN'];
		$auth_header = sprintf("Bearer %s", $token);


		$response = new Response(200, array(), json_encode(array(
			'data' => array()
		)));

		#$request = $this->prophesize( RequestInterface::class );
		#$request_stub = $request->reveal();


		$client = $this->prophesize( ClientInterface::class );
		$client->sendRequest( Argument::any() )->willReturn( $response );
		$client_stub = $client->reveal();


		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item->set( Argument::type("array") )->shouldBeCalled();
		$cache_item->expiresAfter( Argument::type("integer") )->shouldBeCalled();
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache->save( Argument::any() )->shouldBeCalled();
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiHttpClient( $client_stub, $cache_stub, $token );
        $this->assertInstanceOf(DownloadsApiClientInterface::class, $sut);

		$this->assertTrue( is_callable( $sut ));

		$all = $sut->all([
			"product" => "plissee",
			"category" => "montageanleitung"
		]);

		$this->assertInstanceOf( \Traversable::class, $all);
		$latest = $sut->latest([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $latest);
	}



	public function testSimpleWithCacheHit()
	{
		$base_uri = $GLOBALS['DOWNLOADS_API'];
		$token = $GLOBALS['AUTH_TOKEN'];

		//  $auth_header = sprintf("Bearer %s", $token);
		//  $client = new Client([
		     // 'base_uri' => $base_uri,
		     // 'headers'  => array('Authorization' => $auth_header)
		//  ]);

		$client = (new HttpClientFactory)($base_uri, $token);


		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( true );
		$cache_item->get( )->willReturn( array("foo", "bar"));
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiHttpClient( $client, $cache_stub, $token );
		$this->assertTrue( is_callable( $sut ));

		$all = $sut->all([
			"product" => "plissee",
			"category" => "montageanleitung"
		]);
		$this->assertInstanceOf( \Traversable::class, $all);

		$latest = $sut->latest([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $latest);
	}


	/**
	 * @dataProvider provideMalformedClientHeaders
	 */
	public function DEPRECATEDtestExceptionOnMissingAuthorizationHeader( $invalid_headers )
	{
		// $client = $this->prophesize( Client::class );
		// $client->getConfig( Argument::type("string"))->willReturn( $invalid_headers );
		// $client_stub = $client->reveal();

		$base_uri = $GLOBALS['DOWNLOADS_API'];
		$token = $GLOBALS['AUTH_TOKEN'];
		$client = (new HttpClientFactory)($base_uri, $token);

		$this->expectException( \RuntimeException::class );
		$this->expectException( DownloadsApiClientRuntimeException::class );


		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		new DownloadsApiClient( $client, $cache_stub, $token );

	}

	public function provideMalformedClientHeaders()
	{
		return array(
			[ array("foo" => "bar") ],
			[ false ],
			[ null ]
		);
	}


	public function testEmptyIteratorResultOnRequestException()
	{
		$exception = $this->prophesize( ClientExceptionInterface::class );

		$client = $this->prophesize( ClientInterface::class );
		$client->sendRequest( Argument::type(RequestInterface::class))->willThrow( $exception->reveal() );
		$client_stub = $client->reveal();


		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiHttpClient( $client_stub, $cache_stub, "foo" );

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

		$client = $this->prophesize( ClientInterface::class );
		$client->sendRequest( Argument::type(RequestInterface::class))->willReturn( $response );
		$client_stub = $client->reveal();

		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiHttpClient( $client_stub, $cache_stub, "doo" );

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
