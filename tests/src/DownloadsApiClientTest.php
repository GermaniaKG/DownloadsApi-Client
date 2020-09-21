<?php
namespace tests;

use Germania\DownloadsApiClient\DownloadsApiClient;
use Germania\DownloadsApiClient\DownloadsApiClientUnexpectedValueException;
use Germania\DownloadsApiClient\DownloadsApiClientRuntimeException;
use GuzzleHttp\Client;
use Prophecy\Argument;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class DownloadsApiClientTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

	public function testDefaultCacheLifeTime()
	{
		$response = new Response(200, array(), json_encode(array(
			'data' => array()
		)));

		$client = $this->prophesize( Client::class );
		// $client->get( Argument::type("string"), Argument::type("array") )->willReturn( $response );
		$client->getConfig( Argument::type("string") )->willReturn( array('Authorization' => "foo"));
		$client_stub = $client->reveal();

		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiClient( $client_stub, $cache_stub );

		// Test
		$default_cache_lifetime = $sut->getDefaultCacheLifetime();
		$this->assertIsInt( $default_cache_lifetime );

		$test_value = $default_cache_lifetime + 1;
		$sut->setDefaultCacheLifetime( $test_value );

		$new_default_cache_lifetime = $sut->getDefaultCacheLifetime();
		$this->assertEquals( $test_value, $new_default_cache_lifetime );
	}


	public function testStashPrecomputeTime()
	{
		$response = new Response(200, array(), json_encode(array(
			'data' => array()
		)));

		$client = $this->prophesize( Client::class );
		// $client->get( Argument::type("string"), Argument::type("array") )->willReturn( $response );
		$client->getConfig( Argument::type("string") )->willReturn( array('Authorization' => "foo"));
		$client_stub = $client->reveal();

		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiClient( $client_stub, $cache_stub );

		// Test
		$stash_precompute_time = $sut->getStashPrecomputeTime();
		$this->assertIsInt( $stash_precompute_time );

		$test_value = $stash_precompute_time + 1;
		$sut->setStashPrecomputeTime( $test_value );

		$new_default_cache_lifetime = $sut->getStashPrecomputeTime();
		$this->assertEquals( $test_value, $new_default_cache_lifetime );
	}





	public function testSimpleWithNothingInCache()
	{
		$base_uri = $GLOBALS['DOWNLOADS_API'];
		$token = $GLOBALS['AUTH_TOKEN'];
		$auth_header = sprintf("Bearer %s", $token);


		$response = new Response(200, array(), json_encode(array(
			'data' => array()
		)));


		$client = $this->prophesize( Client::class );
		$client->get( Argument::type("string"), Argument::type("array") )->willReturn( $response );
		$client->getConfig( Argument::type("string") )->willReturn( array('Authorization' => "foo"));
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

		$sut = new DownloadsApiClient( $client_stub, $cache_stub );
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
		$auth_header = sprintf("Bearer %s", $token);

		$client = new Client([
		    'base_uri' => $base_uri,
		    'headers'  => array('Authorization' => $auth_header)
		]);

		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( true );
		$cache_item->get( )->willReturn( array("foo", "bar"));
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiClient( $client, $cache_stub );
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
	public function testExceptionOnMissingAuthorizationHeader( $invalid_headers )
	{
		$client = $this->prophesize( Client::class );
		$client->getConfig( Argument::type("string"))->willReturn( $invalid_headers );
		$client_stub = $client->reveal();

		$this->expectException( \RuntimeException::class );
		$this->expectException( DownloadsApiClientRuntimeException::class );


		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		new DownloadsApiClient( $client_stub, $cache_stub );

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
		$exception = $this->prophesize( ClientException::class );

		$client = $this->prophesize( Client::class );
		$client->getConfig( Argument::type("string"))->willReturn( array("Authorization" => "foobar") );
		$client->get( Argument::type("string"), Argument::type("array"))->willThrow( $exception->reveal() );
		$client_stub = $client->reveal();

		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiClient( $client_stub, $cache_stub );

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
		$client->getConfig( Argument::type("string"))->willReturn( array("Authorization" => "foobar") );
		$client->get( Argument::type("string"), Argument::type("array"))->willReturn( $response );
		$client_stub = $client->reveal();

		$cache_item = $this->prophesize(CacheItemInterface::class);
		$cache_item->isHit()->willReturn( false );
		$cache_item_stub = $cache_item->reveal();

		$cache = $this->prophesize( CacheItemPoolInterface::class );
		$cache->getItem( Argument::type("string") )->willReturn( $cache_item_stub );
		$cache_stub = $cache->reveal();

		$sut = new DownloadsApiClient( $client_stub, $cache_stub );

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
