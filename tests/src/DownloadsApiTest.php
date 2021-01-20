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
    Message\RequestInterface,
    Message\ResponseInterface,
};

use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ReponseDecoderException;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class DownloadsApiTest extends \PHPUnit\Framework\TestCase
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



    /**
     * Creates a new Api client.
     *
     * @return DownloadsApi
     */
	public function testInstantiation() : DownloadsApi
	{
		$sut = new DownloadsApi( $this->client, $this->request );
        $sut->setLogger( $this->getLogger());

        $this->assertInstanceOf(DownloadsApiInterface::class, $sut);

        return $sut;
	}


    public function testResponseDecorderInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request );
        $res = $sut->setResponseDecoder(new JsonApiResponseDecoder);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);
    }


    public function testClientInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request );
        $psr_18 = $this->prophesize(ClientInterface::class)->reveal();
        $res = $sut->setClient($psr_18);
        $this->assertInstanceOf(DownloadsApiAbstract::class, $res);
    }


    public function testRequestInterceptors() : void
    {
        $sut = new DownloadsApi( $this->client, $this->request );
        $psr_7 = $this->prophesize(RequestInterface::class)->reveal();
        $res = $sut->setRequest($psr_7);
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
     * @depends testInstantiation
     * @param mixed $filter_params
     */
    public function testRealApiCall($filter_params, DownloadsApiInterface $sut ) : void
    {
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
        $factory = new Factory;
        $request = $factory->createRequest($base_uri, $invalid_token);

        $sut->setRequest( $request );

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
            [ $GLOBALS['DOWNLOADS_API'], "invalid" ],
            [ $GLOBALS['DOWNLOADS_API'], "" ],
            [ "invalid",    $GLOBALS['AUTH_TOKEN'] ],
            [ "",           $GLOBALS['AUTH_TOKEN'] ],
            [ "",       "" ],
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
