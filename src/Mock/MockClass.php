<?php
namespace ShortifyPunit\Mock;


use ShortifyPunit\ArgumentMatcher;
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Exceptions\ExceptionFactory;

class MockClass
{
    use ArgumentMatcher, ExceptionFactory;

    /**
     * @var array - return values of mocked functions by instance id
     *
     * Nesting:
     *   - Single Stub: [className][methodName][instanceId][args] = array('action' => ..., 'value' => ...)
     *   - Multiple Stubbing:
     *     - For the first method using the single stub
     *     - For the rest of the methods: [methodName][args]...[methodName][args]... = array('response' => array('action' => ..., 'value' => ...))
     */
    protected static $returnValues = [];

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
     * Creating response by response options (Return/Throw/Callback)
     *
     * @param $response
     * @param $arguments
     * @return mixed
     */
    private static function createResponse($response, $arguments)
    {
        list($action, $value) = self::extractResponseValues($response);


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
    protected static function setWhenMockResponse($className, $instanceId, $methodName, $args, $action, $returns)
    {
        $args = serialize($args);

        self::$returnValues[$className][$methodName][$instanceId][$args] = ['action' => $action, 'value' => $returns];
    }

    /**
     * @param $methods
     * @param $namespace
     * @param $basename
     * @param $mockedObjectName
     * @param $class
     * @return string
     */
    protected static function mockClassMethods($methods, $namespace, $basename, $mockedObjectName, $class)
    {
        /* Mocking methods */
        foreach ($methods as $method)
        {
            // Ignoring final & private methods
            if ($method->isFinal() || $method->isPrivate()) {
                continue;
            }

            // Ignoring constructor (created earlier)
            if ($method->name == '__construct') {
                continue;
            }

            $methodName = $method->getName();
            $returnsByReference = $method->returnsReference() ? '&' : '';

            $methodParams = [];

            // Get method parameters
            foreach ($method->getParameters() as $param)
            {
                // Get type hinting
                if ($param->isArray()) {
                    $type = 'array ';
                } else if ($param->getClass()) {
                    $type = '\\' . $param->getClass()->getName();
                } else {
                    $type = '';
                }

                // Get default value if exists
                try {
                    $paramDefaultValue = $param->getDefaultValue();
                } catch (\ReflectionException $e) {
                    $paramDefaultValue = NULL;
                }

                // Changing the params into php function definition
                $methodParams[] = $type . ($param->isPassedByReference() ? '&' : '') .
                    '$' . $param->getName() . ($param->isOptional() ? '=' . var_export($paramDefaultValue, true) : '');
            }

            $methodParams = implode(',', $methodParams);


            $class .= <<<EOT
    public function $returnsByReference $methodName ({$methodParams}) {
        return {$namespace}\\{$basename}::_create_response('{$mockedObjectName}', \$this->mockInstanceId, '{$methodName}', func_get_args());
    }
EOT;

        }
        return $class;
    }


    /**
     * @param \ReflectionClass $reflection
     * @param $namespace
     * @param $basename
     * @return mixed
     */
    protected static function mockClass(\ReflectionClass $reflection, $namespace, $basename)
    {
        $mockedObjectName = $reflection->getShortName().'Mock';

        $className = $reflection->getName();
        $methods = $reflection->getMethods();

        if ($reflection->isInterface()) {
            $extends = 'implements';
            $marker = ", {$namespace}\\Mock\\MockInterface" ;
        } else {
            $extends = 'extends';
            $marker = "implements {$namespace}\\Mock\\MockInterface";
        }

        $mockedNamespace = $reflection->getNamespaceName();
        $namespaceDeclaration = $mockedNamespace ? "namespace $mockedNamespace;" : '';

        $mockerClass = "{$mockedNamespace}\\{$mockedObjectName}";

        // Prevent duplicate mocking, return new instance of the mocked class
        if (class_exists($mockerClass, FALSE)) {
            return new $mockerClass();
        }

        $class = <<<EOT
  $namespaceDeclaration
  class $mockedObjectName $extends $className $marker {
   public \$mockInstanceId;

   public function __construct() {
        \$this->mockInstanceId = {$namespace}\\{$basename}::generateInstanceId();
   }

EOT;
        $class = self::mockClassMethods($methods, $namespace, $basename, $mockedObjectName, $class);
        $class .= '}';

        eval($class);

        return new $mockedObjectName();
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
     * @param $response
     * @return array
     */
    private static function extractResponseValues($response)
    {
        if (!array_key_exists('action', $response) || !array_key_exists('value', $response)) {
            throw self::generateException('Create chain response corrupt response return values');
        }

        $action = $response['action'];
        $value = $response['value'];
        return array($action, $value);
    }

} 