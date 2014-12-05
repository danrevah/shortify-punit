<?php

use ShortifyPunit\ShortifyPunit;

class VerifyTest extends \PHPUnit_Framework_TestCase
{
    public function testVerifyChainNoParameters()
    {
        $mock = ShortifyPunit::mock('Foo');

        $mock->bar()->foo();

        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->atLeast(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->calledTimes(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->lessThan(2));

        $this->assertFalse(ShortifyPunit::Verify($mock)->bar()->foo()->lessThan(1));
        $this->assertFalse(ShortifyPunit::Verify($mock)->bar()->foo()->calledTimes(2));
        $this->assertFalse(ShortifyPunit::Verify($mock)->bar()->foo()->atLeast(2));
    }

    public function testVerifySingleStubNoParameters()
    {
        $mock = ShortifyPunit::mock('Foo');

        ShortifyPunit::when($mock)->bar()->returns(1);

        $mock->bar();

        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->atLeast(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->calledTimes(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->lessThan(2));

        $mock->bar();

        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->atLeast(2));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->calledTimes(2));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->lessThan(3));

        ShortifyPunit::when($mock)->bar(1)->returns(2);

        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->neverCalled());
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->atLeast(0));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->calledTimes(0));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->lessThan(1));

        $mock->bar(1);

        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->atLeast(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->calledTimes(1));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar(1)->lessThan(2));
    }

    public function testVerify()
    {
        $mock = ShortifyPunit::mock('Foo');

        ShortifyPunit::when($mock)->bar()->foo()->returns(10);


        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->neverCalled());
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->atLeast(0));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->calledTimes(0));
        $this->assertTrue(ShortifyPunit::Verify($mock)->bar()->foo()->lessThan(1));

        $this->assertFalse(ShortifyPunit::Verify($mock)->bar()->foo()->atLeast(1));
        $this->assertFalse(ShortifyPunit::Verify($mock)->bar()->foo()->calledTimes(1));
        $this->assertFalse(ShortifyPunit::Verify($mock)->bar()->foo()->lessThan(0));
    }
} 