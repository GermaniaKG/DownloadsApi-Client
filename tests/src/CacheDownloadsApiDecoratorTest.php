<?php
namespace tests;

use Germania\DownloadsApiClient\{
    CacheDownloadsApiDecorator,
    Factory,
    ApiClient,
    ApiClientInterface
};
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

use Psr\Http\{
    Client\ClientInterface as Psr18Client,
    Client\ClientExceptionInterface,
    Message\RequestInterface,
    Message\ResponseInterface,
};

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use GuzzleHttp\Psr7\Response;

class CacheDownloadsApiDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;


    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    public $request;

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    public $client;


    public function setUp() : void
    {
        $base_uri = $GLOBALS['DOWNLOADS_API'];
        $token = $GLOBALS['AUTH_TOKEN'];

        $factory = new Factory;
        $this->client = $factory->createClient();
        $this->request = $factory->createRequest($base_uri, $token);

    }



    public function testInstantiation() : CacheDownloadsApiDecorator
    {
        $client_mock = $this->prophesize(ApiClientInterface::class);
        $client = $client_mock->reveal();

        $cache_mock = $this->prophesize(CacheItemPoolInterface::class);
        $cache = $cache_mock->reveal();

        $sut = new CacheDownloadsApiDecorator($client, $cache);
        $this->assertInstanceOf(ApiClientInterface::class, $sut);

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testDefaultCacheLifetimeInterceptors( ApiClientInterface $sut ) : void
    {
        $old_ttl = $sut->getDefaultCacheLifetime();
        $new_ttl = 100;

        $result = $sut->setDefaultCacheLifetime($new_ttl)->getDefaultCacheLifetime();
        $this->assertEquals($result, $new_ttl);
    }


    /**
     * @depends testInstantiation
     */
    public function testCacheItemPoolInterceptors( ApiClientInterface $sut ) : void
    {
        $cache_mock = $this->prophesize(CacheItemPoolInterface::class);
        $cache = $cache_mock->reveal();

        $result = $sut->setCacheItemPool($cache);
        $this->assertEquals($result, $sut);
    }


    /**
     * @depends testInstantiation
     */
    public function testMakeCacheKey( ApiClientInterface $sut ) : void
    {
        $result = $sut->makeCacheKey("path", "auth", ["foo", "bar"]);
        $this->assertIsString($result);
    }





    /**
     * @depends testInstantiation
     */
    public function __testRealApiCall( ApiClientInterface $sut ) : void
    {
        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::any() )->shouldBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->deleteItem( Argument::type("string") )->shouldBeCalled();
        $cache_stub->save( Argument::any() )->shouldBeCalled();
        $cache = $cache_stub->reveal();


        $client = new ApiClient( $this->client, $this->request);
        $sut->setClient($client);
        $sut->setCacheItemPool($cache);

        $all = $sut->all([
            "product" => "plissee",
            "category" => "montageanleitung"
        ]);

        $this->assertIsIterable( $all);

        $latest = $sut->latest([  "product" => "plissee" ]);
        $this->assertIsIterable( $latest);
    }





    /**
     * @depends testInstantiation
     */
    public function testSimpleWithCacheHit( CacheDownloadsApiDecorator $sut ) : void
    {
        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( true );
        $cache_item_stub->get( )->willReturn( array("foo", "bar"));
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache = $cache_stub->reveal();

        $decoratee_stub = $this->prophesize(ApiClientInterface::class);
        $decoratee_stub->getAuthentication()->willReturn("auth");
        $decoratee_stub->all( Argument::any())->willReturn( array() );
        $decoratee = $decoratee_stub->reveal();

        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);

        $all = $sut->all([
            "product" => "plissee",
            "category" => "montageanleitung"
        ]);
        $this->assertInstanceOf( \Traversable::class, $all);

        $latest = $sut->latest([  "product" => "plissee" ]);
        $this->assertInstanceOf( \Traversable::class, $latest);
    }


    /**
     * @depends testInstantiation
     */
    public function testSimpleWithNothingInCache(CacheDownloadsApiDecorator $sut ) : void
    {

        $response = new Response(200, array(), json_encode(array(
            'data' => array()
        )));

        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::any() )->shouldBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->deleteItem( Argument::type("string") )->shouldBeCalled();
        $cache_stub->save( Argument::any() )->shouldBeCalled();
        $cache = $cache_stub->reveal();

        $decoratee_stub = $this->prophesize(ApiClientInterface::class);
        $decoratee_stub->getAuthentication()->willReturn("auth");
        $decoratee_stub->all( Argument::any())->willReturn( array() );
        $decoratee = $decoratee_stub->reveal();

        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);


        $all = $sut->all([
            "product" => "plissee",
            "category" => "montageanleitung"
        ]);

        $this->assertInstanceOf( \Traversable::class, $all);

        $latest = $sut->latest([  "product" => "plissee" ]);
        $this->assertInstanceOf( \Traversable::class, $latest);
    }



    /**
     * @depends testInstantiation
     */
    public function testEmptyIteratorResultOnRequestException(CacheDownloadsApiDecorator $sut ) : void
    {

        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->deleteItem( Argument::type("string") )->shouldBeCalled( );
        $cache_stub->save( Argument::type(CacheItemInterface::class) )->shouldBeCalled( );
        $cache = $cache_stub->reveal();

        $http_client_stub = $this->prophesize( Psr18Client::class );
        // $http_client_stub->sendRequest( Argument::type(RequestInterface::class))
        //                  ->shouldBeCalled()
        //                  ->willThrow( ClientExceptionInterface::class );
        $http_client = $http_client_stub->reveal();


        $decoratee = new ApiClient( $http_client, $this->request);
        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);

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



}
