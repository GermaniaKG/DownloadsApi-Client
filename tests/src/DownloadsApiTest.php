<?php
namespace tests;

use Germania\DownloadsApi\{
    Factory,
    DownloadsApi,
    DownloadsApiAbstract,
    DownloadsApiInterface,
    Exceptions\DownloadsApiExceptionInterface,
    Exceptions\DownloadsApiResponseException,
    Exceptions\DownloadsApiUnexpectedValueException,
    Exceptions\DownloadsApiRuntimeException
};


use Psr\Http\{
    Client\ClientInterface,
    Client\ClientExceptionInterface,
    Client\RequestExceptionInterface,
    Message\RequestFactoryInterface,
    Message\RequestInterface,
    Message\ResponseInterface,
};

use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ReponseDecoderException;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

use Nyholm\Psr7\Factory\Psr17Factory;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class DownloadsApiTest extends \PHPUnit\Framework\TestCase
{

    use ProphecyTrait,
        LoggerTrait;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    public $request_factory;

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    public $client;


    public function setUp() : void
    {
        $this->client = new GuzzleClient;
        $this->request_factory = new Psr17Factory;
    }



    /**
     * Creates a new Api client.
     *
     * @return DownloadsApi
     */
	public function testInstantiation() : DownloadsApi
	{
		$sut = new DownloadsApi( $this->client, $this->request_factory, $GLOBALS['AUTH_TOKEN'] );
        $sut->setLogger( $this->getLogger());

        $this->assertInstanceOf(DownloadsApiInterface::class, $sut);

        return $sut;
	}


    public function testErrorLevelInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request_factory, $GLOBALS['AUTH_TOKEN'] );

        $res = $sut->setErrorLoglevel(\Psr\Log\LogLevel::DEBUG);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);

        $res = $sut->setSuccessLoglevel(\Psr\Log\LogLevel::DEBUG);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);
    }


    public function testResponseDecorderInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request_factory, $GLOBALS['AUTH_TOKEN'] );
        $res = $sut->setResponseDecoder(new JsonApiResponseDecoder);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);
    }


    public function testClientInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request_factory, $GLOBALS['AUTH_TOKEN'] );
        $psr_18 = $this->prophesize(ClientInterface::class)->reveal();
        $res = $sut->setClient($psr_18);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);
    }


    public function testRequestFactoryInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request_factory, $GLOBALS['AUTH_TOKEN'] );
        $psr_7 = $this->prophesize(RequestFactoryInterface::class)->reveal();
        $res = $sut->setRequestFactory($psr_7);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);
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
     * @param mixed $filter_params
     */
    public function testRealApiCall($filter_params ) : void
    {
        $sut = new DownloadsApi( $this->client, $this->request_factory, $GLOBALS['AUTH_TOKEN'] );
        $sut->setLogger( $this->getLogger() );

        $all = $sut->all($filter_params);
        $this->assertIsIterable($all);

        $latest = $sut->latest($filter_params);
        $this->assertIsIterable( $latest);

    }




    /**
     * @dataProvider provideInvalidAuthentication
     * @depends testInstantiation
     * @param mixed $base_uri
     * @param mixed $invalid_token
     */
    public function testInvalidAuthentication( $base_uri, $invalid_token, DownloadsApiInterface $sut ) : void
    {

        $sut->setAuthentication($invalid_token);

        $filter_params = array();

        $this->expectException( DownloadsApiResponseException::class );
        $this->expectException( DownloadsApiExceptionInterface::class );
        $all = $sut->all( $filter_params);

        $this->expectException( DownloadsApiResponseException::class );
        $this->expectException( DownloadsApiExceptionInterface::class );
        $latest = $sut->latest($filter_params);
    }

    public function provideInvalidAuthentication()
    {
        return array(
            [ DownloadsApi::BASE_URL, "invalid" ],
            [ DownloadsApi::BASE_URL, "" ],
        );
    }





    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
	public function testAllDocumentsEmptyIteratorResultOnRequestException($filter_params, DownloadsApiInterface $sut ) : void
	{

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))
                    ->willThrow( RequestException::class );
		$client = $client_stub->reveal();

		$sut->setClient($client);

        $this->expectException( DownloadsApiExceptionInterface::class );
        $this->expectException( DownloadsApiRuntimeException::class );
		$all = $sut->all( $filter_params );

	}




    /**
     * @dataProvider provideFilterParameters
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testLatestDocumentsEmptyIteratorResultOnRequestException($filter_params, DownloadsApiInterface $sut ) : void
    {

        $client_stub = $this->prophesize( ClientInterface::class );
        $client_stub->sendRequest( Argument::type(RequestInterface::class))
                    ->willThrow( RequestException::class );
        $client = $client_stub->reveal();

        $sut->setClient($client);

        $this->expectException( DownloadsApiExceptionInterface::class );
        $this->expectException( DownloadsApiRuntimeException::class );
        $latest = $sut->latest( $filter_params);
    }






	/**
     * @depends testInstantiation
	 * @dataProvider provideVariousInvalidResonseBodies
	 */
	public function testAllDocumentsExceptionOnWeirdResponseBody( string $body, DownloadsApiInterface $sut ) : void
	{
		$response = new Response( 200, array(), $body );

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))
                    ->willReturn( $response );
        $client = $client_stub->reveal();

		$sut->setClient($client);

        $this->expectException( DownloadsApiUnexpectedValueException::class );
		$sut->all();

	}




    /**
     * @depends testInstantiation
     * @dataProvider provideVariousInvalidResonseBodies
     */
    public function testLatestDocumentsExceptionOnWeirdResponseBody( string $body, DownloadsApiInterface $sut ) : void
    {
        $response = new Response( 200, array(), $body );

        $client_stub = $this->prophesize( ClientInterface::class );
        $client_stub->sendRequest( Argument::type(RequestInterface::class))
                    ->willReturn( $response );
        $client = $client_stub->reveal();

        $sut->setClient($client);

        $this->expectException( DownloadsApiUnexpectedValueException::class );
        $sut->latest();
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
