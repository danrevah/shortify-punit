<?php
use ShortifyPunit\ShortifyPunit;
use ShortifyPunit\Stub\WhenCase;

class ShortifyPunitWhenCaseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testMethodWhenCalls()
    {
        $mock = ShortifyPunit::mock('\Exception');
        $whenCase = new WhenCase(get_class($mock), $mock->mockInstanceId);
        $whenCase->test(array());
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testMethodWhenCallsAllowedActions()
    {
        $mock = ShortifyPunit::mock('\Exception');
        $whenCase = new WhenCase(get_class($mock), $mock->mockInstanceId, 'abc');
        $whenCase->test();
    }

}