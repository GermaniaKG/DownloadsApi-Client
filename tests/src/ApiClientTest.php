<?php
namespace tests;

use Germania\DownloadsApiClient\ApiClient;
use Germania\DownloadsApiClient\ApiClientAbstract;
use Germania\DownloadsApiClient\ApiClientInterface;
use Germania\DownloadsApiClient\ApiClientUnexpectedValueException;
use Germania\DownloadsApiClient\ApiClientRuntimeException;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

use Germania\DownloadsApiClient\Factory;

use GuzzleHttp\Client;
use Prophecy\Argument;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\ResponseInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class ApiClientTest extends \PHPUnit\Framework\TestCase
{

    use ProphecyTrait;

    public $request;
    public $factory;
    public $client;


    public function setUp() : void
    {
        $base_uri = $GLOBALS['DOWNLOADS_API'];
        $token = $GLOBALS['AUTH_TOKEN'];

        $this->factory = new Factory;
        $this->client = $this->factory->createClient();
        $this->request = $this->factory->createRequest($base_uri, $token);

    }



	public function testInstantiation()
	{
        $client_stub = $this->prophesize(ClientInterface::class);
        $client = $client_stub->reveal();

        $request_stub = $this->prophesize(RequestInterface::class);
        $request = $request_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache = $cache_stub->reveal();

		$sut = new ApiClient( $client, $request, $cache );

        $this->assertInstanceOf(ApiClientInterface::class, $sut);
		$this->assertTrue( is_callable( $sut ));

        return $sut;
	}


    /**
     * @depends testInstantiation
     */
    public function testResponseDecorderInterceptors( $sut )
    {
        $res = $sut->setResponseDecoder(function() {});
        $this->assertInstanceOf(ApiClientAbstract::class, $res);
    }


    /**
     * @depends testInstantiation
     */
    public function testDefaultCacheLifetimeInterceptors( $sut )
    {
        $old_ttl = $sut->getDefaultCacheLifetime();
        $new_ttl = 100;

        $result = $sut->setDefaultCacheLifetime($new_ttl)->getDefaultCacheLifetime();
        $this->assertEquals($result, $new_ttl);
    }


    /**
     * @depends testInstantiation
     */
    public function testStashPrecomputeTimeInterceptors( $sut )
    {
        $old_ttl = $sut->getStashPrecomputeTime();
        $new_ttl = 100;

        $result = $sut->setStashPrecomputeTime($new_ttl)->getStashPrecomputeTime();
        $this->assertEquals($result, $new_ttl);
    }



    public function ___testReal()
    {
        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::type("array") )->shouldBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->save( Argument::any() )->shouldBeCalled();
        $cache = $cache_stub->reveal();



        $sut = new ApiClient( $this->client, $this->request, $cache );
        $all = $sut->all([
            "product" => "plissee",
            "category" => "montageanleitung"
        ]);

        $this->assertTrue( is_iterable($all));

        $latest = $sut->latest([  "product" => "plissee" ]);
        $this->assertTrue( is_iterable($latest));
    }



    public function testSimpleWithNothingInCache()
    {

        $response = new Response(200, array(), json_encode(array(
            'data' => array()
        )));


        $client_stub = $this->prophesize( ClientInterface::class );
        $client_stub->sendRequest( Argument::any() )->willReturn( $response );
        $client = $client_stub->reveal();

        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::type("array") )->shouldBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->save( Argument::any() )->shouldBeCalled();
        $cache = $cache_stub->reveal();

        $sut = new ApiClient( $client, $this->request, $cache );
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
		$cache_item_stub = $this->prophesize(CacheItemInterface::class);
		$cache_item_stub->isHit()->willReturn( true );
		$cache_item_stub->get( )->willReturn( array("foo", "bar"));
		$cache_item = $cache_item_stub->reveal();

		$cache_stub = $this->prophesize( CacheItemPoolInterface::class );
		$cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
		$cache = $cache_stub->reveal();

		$sut = new ApiClient( $this->client, $this->request, $cache );
		$all = $sut->all([
			"product" => "plissee",
			"category" => "montageanleitung"
		]);
		$this->assertInstanceOf( \Traversable::class, $all);

		$latest = $sut->latest([  "product" => "plissee" ]);
		$this->assertInstanceOf( \Traversable::class, $latest);
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

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))->willThrow( $exception->reveal() );
		$client = $client_stub->reveal();


		$cache_item_stub = $this->prophesize(CacheItemInterface::class);
		$cache_item_stub->isHit()->willReturn( false );
		$cache_item = $cache_item_stub->reveal();

		$cache_stub = $this->prophesize( CacheItemPoolInterface::class );
		$cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
		$cache = $cache_stub->reveal();

		$sut = new ApiClient( $client, $this->request, $cache);

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

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))->willReturn( $response );
		$client = $client_stub->reveal();

		$cache_item_stub = $this->prophesize(CacheItemInterface::class);
		$cache_item_stub->isHit()->willReturn( false );
		$cache_item = $cache_item_stub->reveal();

		$cache_stub = $this->prophesize( CacheItemPoolInterface::class );
		$cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
		$cache = $cache_stub->reveal();

		$sut = new ApiClient( $client, $this->request, $cache );

		$this->expectException( ApiClientUnexpectedValueException::class );
		$all = $sut->all();

		$this->expectException( ApiClientUnexpectedValueException::class );
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
