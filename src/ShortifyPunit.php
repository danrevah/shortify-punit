<?php
namespace ShortifyPunit;

use Hamcrest\AssertionError;
use ShortifyPunit\Enums\MockTypes;
use ShortifyPunit\Mock\MockInterface;
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Exceptions\ExceptionFactory;
use ShortifyPunit\Mock\MockTrait;
use ShortifyPunit\Stub\WhenCase;
use ShortifyPunit\Stub\WhenChainCase;

class ShortifyPunit
{
    use ArgumentMatcher, MockTrait;

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
     * @var array - return values of mocked functions by instance id
     *
     * Nesting:
     *   - Single Stub: [className][methodName][instanceId][args] = array('action' => ..., 'value' => ...)
     *   - Multiple Stubbing:
     *     - For the first method using the single stub
     *     - For the rest of the methods: [methodName][args]...[methodName][args]... = array('response' => array('action' => ..., 'value' => ...))
     */
    private static $returnValues = [];


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
     * Checking if a method with specific arguments has been stubbed
     *
     * @param $className
     * @param $instanceId
     * @param $methodName
     * @return bool
     */
    protected static function _is_method_stubbed($className, $instanceId, $methodName)
    {
        // check if instance of this method even exist
        if ( ! isset(self::$returnValues[$className][$methodName][$instanceId])) {
            return FALSE;
        }

        return TRUE;
    }
    /**
     * Setting up a chained mock response, function is called from mocked classes using `friend classes` style
     *
     * @param $chainedMethodsBefore
     * @param $currentMethod
     * @param $args
     * @return null
     */
    protected static function _create_chain_response($chainedMethodsBefore, $currentMethod, $args)
    {
        $rReturnValues = &self::$returnValues;
        $currentMethodName = key($currentMethod);

        // Check return values chain
        foreach ($chainedMethodsBefore as $chainedMethod)
        {
            $chainedMethodName = key($chainedMethod);
            $chainedMethodArgs = $chainedMethod[$chainedMethodName];

            $serializedChainMethodArgs = serialize($chainedMethodArgs);

            $rReturnValues = &$rReturnValues[$chainedMethodName][$serializedChainMethodArgs];
        }

        // Check current method exist in return values chain
        $serializedArgs = serialize($args);

        if ( ! isset($rReturnValues[$currentMethodName][$serializedArgs]))
        {
            $serializedArgs = static::checkMatchingArguments($rReturnValues[$currentMethodName], $args);

            if (is_null($serializedArgs)) {
                return NULL;
            }
        }

        $response = $rReturnValues[$currentMethodName][$serializedArgs];

        if ( ! array_key_exists('response', $response)) {
            throw self::generateException('Create chain response corrupt response return values');
        }

        $response = $response['response'];

        return self::createResponse($response, $args);
    }



    /**
     * Setting up a mock response, function is called from mocked classes using `friend classes` style
     *
     * @param $className
     * @param $instanceId
     * @param $methodName
     * @param $args
     * @param $action
     * @param $returns
     */
    protected static function setWhenMockResponse($className, $instanceId, $methodName, $args, $action, $returns)
    {
        $args = serialize($args);

        self::$returnValues[$className][$methodName][$instanceId][$args] = ['action' => $action, 'value' => $returns];
    }

    /**
     * Generating instance id, function is called from mocked classes using `friend classes` style
     * @return int
     */
    protected static function generateInstanceId()
    {
        return ++self::$instanceId;
    }

    /**
     * Create response is a private method which is called from the Mocked classes using `friend classes` style
     * returns a value which was set before in the When() function otherwise returning NULL
     *
     * @param $className
     * @param $instanceId
     * @param $methodName
     * @param $arguments
     * @internal param $args
     * @return Mixed | null
     */
    protected static function _create_response($className, $instanceId, $methodName, $arguments)
    {
        $args = serialize($arguments);

        // check if instance of this method even exist
        if ( ! isset(self::$returnValues[$className][$methodName][$instanceId])) {
            return NULL;
        }

        // Check if exist as-is in return values array
        if (isset(self::$returnValues[$className][$methodName][$instanceId][$args]))
        {
            $return = self::$returnValues[$className][$methodName][$instanceId][$args];

            return self::createResponse($return, $arguments);
        }


        // try to finding matching Hamcrest-API Function (anything(), equalTo())
        $returnValues = self::$returnValues[$className][$methodName][$instanceId];
        $args = static::checkMatchingArguments($returnValues, $arguments);

        if (is_null($args)) {
            return NULL;
        }

        $return = self::$returnValues[$className][$methodName][$instanceId][$args];

        return self::createResponse($return, $arguments);
    }

    /**
     * Adding chained response to ReturnValues array
     *
     * @param $response
     */
    protected static function addChainedResponse($response)
    {
        $firstChainedMethodName = key($response);

        if (isset(self::$returnValues[$firstChainedMethodName])) {
            self::$returnValues[$firstChainedMethodName] = array_replace_recursive(self::$returnValues[$firstChainedMethodName],$response[$firstChainedMethodName]);
        } else {
            self::$returnValues[$firstChainedMethodName] = $response[$firstChainedMethodName];
        }
    }

    /**
     * @param $mockedClass
     * @return \ReflectionClass
     */
    private static function getMockReflection($mockedClass)
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