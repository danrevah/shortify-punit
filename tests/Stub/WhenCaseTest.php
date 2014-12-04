<?php
use ShortifyPunit\ShortifyPunit;
use ShortifyPunit\Stub\WhenCase;

class Foo
{
    function bar() {}
}

class ShortifyPunitWhenCaseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testMethodWhenCalls()
    {
        $mock = ShortifyPunit::mock('\Exception');
        $whenCase = new WhenCase(get_class($mock), $mock->getInstanceId());
        $whenCase->test(array());
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testMethodWhenCallsAllowedActions()
    {
        $mock = ShortifyPunit::mock('\Exception');
        $whenCase = new WhenCase(get_class($mock), $mock->getInstanceId(), 'abc');
        $whenCase->test();
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testNoSuchAction()
    {
        $mock = ShortifyPunit::mock('Foo');
        $whenCase = new WhenCase(get_class($mock), $mock->getInstanceId());
        $whenCase->bar(array())->foobar(array());
    }


    /**
     * Testing when case add method
     */
    public function testWhenCaseAddMethod()
    {
        $mock = ShortifyPunit::mock('Foo');
        $whenCase = new WhenCase(get_class($mock), $mock->getInstanceId());
        $whenCase->bar(array())->returns(array());
    }

}