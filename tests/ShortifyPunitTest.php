<?php
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\ShortifyPunit;
use ShortifyPunit\Mock\MockInterface;

/**
 * Class SimpleClassForMocking
 */
class SimpleClassForMocking
{
    public function first_method() {
        return 1;
    }

    public function second_method() {
        return 2;
    }

    public function third_method() {
        return 3;
    }

    public function fourth_method() {
        return 4;
    }

    public function params(array $arr, SimpleClassForMocking $instance, $code = 1)
    {

    }
}

/**
 * Class FinalClassForMocking
 */
final class FinalClassForMocking
{
}

interface InterfaceTest
{

}

class ShortifyPunitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Basic mocking test
     * @checks instance of PHP Exception Class
     * @expects instance of Exception
     */
    public function testInstanceOfMock()
    {
        $exceptionMock = ShortifyPunit::mock('Exception');

        $this->assertInstanceOf('Exception', $exceptionMock);
    }

    /**
     * Custom mocking test
     * @checks instance of Custom PHP Class
     * @expects instance of the Custom Class
     */
    public function testInstanceOfCustomClass()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        $this->assertInstanceOf('SimpleClassForMocking', $mock);
    }

    public function testStubbingNullIfNotSet()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        $this->assertNull($mock->first_method());
        $this->assertNull($mock->second_method());
    }

    /**
     * @checks Friend classes
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testFriendClasses()
    {
        ShortifyPunit::generateInstanceId();
    }

    /**
     * @checks Private method exist
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testMethodExist()
    {
        ShortifyPunit::some_fake_method();
    }

    /**
     * Mock return values test
     * @checks return values of several mocks
     * @expects correct return value in correct format (int|string)
     */
    public function testStubbingReturnValues()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->returns(1);
        $this->assertEquals(1, $mock->first_method());

        ShortifyPunit::when($mock)->first_method()->returns(2);
        $this->assertEquals(2, $mock->first_method());

        ShortifyPunit::when($mock)->second_method()->returns('string');
        $this->assertEquals('string', $mock->second_method());

        ShortifyPunit::when($mock)->params(array(), $mock, 'abc')->returns(1);
        $this->assertEquals(1, $mock->params(array(), $mock, 'abc'));
    }

    public function testCallbackReturnValues()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');
        ShortifyPunit::when($mock)->first_method()->callback(function() {});
        $mock->first_method();
    }
    /**
     * @checks when which is not instance of Mock
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenNotInstanceOf()
    {
        $someClass = new SimpleClassForMocking();
        ShortifyPunit::when($someClass)->returns(1);
    }

    /**
     * @checks Asserting interface mock
     */
    public function testMockInstance()
    {
        $mock = ShortifyPunit::mock('InterfaceTest');
        $this->assertInstanceOf('InterfaceTest', $mock);
    }

    /**
     * Stubbing throw exceptions test
     * @checks throw exception stubbing
     * @expectedException \Exception
     */
    public function testStubbingThrowException()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->throws(new \Exception());

        $mock->first_method();
    }

    /**
     * Testing fake class mock
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testFakeClassMock()
    {
        $mock = ShortifyPunit::mock('some_fake_class_name_to_mock');
    }

    /**
     * Fail on final class for mocking
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testFinalClassForMock()
    {
        $mock = ShortifyPunit::mock('FinalClassForMocking');
    }

    /**
     * Mock instance id value
     *
     * @checks instance id of mocks are increasing properly
     * @expects counter to increase after each mock so there will be no identical instance id
     */
    public function testInstanceIdOfMocks()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');
        $instanceId = $mock->getInstanceId();

        $mockTwo = ShortifyPunit::mock('SimpleClassForMocking');
        $mockThree = ShortifyPunit::mock('SimpleClassForMocking');

        $this->assertEquals($instanceId+1, $mockTwo->getInstanceId());
        $this->assertEquals($instanceId+2, $mockThree->getInstanceId());
    }

    /**
     * Testing concatenation of functions
     *
     * @checks return values of concatenation functions, and checking that expect of the first function
     * no other function has been manipulated so the user could mock other values in case of direct call
     */
    public function testChainStubbing()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->second_method(2,3)->returns(1);
        ShortifyPunit::when($mock)->first_method()->second_method(2,3,4)->returns(2);
        ShortifyPunit::when($mock)->first_method(1)->second_method(2,3,4)->returns(3);
        ShortifyPunit::when($mock)->first_method(1,2)->second_method(2,3,4)->returns(4);
        ShortifyPunit::when($mock)->first_method(1,2)->second_method(1,8,9)->returns(5);
        ShortifyPunit::when($mock)->first_method(1,2,3)->second_method(1,2)->third_method()->returns(6);
        ShortifyPunit::when($mock)->first_method(1,2)->second_method(1,3)->third_method()->returns(7);
        ShortifyPunit::when($mock)->first_method(1,2)->second_method(1,8)->third_method()->fourth_method(2)->returns(8);
        ShortifyPunit::when($mock)->first_method(1,2)->second_method(1,8)->third_method()->fourth_method(3)->returns(9);
        ShortifyPunit::when($mock)->first_method(1,2)->second_method(1,1)->third_method()->fourth_method(2)->returns(10);

        ShortifyPunit::when($mock)->first_method(equalTo(5))->second_method(1,8)->third_method(anything())->fourth_method(startsWith('foo'))->returns(11);

        $this->assertEquals($mock->first_method()->second_method(2,3), 1);
        $this->assertEquals($mock->first_method()->second_method(2,3,4), 2);
        $this->assertEquals($mock->first_method(1)->second_method(2,3,4), 3);
        $this->assertEquals($mock->first_method(1,2)->second_method(2,3,4), 4);
        $this->assertEquals($mock->first_method(1,2)->second_method(1,8,9), 5);
        $this->assertEquals($mock->first_method(1,2,3)->second_method(1,2)->third_method(), 6);
        $this->assertEquals($mock->first_method(1,2)->second_method(1,3)->third_method(), 7);
        $this->assertEquals($mock->first_method(1,2)->second_method(1,8)->third_method()->fourth_method(2), 8);
        $this->assertEquals($mock->first_method(1,2)->second_method(1,8)->third_method()->fourth_method(3), 9);
        $this->assertEquals($mock->first_method(1,2)->second_method(1,1)->third_method()->fourth_method(2), 10);

        $this->assertEquals($mock->first_method(5)->second_method(1,8)->third_method('foo')->fourth_method('foo bar'), 11);

        $this->assertNull($mock->first_method(1,2)->second_method());
        $this->assertNull($mock->first_method(1,2)->second_method(1,8)->third_method(312321231));
    }

    /**
     * @expectedException Exception
     */
    public function testChainStubbingThrow()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->second_method(2,3)->throws('Exception');

        $mock->first_method()->second_method(2,3);
    }

    /**
     * @expectedException PHPUnit_Framework_AssertionFailedError
     */
    public function testChainNoReturnValue()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->second_method(2,3)->returns();
    }

    /**
     * @expectedException Exception
     */
    public function testChainStubbingCorruptData()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::setReturnValues(array());
        ShortifyPunit::when($mock)->first_method()->second_method(2,3)->throws('Exception');

        $mock->first_method()->second_method(2,3);
    }


    /**
     * Testing the hamcrest functions
     */
    public function testHamcrestTest()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->second_method(anything())->returns(1);
        ShortifyPunit::when($mock)->first_method(1)->second_method(equalTo(1))->returns(2);
        ShortifyPunit::when($mock)->first_method(2)->second_method(anything(), equalTo(1))->returns(3);
        ShortifyPunit::when($mock)->first_method(3)->second_method(containsString('foo bar'), anInstanceOf('SimpleClassForMocking'))->returns(4);
        ShortifyPunit::when($mock)->first_method(equalTo(4))->second_method(1)->returns(5);
        ShortifyPunit::when($mock)->first_method(equalTo(5))->second_method(not(1))->third_method(anyOf(1,2,3))->returns(6);
        ShortifyPunit::when($mock)->first_method(equalTo(5))->second_method(not(1))->third_method(anything())->returns(7);

        $this->assertEquals($mock->first_method()->second_method(1), 1);
        $this->assertEquals($mock->first_method()->second_method(array()), 1);
        $this->assertNull($mock->first_method('foo'));
        $this->assertNull($mock->first_method()->second_method('foo bar', new Exception()));

        $this->assertEquals($mock->first_method(1)->second_method(1), 2);
        $this->assertNull($mock->first_method(1)->second_method('bar'));

        $this->assertEquals($mock->first_method(2)->second_method('anything', 1), 3);
        $this->assertNull($mock->first_method(2)->second_method(false, 2));

        $this->assertEquals($mock->first_method(3)->second_method('foo bar', $mock), 4);
        $this->assertNull($mock->first_method(3)->second_method('foo', $mock));
        $this->assertNull($mock->first_method(3)->second_method('foo bar', new Exception()));

        $this->assertEquals($mock->first_method(4)->second_method(1), 5);

        $this->assertEquals($mock->first_method(5)->second_method(2)->third_method(1), 6);
        $this->assertEquals($mock->first_method(5)->second_method(3)->third_method(2), 6);
        $this->assertEquals($mock->first_method(5)->second_method(2)->third_method(4), 7);
    }

    /**
     * Testing spies
     */
    public function testSpies()
    {
        $spy = ShortifyPunit::spy('SimpleClassForMocking');

        $this->assertEquals($spy->first_method(), 1); // default value
        $this->assertEquals($spy->second_method(), 2); // default value
        $this->assertEquals($spy->third_method(), 3); // default value

        // after stubbing
        ShortifyPunit::when($spy)->first_method()->returns('foo');
        $this->assertEquals($spy->first_method(), 'foo');

        // still default value
        $this->assertEquals($spy->second_method(), 2); // default value
        $this->assertEquals($spy->third_method(), 3); // default value

        // test chain stubbing
        ShortifyPunit::when($spy)->second_method()->third_method()->returns('bar');
        $this->assertEquals($spy->second_method()->third_method(), 'bar');

        // after chaning methods in chain with same arguments are instanceof `MockClassOnTheFly`
        $this->assertInstanceOf('ShortifyPunit\Mock\MockClassOnTheFly', $spy->second_method());
    }
}