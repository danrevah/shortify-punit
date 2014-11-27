<?php
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\ShortifyPunit;

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

    /**
     * @checks when which is not instance of Mock
     */
    public function testWhenNotInstanceOf()
    {
        $someClass = new SimpleClassForMocking();
        $when = ShortifyPunit::when($someClass);
        $this->assertNull($when);
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
     * Testing fake stubbing action
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testFakeStubbingAction()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->second_method()->no_such_action('string');
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
        $instanceId = $mock->mockInstanceId;

        $mockTwo = ShortifyPunit::mock('SimpleClassForMocking');
        $mockThree = ShortifyPunit::mock('SimpleClassForMocking');

        $this->assertEquals($instanceId+1, $mockTwo->mockInstanceId);
        $this->assertEquals($instanceId+2, $mockThree->mockInstanceId);
    }

    /**
     * Testing concatenation of functions
     *
     * @checks return values of concatenation functions, and checking that expect of the first function
     * no other function has been manipulated so the user could mock other values in case of direct call
     */
    public function testWhenConcatenation()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->second_method()->returns('second');
        $this->assertEquals('second', $mock->second_method());

        ShortifyPunit::when_chain_methods($mock, array('first_method' => array(),
                                                'second_method' => array()),
                                   MockAction::RETURNS,
                                   'empty');

        // asserting that first method returns `MockClassOnTheFly` object
        $this->assertInstanceOf('ShortifyPunit\ShortifyPunitMockClassOnTheFly', $mock->first_method());

        // asserting concatenation
        $this->assertEquals('empty', $mock->first_method()->second_method());

        // checking that second_method wasn't changed due to concatenation
        $this->assertEquals('second', $mock->second_method());

        // testing with parameters
        ShortifyPunit::when_chain_methods($mock, array('first_method' => array(1,2),
                'second_method' => array(3,4)),
            MockAction::RETURNS, 'two parameters');

        ShortifyPunit::when_chain_methods($mock, array('first_method' => array(1,2),
                'second_method' => array(3,4,5)),
            MockAction::RETURNS, 'three parameters');

        $this->assertEquals('two parameters', $mock->first_method(1,2)->second_method(3,4));
        $this->assertEquals('three parameters', $mock->first_method(1,2)->second_method(3,4, 5));
        $this->assertEquals('empty', $mock->first_method()->second_method()); // still keeping the last value

        // three chaining
        ShortifyPunit::when_chain_methods($mock, array('first_method' => array(1,2,5,6),
                'second_method' => array(3,4,5),
                'params' => array(array(), 1, new SimpleClassForMocking())),
            MockAction::RETURNS, 'three methods');

        $this->assertEquals('three methods', $mock->first_method(1,2,5,6)->second_method(3,4,5)->params(array(), 1, new SimpleClassForMocking()));
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenConcatenationMinimumMethod()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when_chain_methods($mock, array('first_method' => array()), MockAction::RETURNS, 'abc');
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenConcatenationFakeMethodName()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when_chain_methods($mock, array('fake method name' => array()), MockAction::RETURNS, 'abc');
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenConcatenationMethodNotString()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when_chain_methods($mock, array(1, 2), MockAction::RETURNS, 'abc');
    }
}