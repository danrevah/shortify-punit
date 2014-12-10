<?php
namespace ShortifyPunit;

use ShortifyPunit\Enums\MockTypes;
use ShortifyPunit\Matcher\ArgumentMatcher;
use ShortifyPunit\Mock\MockInterface;
use ShortifyPunit\Mock\MockTrait;
use ShortifyPunit\Stub\WhenChainCase;
use ShortifyPunit\Verify\Verify;

abstract class ShortifyPunitBase
{
    use ArgumentMatcher, MockTrait;

    /**
     * @var string - Mocked classes base prefix
     */
    protected static $classBasePrefix = 'ShortifyPunit';

    /**
     * @var string - Current namespace
     */
    protected static $namespace = 'ShortifyPunit';

    /**
     * Mocking interfaces & classes
     *
     * @desc Ignoring final and private methods
     *
     * Examples:
     *      // Creating a new mock for SimpleClassForMocking
     *      $mock = ShortifyPunit::mock('SimpleClassForMocking');
     *
     *      // Returns NULL, was not stubbed yet
     *      $mock->first_method();
     *
     * @param $mockedClass
     * @return mixed
     */
    public static function mock($mockedClass)
    {
        $reflection = self::getMockReflection($mockedClass);

        return static::mockClass($reflection, self::$namespace, self::$classBasePrefix);
    }

    /**
     * Partial Mocking interfaces|classes
     *
     * @desc Partial mock is not stubbing any function by default (to NULL) like in regular mock()
     *
     * Examples:
     *      // class to partial mock / spy
     *      class Foo {
     *        function bar() { return 'bar'; }
     *      }
     *
     *      $mock = ShortifyPunit::mock('Foo');
     *      $spy = ShortifyPunit::spy('Foo');
     *
     *      $mock->bar(); // returns NULL
     *      echo $spy->bar(); // prints 'bar'
     *
     *      ShortifyPunit::when($spy)->bar()->returns('foo'); // stubbing spy
     *      echo $spy->bar(); // prints 'foo'
     *
     * @param $mockedClass
     * @return mixed
     */
    public static function spy($mockedClass)
    {
        $reflection = self::getMockReflection($mockedClass);

        return static::mockClass($reflection, self::$namespace, self::$classBasePrefix, MockTypes::PARTIAL);
    }

    /**
     * Setting up a when case
     *
     * Examples:
     *      // Chain Stubbing
     *      ShortifyPunit::when($mock)->first_method()->second_method(1)->returns(1);
     *      ShortifyPunit::when($mock)->first_method()->second_method(2)->returns(2);
     *      ShortifyPunit::when($mock)->first_method(1)->second_method(1)->returns(3);
     *      ShortifyPunit::when($mock)->first_method(2)->second_method(2)->third_method()->returns(4);
     *
     *      echo $mock->first_method()->second_method(1); // prints '1'
     *      echo $mock->first_method()->second_method(2); // prints '2'
     *      echo $mock->first_method(1)->second_method(1); // prints '3'
     *      echo $mock->first_method(2)->second_method(2)->third_method(); // prints '4'
     *
     * @param MockInterface $mock
     * @return WhenChainCase
     */
    public static function when($mock)
    {
        if ( ! $mock instanceof MockInterface) {
            throw self::generateException('when() must get a mocked instance as parameter');
        }

        return new WhenChainCase($mock);
    }

    /**
     * Verifying method interactions
     *
     * Examples:
     *      ShortifyPunit::when($mock)->first_method()->returns(1);
     *      echo $mock->first_method(); // method called once
     *
     *      ShortifyPunit::verify($mock)->first_method()->neverCalled(); // returns FALSE
     *      ShortifyPunit::verify($mock)->first_method()->atLeast(2); // returns FALSE
     *      ShortifyPunit::verify($mock)->first_method()->calledTimes(1); // returns TRUE
     *
     *      echo $mock->first_method(); // method has been called twice
     *
     *      ShortifyPunit::verify($mock)->first_method()->neverCalled(); // returns FALSE
     *      ShortifyPunit::verify($mock)->first_method()->atLeast(2); // returns TRUE
     *      ShortifyPunit::verify($mock)->first_method()->calledTimes(2); // returns TRUE
     *
     * @param $mock
     * @return Verify
     */
    public static function verify($mock)
    {
        if ( ! $mock instanceof MockInterface) {
            throw self::generateException('verify() must get a mocked instance as parameter');
        }

        return new Verify($mock);
    }

    /**
     * @param $mockedClass
     * @return \ReflectionClass
     */
    protected static function getMockReflection($mockedClass)
    {
        if (!class_exists($mockedClass) and !interface_exists($mockedClass)) {
            throw self::generateException("Mocking failed `{$mockedClass}` No such class or interface");
        }

        $reflection = new \ReflectionClass($mockedClass);

        if ($reflection->isFinal()) {
            throw self::generateException("Unable to mock class {$mockedClass} declared as final");
        }
        return $reflection;
    }

} 