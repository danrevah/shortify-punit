<?php

use ShortifyPunit\ShortifyPunit;

class VerifyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Testing the verify corrupt data return values
     */
    public function testVerifyCorruptDataReturnValues()
    {
        $mock = ShortifyPunit::mock('Foo');
        ShortifyPunit::when($mock)->bar()->foo()->returns(1);

        $this->assertTrue(ShortifyPunit::verify($mock)->blabla()->calledTimes(0)); // missing function
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->calledTimes(0)); // this function with that parameter hasn't been stubbed
    }

    /**
     * Testing if verify throws exception if not a mocked object
     *
     * @expectedException \PHPUnit_Framework_AssertionFailedError
     */
    public function testVerifyNotAnInstanceOfMockInterface()
    {
        ShortifyPunit::verify(new Foo());
    }

    /**
     * Basic test verify chain stubbing without any parameter
     */
    public function testVerifyChainNoParameters()
    {
        $mock = ShortifyPunit::mock('Foo');

        ShortifyPunit::when($mock)->bar()->foo()->returns(1);

        $mock->bar()->foo();

        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->foo()->atLeast(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->foo()->calledTimes(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->foo()->lessThan(2));

        $this->assertFalse(ShortifyPunit::verify($mock)->bar()->foo()->lessThan(1));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar()->foo()->calledTimes(2));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar()->foo()->atLeast(2));
    }

    /**
     * Basic single stubbing
     */
    public function testVerifySingleStub()
    {
        $mock = ShortifyPunit::mock('Foo');

        ShortifyPunit::when($mock)->bar()->returns(1);

        $mock->bar();

        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->atLeast(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->calledTimes(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->lessThan(2));

        $mock->bar();

        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->atLeast(2));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->calledTimes(2));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar()->lessThan(3));

        ShortifyPunit::when($mock)->bar(1)->returns(2);

        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->neverCalled());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->atLeast(0));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->calledTimes(0));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->lessThan(1));

        $mock->bar(1);

        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->atLeast(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->calledTimes(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->lessThan(2));
    }

    /**
     * Verifying chained stubbing with parameters
     */
    public function testVerifyWithParameters()
    {
        $mock = ShortifyPunit::mock('Foo');

        ShortifyPunit::when($mock)->bar(1)->foo(2)->returns(10);

        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->neverCalled());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(0));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(0));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(1));

        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(1));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeastOnce());
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(1));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(0));

        $mock->bar(1)->foo(2);

        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->neverCalled());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeastOnce());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(2));

        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(2));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(2));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(1));
    }

    /**
     * Testing with Hamcrest matching functions
     */
    public function testWithHamcrestMatcher()
    {
        $mock = ShortifyPunit::mock('Foo');

        ShortifyPunit::when($mock)->bar(equalTo(1))->foo(anything())->returns(10);

        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->neverCalled());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(0));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(0));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(1));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeastOnce());

        $mock->bar(1)->foo(2);

        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->neverCalled());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeastOnce());
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(1));
        $this->assertTrue(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(2));

        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->atLeast(2));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->calledTimes(2));
        $this->assertFalse(ShortifyPunit::verify($mock)->bar(1)->foo(2)->lessThan(1));
    }
}