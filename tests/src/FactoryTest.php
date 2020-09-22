<?php
namespace tests;

use Germania\DownloadsApiClient\Factory;
use Germania\DownloadsApiClient\FactoryInterface;
use Psr\Http\Client\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

	public $api = "http://httpbin.org/bearer";
	public $token = "FooBar";


	public function testInstantiation()
	{
		$sut = new Factory;

        $this->assertInstanceOf(FactoryInterface::class, $sut);
        return $sut;
	}


    /**
     * @depends testInstantiation
     */
    public function testFactoryOnClientInterface( $sut )
    {
        $client = $sut->createClient();

        $this->assertInstanceOf( ClientInterface::class, $client);
    }


    /**
     * @depends testInstantiation
     */
    public function testFactoryOnReuestInterface( $sut )
    {
        $client = $sut->createRequest($this->api, $this->token);

        $this->assertInstanceOf( RequestInterface::class, $client);
    }

}
