<?php
namespace ShortifyPunit;

use Hamcrest\AssertionError;
use ShortifyPunit\Mock\MockInterface;
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Exceptions\ExceptionFactory;
use ShortifyPunit\Stub\WhenCase;
use ShortifyPunit\Stub\WhenChainCase;
use ShortifyPunit\Mock\MockClass;

class ShortifyPunit
{
    use MockClass;
    use ArgumentMatcher;
    use ExceptionFactory;

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

        $namespace = self::$namespace;

        $reflection = new \ReflectionClass($backTrace[2]['class']);

        if ( ! $reflection->implementsInterface("{$namespace}\\Mock\\MockInterface") &&
            isset($backTrace[2]['class']) && ! in_array($backTrace[2]['class'], self::$friendClasses))
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

        $basename = self::$classBasePrefix;
        $namespace = self::$namespace;

        $mockedNamespace = $reflection->getNamespaceName();
        $mockedObjectName = $reflection->getShortName().'Mock';

        $className = $reflection->getName();
        $methods = $reflection->getMethods();

        if ($reflection->isInterface()) {
            $extends = 'implements';
            $marker = ", {$namespace}\\Mock\\MockInterface" ;
        }
        else {
            $extends = 'extends';
            $marker = "implements {$namespace}\\Mock\\MockInterface";
        }

        $namespaceDeclaration = $mockedNamespace ? "namespace $mockedNamespace;" : '';
        $mockerClass = "{$mockedNamespace}\\{$mockedObjectName}";

        // Prevent duplicate mocking, return new instance of the mocked class
        if (class_exists($mockerClass, FALSE)) {
            return new $mockerClass();
        }

        $class = static::mockClass($namespaceDeclaration, $mockedObjectName, $extends, $className, $marker, $namespace, $basename, $methods);
        eval($class);

        return new $mockedObjectName();
    }

    /**
     * Setting up a when case
     *
     * @param $mock
     * @return NULL|WhenCase
     */
    public static function when($mock)
    {
        if ($mock instanceof MockInterface) {
            return new WhenChainCase($mock);
        }

        return NULL;
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
     * Setting up a chained mock response, function is called from mocked classes using `friend classes` style
     *
     * @param $chainedMethodsBefore
     * @param $currentMethod
     * @param $args
     * @return null
     */
    private static function __create_chain_response($chainedMethodsBefore, $currentMethod, $args)
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
     * Creating response by response options (Return/Throw/Callback)
     *
     * @param $response
     * @param $arguments
     * @return mixed
     */
    private static function createResponse($response, $arguments)
    {
        if ( ! array_key_exists('action', $response) || ! array_key_exists('value', $response)) {
            throw self::generateException('Create chain response corrupt response return values');
        }

        $action = $response['action'];
        $value = $response['value'];


        if ($action == MockAction::THROWS) {
            throw is_object($value) ? $value : new $value;
        }
        else if ($action == MockAction::CALLBACK) {
            return call_user_func_array($value, $arguments);
        }

        return $value;
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
    private static function setWhenMockResponse($className, $instanceId, $methodName, $args, $action, $returns)
    {
        $args = serialize($args);

        self::$returnValues[$className][$methodName][$instanceId][$args] = ['action' => $action, 'value' => $returns];
    }

    /**
     * Generating instance id, function is called from mocked classes using `friend classes` style
     * @return int
     */
    private static function generateInstanceId()
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
    private static function __create_response($className, $instanceId, $methodName, $arguments)
    {
        $args = serialize($arguments);

        if ( ! isset(self::$returnValues[$className][$methodName][$instanceId][$args]))
        {

            if ( ! isset(self::$returnValues[$className][$methodName][$instanceId])) {
                return NULL;
            }

            $returnValues = self::$returnValues[$className][$methodName][$instanceId];
            $args = static::checkMatchingArguments($returnValues, $arguments);

            if (is_null($args)) {
                return NULL;
            }
        }

        $return = self::$returnValues[$className][$methodName][$instanceId][$args];

        return self::createResponse($return, $arguments);
    }

    /**
     * Adding chained response to ReturnValues array
     *
     * @param $response
     */
    private static function addChainedResponse($response)
    {
        $firstChainedMethodName = key($response);

        if (isset(self::$returnValues[$firstChainedMethodName])) {
            self::$returnValues[$firstChainedMethodName] = array_replace_recursive(self::$returnValues[$firstChainedMethodName],$response[$firstChainedMethodName]);
        } else {
            self::$returnValues[$firstChainedMethodName] = $response[$firstChainedMethodName];
        }
    }
}