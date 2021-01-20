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
    Message\RequestInterface,
    Message\ResponseInterface,
};

use Germania\ResponseDecoder\JsonApiResponseDecoder;
use Germania\ResponseDecoder\ReponseDecoderException;

use GuzzleHttp\Psr7\Response;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ApiClientTest extends \PHPUnit\Framework\TestCase
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



	public function testInstantiation() : ApiClient
	{
        $client_stub = $this->prophesize(ClientInterface::class);
        $client = $client_stub->reveal();

        $request_stub = $this->prophesize(RequestInterface::class);
        $request = $request_stub->reveal();

		$sut = new ApiClient( $client, $request );

        $this->assertInstanceOf(ApiClientInterface::class, $sut);
		$this->assertTrue( is_callable( $sut ));

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


    public function testRealApiCall() : void
    {
        $sut = new ApiClient( $this->client, $this->request );

        $all = $sut->all([
            "product" => "plissee",
            "category" => "montageanleitung"
        ]);

        $this->assertIsIterable($all);

        $latest = $sut->latest([  "product" => "plissee" ]);
        $this->assertIsIterable( $latest);

    }








	public function testEmptyIteratorResultOnRequestException() : void
	{
		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))->willThrow( ClientExceptionInterface::class );
		$client = $client_stub->reveal();

		$sut = new ApiClient( $client, $this->request);
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
	public function testExceptionOnWeirdResponseBody( string $body ) : void
	{
		$response = new Response( 200, array(), $body );

		$client_stub = $this->prophesize( ClientInterface::class );
		$client_stub->sendRequest( Argument::type(RequestInterface::class))->willReturn( $response );
		$client = $client_stub->reveal();

		$sut = new ApiClient( $client, $this->request);

		$this->expectException( ReponseDecoderException::class );
		$all = $sut->all();

		$this->expectException( ReponseDecoderException::class );
		$latest = $sut->latest([  "product" => "plissee" ]);
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
