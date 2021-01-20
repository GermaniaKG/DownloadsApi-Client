<?php
namespace tests;

use Germania\DownloadsApiClient\{
    Factory,
    ApiClient,
    ApiClientAbstract,
    ApiClientInterface,
    ApiClientUnexpectedValueException,
    ApiClientRuntimeException
};


use Psr\Http\{
    Client\ClientInterface,
    Client\ClientExceptionInterface,
    Client\RequestExceptionInterface,
    Message\RequestInterface,
    Message\ResponseInterface,
};

use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ReponseDecoderException;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ApiClientTest extends \PHPUnit\Framework\TestCase
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



	public function testInstantiation() : ApiClient
	{
		$sut = new ApiClient( $this->client, $this->request );
        $sut->setLogger( $this->getLogger());

        $this->assertInstanceOf(ApiClientInterface::class, $sut);
		$this->assertIsCallable( $sut );

        return $sut;
	}


    /**
     * @depends testInstantiation
     */
    public function testResponseDecorderInterceptors( ApiClientInterface $sut ) : void
    {
        $res = $sut->setResponseDecoder(new JsonApiResponseDecoder);
        $this->assertInstanceOf(ApiClientAbstract::class, $res);
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
    public function testRealApiCall($filter_params, ApiClientInterface $sut ) : void
    {
        $all = $sut->all($filter_params);
        $this->assertIsIterable($all);

        $latest = $sut->latest($filter_params);
        $this->assertIsIterable( $latest);

    }




    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testInvalidAuthentication( $filter_params, ApiClientInterface $sut ) : void
    {
        $base_uri      = $GLOBALS['DOWNLOADS_API'];
        $invalid_token ="invalid";

        $factory = new Factory;
        $request = $factory->createRequest($base_uri, $invalid_token);

        $sut->setRequest( $request );

        $this->expectException( ApiClientRuntimeException::class );
        $all = $sut->all( $filter_params);

        $this->expectException( ApiClientRuntimeException::class );
        $latest = $sut->latest($filter_params);
    }



    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
	public function testEmptyIteratorResultOnRequestException($filter_params, ApiClientInterface $sut ) : void
	{

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))
                    ->willThrow( RequestException::class );
		$client = $client_stub->reveal();

		$sut->setClient($client);

        $this->expectException( RequestExceptionInterface::class );
		$all = $sut->all( $filter_params );


        $this->expectException( RequestExceptionInterface::class );
		$latest = $sut->latest( $filter_params);
	}



	/**
     * @depends testInstantiation
	 * @dataProvider provideVariousInvalidResonseBodies
	 */
	public function testExceptionOnWeirdResponseBody( string $body, ApiClientInterface $sut ) : void
	{
		$response = new Response( 200, array(), $body );

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))
                    ->willReturn( $response );
        $client = $client_stub->reveal();

		$sut->setClient($client);

		$this->expectException( ReponseDecoderException::class );
		$all = $sut->all();

		$this->expectException( ReponseDecoderException::class );
		$latest = $sut->latest();
	}



    /**
     * @return string[]
     */
	public function provideVariousInvalidResonseBodies() : array
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
