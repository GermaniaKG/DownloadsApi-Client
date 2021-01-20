<?php
namespace tests;

use Germania\DownloadsApi\DownloadsApiDecorator;
use Germania\DownloadsApi\DownloadsApiInterface;
use Prophecy\PhpUnit\ProphecyTrait;

class DownloadsApiDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public function testInstantiation() : DownloadsApiInterface
    {
        $client_mock = $this->prophesize(DownloadsApiInterface::class);
        $client = $client_mock->reveal();

        $sut = new DownloadsApiDecorator($client);
        $this->assertInstanceOf(DownloadsApiInterface::class, $sut);

        return $sut;
    }



}
