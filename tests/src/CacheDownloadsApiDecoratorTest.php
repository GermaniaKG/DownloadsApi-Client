<?php
namespace tests;

use Germania\DownloadsApi\{
    CacheDownloadsApiDecorator,
    Factory,
    DownloadsApi,
    DownloadsApiInterface,
    Exceptions\DownloadsApiResponseException,
};
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;

use Psr\Http\{
    Client\ClientInterface as Psr18Client,
    Client\ClientExceptionInterface,
    Client\RequestExceptionInterface,
    Message\RequestInterface,
    Message\ResponseInterface,
};

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

class CacheDownloadsApiDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait,
        LoggerTrait;


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
        $client_mock = $this->prophesize(DownloadsApiInterface::class);
        $client_mock->getAuthentication()->willReturn("some-auth-ID");
        $client = $client_mock->reveal();

        $cache_mock = $this->prophesize(CacheItemPoolInterface::class);
        $cache = $cache_mock->reveal();

        $sut = new CacheDownloadsApiDecorator($client, $cache);
        $sut->setLogger( $this->getLogger());

        $this->assertInstanceOf(DownloadsApiInterface::class, $sut);

        return $sut;
    }


    /**
     * @depends testInstantiation
     */
    public function testDefaultCacheLifetimeInterceptors( DownloadsApiInterface $sut ) : void
    {
        $old_ttl = $sut->getDefaultCacheLifetime();
        $new_ttl = 100;

        $result = $sut->setDefaultCacheLifetime($new_ttl)->getDefaultCacheLifetime();
        $this->assertEquals($result, $new_ttl);
    }


    /**
     * @depends testInstantiation
     */
    public function testCacheItemPoolInterceptors( DownloadsApiInterface $sut ) : void
    {
        $cache_mock = $this->prophesize(CacheItemPoolInterface::class);
        $cache = $cache_mock->reveal();

        $result = $sut->setCacheItemPool($cache);
        $this->assertEquals($result, $sut);
    }


    /**
     * @depends testInstantiation
     */
    public function testMakeCacheKey( DownloadsApiInterface $sut ) : void
    {
        $result = $sut->makeCacheKey("path", ["foo", "bar"]);
        $this->assertIsString($result);
    }




    /**
     * Data provider for API test calls
     *
     * @return array[]
     */
    public function provideFilterParameters() : array
    {
        return array(
            [ array("product" => "plissee", "category" => "montageanleitung") ],
        );
    }




    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testAllDocumentsApiCallWithCacheMissAndException($filter_params, CacheDownloadsApiDecorator $sut ) : void
    {
        $api_result = array("foo", "bar");
        $api_result_iterator = new \ArrayIterator($api_result);


        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::exact($api_result) )->shouldNotBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldNotBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->deleteItem( Argument::type("string") )->shouldBeCalled();
        $cache_stub->save( Argument::any() )->shouldNotBeCalled();
        $cache = $cache_stub->reveal();

        $decoratee_stub = $this->prophesize(DownloadsApiInterface::class);
        $decoratee_stub->getAuthentication()->willReturn("auth");
        $decoratee_stub->request( Argument::any(), Argument::any())->willThrow( DownloadsApiResponseException::class );
        $decoratee = $decoratee_stub->reveal();

        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);

        $this->expectException(DownloadsApiResponseException::class);
        $sut->all($filter_params);
    }


    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testLatestDocumentsApiCallWithCacheMissAndException($filter_params, CacheDownloadsApiDecorator $sut ) : void
    {
        $api_result = array("foo", "bar");
        $api_result_iterator = new \ArrayIterator($api_result);


        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::exact($api_result) )->shouldNotBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldNotBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->deleteItem( Argument::type("string") )->shouldBeCalled();
        $cache_stub->save( Argument::any() )->shouldNotBeCalled();
        $cache = $cache_stub->reveal();

        $decoratee_stub = $this->prophesize(DownloadsApiInterface::class);
        $decoratee_stub->getAuthentication()->willReturn("auth");
        $decoratee_stub->request( Argument::any(), Argument::any())->willThrow( DownloadsApiResponseException::class );
        $decoratee = $decoratee_stub->reveal();

        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);

        $this->expectException(DownloadsApiResponseException::class);
        $sut->latest($filter_params);
    }




    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testApiCallWithCacheMiss($filter_params, CacheDownloadsApiDecorator $sut ) : void
    {
        $api_result = array("foo", "bar");
        $api_result_iterator = new \ArrayIterator($api_result);


        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( false );
        $cache_item_stub->set( Argument::exact($api_result) )->shouldBeCalled();
        $cache_item_stub->expiresAfter( Argument::type("integer") )->shouldBeCalled();
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache_stub->deleteItem( Argument::type("string") )->shouldBeCalled();
        $cache_stub->save( Argument::any() )->shouldBeCalled();
        $cache = $cache_stub->reveal();

        $decoratee_stub = $this->prophesize(DownloadsApiInterface::class);
        $decoratee_stub->getAuthentication()->willReturn("auth");
        $decoratee_stub->request( Argument::any(), Argument::any())->willReturn(  $api_result );
        $decoratee = $decoratee_stub->reveal();

        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);


        $all = $sut->all($filter_params);
        $this->assertIsIterable( $all);
        $this->assertEquals($api_result, $all);

        $latest = $sut->latest($filter_params);
        $this->assertIsIterable( $latest);
        $this->assertEquals($api_result, $latest);
    }






    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testApiCallWithCacheHit( $filter_params, CacheDownloadsApiDecorator $sut ) : void
    {
        $api_result = array("foo", "bar");
        $api_result_iterator = new \ArrayIterator($api_result);

        $cache_item_stub = $this->prophesize(CacheItemInterface::class);
        $cache_item_stub->isHit()->willReturn( true );
        $cache_item_stub->get( )->willReturn( $api_result );
        $cache_item = $cache_item_stub->reveal();

        $cache_stub = $this->prophesize( CacheItemPoolInterface::class );
        $cache_stub->getItem( Argument::type("string") )->willReturn( $cache_item );
        $cache = $cache_stub->reveal();

        $decoratee_stub = $this->prophesize(DownloadsApiInterface::class);
        $decoratee_stub->getAuthentication()->willReturn("auth");
        $decoratee_stub->all( Argument::any())->willReturn( $api_result);
        $decoratee_stub->latest( Argument::any())->willReturn( $api_result);
        $decoratee = $decoratee_stub->reveal();

        $sut->setClient($decoratee);
        $sut->setCacheItemPool($cache);

        $all = $sut->all( $filter_params );
        $this->assertEquals($api_result, $all);
        $this->assertIsIterable( $all);

        $latest = $sut->latest( $filter_params );
        $this->assertEquals($api_result, $latest);
        $this->assertIsIterable( $latest);
    }



}
