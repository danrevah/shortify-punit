<?php
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
     * Mock return values test
     * @checks return values of several mocks
     * @expects correct return value in correct format (int|string)
     */
    public function testMockReturnValues()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->first_method()->returns(1);
        $this->assertEquals(1, $mock->first_method());

        ShortifyPunit::when($mock)->first_method()->returns(2);
        $this->assertEquals(2, $mock->first_method());

        ShortifyPunit::when($mock)->second_method()->returns('string');
        $this->assertEquals('string', $mock->second_method());
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

        ShortifyPunit::when_concat($mock, array('first_method' => array(),
                                                'second_method' => array()),
                                   'returns',
                                   'empty');

        // asserting that first method returns `MockClassOnTheFly` object
        $this->assertInstanceOf('ShortifyPunit\ShortifyPunitMockClassOnTheFly', $mock->first_method());

        // asserting concatenation
        $this->assertEquals('empty', $mock->first_method()->second_method());

        // checking that second_method wasn't changed due to concatenation
        $this->assertEquals('second', $mock->second_method());

        // testing with parameters
        ShortifyPunit::when_concat($mock, array('first_method' => array(1,2),
                'second_method' => array(3,4)),
            'returns', 'two parameters');

        ShortifyPunit::when_concat($mock, array('first_method' => array(1,2),
                'second_method' => array(3,4,5)),
            'returns', 'three parameters');

        $this->assertEquals('two parameters', $mock->first_method(1,2)->second_method(3,4));
        $this->assertEquals('three parameters', $mock->first_method(1,2)->second_method(3,4, 5));
        $this->assertEquals('empty', $mock->first_method()->second_method()); // still keeping the last value

    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenConcatenationMinimumMethod()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when_concat($mock, array('first_method' => array()), 'returns', 'abc');
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenConcatenationFakeMethodName()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when_concat($mock, array('fake method name' => array()), 'returns', 'abc');
    }

    /**
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testWhenConcatenationMethodNotString()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when_concat($mock, array(1, 2), 'returns', 'abc');
    }
}