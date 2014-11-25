<?php
// @todo Switch to bootstrap autoloader!
require_once dirname(dirname(__FILE__)).'/src/ShortifyPunit.php';
require_once dirname(__FILE__).'/TestClasses.php';

use ShortifyPunit\ShortifyPunit;

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
     * Testing concatenation of functions
     *
     * @checks return values of concatenated functions, and checking that expect of the first function
     * no other function has been manipulated so the user could mock other values in case of direct call
     */
    public function testWhenConcatenation()
    {
        $mock = ShortifyPunit::mock('SimpleClassForMocking');

        ShortifyPunit::when($mock)->second_method()->returns('second');
        $this->assertEquals('second', $mock->second_method());

        ShortifyPunit::when_concat($mock, array('first_method',
                                                'second_method'),
                                   'abc');

        // asserting that first method returns `MockClassOnTheFly` object
        $this->assertInstanceOf('ShortifyPunit\MockClassOnTheFly', $mock->first_method());

        // asserting concatenation
        $this->assertEquals('abc', $mock->first_method()->second_method());

        // checking that second_method wasn't changed due to concatenation
        $this->assertEquals('second', $mock->second_method());
    }
}