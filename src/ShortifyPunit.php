<?php
namespace ShortifyPunit;

use ShortifyPunit\Mock\MockInterface;
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Exceptions\ExceptionFactory;
use ShortifyPunit\Stub\WhenChainCase;
use ShortifyPunit\Mock\MockClass;

class ShortifyPunit extends MockClass
{
    use ArgumentMatcher, ExceptionFactory;

    /**
     * @var int - Last mock instance id (Counter)
     */
    private static $instanceId = 0;

    /**
     * @var string - Mocked classes base prefix
     */
    private static $classBasePrefix = 'ShortifyPunit';

    /**
     * @var string - Current namespace
     */
    private static $namespace = 'ShortifyPunit';


    /**
     * @var array of allowed friend classes, that could access private methods of this class
     */
    private static $friendClasses = ['ShortifyPunit\Stub\WhenCase', 'ShortifyPunit\Mock\MockClassOnTheFly', 'ShortifyPunit\Stub\WhenChainCase'];

    /**
     * Call static function is used to detect calls to protected & private methods
     * only friend classes are allowed to call private methods (C++ Style)
     *
     * + Friend Classes are those who Implement the mocking interface (ShortifyPunitMockInterface)
     *   or is set in $friendClasses variable
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $class = get_called_class();

        if ( ! method_exists($class, $name)) {
            throw self::generateException("{$class} has no such method!");
        }

        $backTrace = debug_backtrace();
        $callingClassName = $backTrace[2]['class'];

        $namespace = self::$namespace;

        $reflection = new \ReflectionClass($callingClassName);

        if ( ! $reflection->implementsInterface("{$namespace}\\Mock\\MockInterface") &&
             ! in_array($callingClassName, self::$friendClasses))
        {
            throw self::generateException("{$class} is not a friend class!");
        }

        return forward_static_call_array('static::'.$name, $arguments);
    }

    /**
     * Mocking interfaces|classes
     * - Ignoring final and private methods
     *
     * @param $mockedClass
     * @return mixed
     */
    public static function mock($mockedClass)
    {

        if ( ! class_exists($mockedClass) and ! interface_exists($mockedClass)) {
            throw self::generateException("Mocking failed `{$mockedClass}` No such class or interface");
        }

        $reflection = new \ReflectionClass($mockedClass);

        if ($reflection->isFinal()) {
            throw self::generateException("Unable to mock class {$mockedClass} declared as final");
        }

        return static::mockClass($reflection, self::$namespace, self::$classBasePrefix);
    }

    /**
     * Setting up a when case
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
     * @return array
     */
    public static function getReturnValues()
    {
        return self::$returnValues;
    }

    /**
     * @param $returnValues
     */
    public static function setReturnValues($returnValues)
    {
        self::$returnValues = $returnValues;
    }


    /**
     * Generating instance id, function is called from mocked classes using `friend classes` style
     * @return int
     */
    protected static function generateInstanceId()
    {
        return ++self::$instanceId;
    }
}