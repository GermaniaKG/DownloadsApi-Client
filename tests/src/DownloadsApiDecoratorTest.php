<?php
namespace tests;

use Germania\DownloadsApi\DownloadsApiDecorator;
use Germania\DownloadsApi\DownloadsApiInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;

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


    /**
     * @depends testInstantiation
     */
    public function testMethodDelegation( DownloadsApiDecorator $sut )
    {
        $filters = array("foo");
        $result = array();

        $client_mock = $this->prophesize(DownloadsApiInterface::class);
        $client_mock->request( Argument::type('string'), Argument::exact($filters))->shouldBeCalled()->willReturn($result);
        $client_mock->all( Argument::exact($filters))->shouldBeCalled()->willReturn($result);
        $client_mock->latest(Argument::exact($filters))->shouldBeCalled()->willReturn($result);
        $client_mock->getAuthentication()->shouldBeCalled();
        $client_mock->setAuthentication( Argument::type("string"))->shouldBeCalled();

        $client = $client_mock->reveal();

        $sut->decorate( $client );
        $sut->request("foo", $filters);
        $sut->latest($filters);
        $sut->setAuthentication( "foo" );
        $sut->getAuthentication();
        $sut->all($filters);
    }



}
