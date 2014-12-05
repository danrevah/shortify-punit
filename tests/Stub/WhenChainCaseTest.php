<?php
use ShortifyPunit\ShortifyPunit;
use ShortifyPunit\Stub\WhenCase;
use ShortifyPunit\Stub\WhenChainCase;

class WhenChainCaseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testExceptionOnNotMockInterface()
    {
        $when = new WhenChainCase(new Foo());
        $when->bar()->returns(2);
    }



}