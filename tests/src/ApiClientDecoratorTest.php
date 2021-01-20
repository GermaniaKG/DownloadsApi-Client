<?php
namespace tests;

use Germania\DownloadsApiClient\ApiClientDecorator;
use Germania\DownloadsApiClient\ApiClientInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class ApiClientDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public function testInstantiation() : ApiClientInterface
    {
        $client_mock = $this->prophesize(ApiClientInterface::class);
        $client = $client_mock->reveal();

        $sut = new ApiClientDecorator($client);
        $this->assertInstanceOf(ApiClientInterface::class, $sut);

        return $sut;
    }



}
