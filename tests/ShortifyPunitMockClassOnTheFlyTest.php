<?php
use ShortifyPunit\ShortifyPunitMockClassOnTheFly;

class ShortifyPunitMockClassOnTheFlyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing the create functions on the fly class
     *
     * @expects function creation on the fly to return the return value
     */
    public function testCreateFunctionOnTheFly()
    {
        $fakeClass = new ShortifyPunitMockClassOnTheFly();

        $some_method_1 = 'some_method_1';
        $some_method_2 = 'some_method_2';

        $fakeClass->$some_method_1 = function()  {
            return 1;
        };

        $fakeClass->$some_method_2 = function()  {
            return 2;
        };

        $this->assertEquals($fakeClass->$some_method_1(), 1);
        $this->assertEquals($fakeClass->$some_method_2(), 2);
    }
}