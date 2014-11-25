<?php
namespace spu;

trait ShortifyPunitExceptionFactory
{
    /**
     * Throwing PHPUnit Assert Exception if exists otherwise throwing regular PHP Exception
     * @param $exceptionString
     */
    protected static function throwException($exceptionString)
    {
        $exceptionClass = class_exists('\\PHPUnit_Framework_AssertionFailedError') ? '\\PHPUnit_Framework_AssertionFailedError' : '\\Exception';
        throw new $exceptionClass($exceptionString);
    }
}

class ShortifyPunit
{
    use ShortifyPunitExceptionFactory;

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
    private static $namespace = 'spu';

    /**
     * @var array - return values of mocked functions by instance id
     */
    private static $returnValues = [];

    /**
     * @var array of allowed friend classes, that could access private methods of this class
     */
    private static $friendClasses = ['spu\ShortifyPunitWhenCase'];

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
            self::throwException("{$class} has no such method!");
        }

        $backTrace = debug_backtrace();

        if ( ! isset($backTrace[2]['class'])) {
            self::throwException("Error while backtracking calling class");
        }

        $basename = self::$classBasePrefix;
        $namespace = self::$namespace;

        $reflection = new \ReflectionClass($backTrace[2]['class']);

        if ( ! $reflection->implementsInterface("{$namespace}\\{$basename}MockInterface") &&
             ! in_array($backTrace[2]['class'], self::$friendClasses))
        {
            self::throwException("{$class} is not a friend class!");
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
            self::throwException("Mocking failed `{$mockedClass}` No such class or interface");
        }

        $reflection = new \ReflectionClass($mockedClass);

        if ($reflection->isFinal()) {
            self::throwException("Unable to mock class {$mockedClass} declared as final");
        }

        $basename = self::$classBasePrefix;
        $namespace = self::$namespace;

        $mockedNamespace = $reflection->getNamespaceName();
        $mockedObjectName = $reflection->getShortName().'Mock';

        $className = $reflection->getName();
        $methods = $reflection->getMethods();
        $extends = $reflection->isInterface() ? 'implements' : 'extends';
        $marker = $reflection->isInterface() ? ", {$namespace}\\{$basename}MockInterface" : "implements {$namespace}\\{$basename}MockInterface";


        $namespaceDeclaration = $mockedNamespace ? "namespace $mockedNamespace;" : '';
        $mockerClass = $mockedNamespace.'\\'.$mockedObjectName;

        // Prevent duplicate mocking, return new instance of the mocked class
        if (class_exists($mockerClass, FALSE)) {
            return new $mockerClass();
        }

        $class =<<<EOT
  $namespaceDeclaration
  class $mockedObjectName $extends $className $marker {
   public \$mockInstanceId;

   public function __construct() {
        \$this->mockInstanceId = {$namespace}\\{$basename}::generateInstanceId();
   }

EOT;

        /* Mocking methods */
        foreach ($methods as $method)
        {
            if ( ! $method instanceof \ReflectionMethod) {
                continue;
            }

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
            $callParams = [];

            // Get method parameters
            foreach ($method->getParameters() as $param)
            {
                if ( ! $param instanceof \ReflectionParameter) {
                    continue;
                }

                // Get type hinting
                if ($param->isArray()) {
                    $type = 'array ';
                } else if ($param->getClass()) {
                    $type = '\\'.$param->getClass()->getName();
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
                                   '$'.$param->getName() . ($param->isOptional() ? '=' . var_export($paramDefaultValue, 1) : '');
            }

            $methodParams = implode(',', $methodParams);


            $class .=<<<EOT
    public function $returnsByReference $methodName ({$methodParams}) {
        return {$namespace}\\{$basename}::__create_response('{$mockedObjectName}', \$this->mockInstanceId, '{$methodName}', func_get_args());
    }
EOT;

        }

        $class .= '}';


        eval($class);

        $mockObject = new $mockedObjectName();

        return $mockObject;
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

        self::$returnValues[$className][$instanceId][$methodName][$args] = ['action' => $action, 'value' => $returns];
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
     * @param $args
     * @return Mixed | null
     */
    private static function __create_response($className, $instanceId, $methodName, $args)
    {
        $args = serialize($args);


        if (isset(self::$returnValues[$className][$instanceId][$methodName][$args]))
        {
            $return = self::$returnValues[$className][$instanceId][$methodName][$args];

            if ($return['action'] == 'returns') {
                return $return['value'];
            }

            if ($return['action'] == 'throws') {
                throw is_object($return['value']) ? $return['value'] : new $return['value'];
            }
        }

        return NULL;
    }

    /**
     * Setting up a when case
     *
     * @param $class
     * @return ShortifyPunitWhenCase
     */
    public static function when($class)
    {
        if ($class instanceof ShortifyPunitMockInterface) {
            return new ShortifyPunitWhenCase(get_class($class), $class->mockInstanceId);
        }
    }

    /**
     * Setting up a when concatenation case
     * @param $class
     * @param array $methods
     * @param $value
     */
    public static function when_concat($class, array $methods, $value)
    {
        if (count($methods) < 2) {
            self::throwException('When concat must get at least methods!');
        }

        $reversedMethods = array_reverse($methods);

        // pop out the last element (=first before using array_reverse)
        $lastElement = array_pop($reversedMethods);
        $lastClass = false;

        // now after the array_pop this will loop only the functions without the first method
        foreach ($reversedMethods as $method)
        {
            if ( ! is_string($method)) {
                self::throwException('Invalid method name!');
            }

            $fakeClass = new MockClassOnTheFly();

            if ($lastClass === false)
            {
                // @todo Don't forget to handle arguments with func get args
                $fakeClass->$method = function() use ($value) {
                    return $value;
                };
            }
            else
            {
                $fakeClass->$method = function() use ($lastClass) {
                  return $lastClass;
                };
            }

            $lastClass = $fakeClass;
        }

        if ($class instanceof ShortifyPunitMockInterface) {
            $whenCase = new ShortifyPunitWhenCase(get_class($class), $class->mockInstanceId, $lastElement);
            $whenCase->setMethod([], 'returns', $lastClass);
        }

    }
}

/**
 * Class ShortifyPunitWhenCase
 * @package ShortifyPunit
 * @desc When Case, is used to set up mocking response using specific call arguments
 *       and return action (throw exception, return value, ..)
 */
class ShortifyPunitWhenCase
{
    use ShortifyPunitExceptionFactory;

    private $className;
    private $method;
    private $args;

    public function __construct($className, $instanceId, $method = '')
    {
        $this->className = $className;
        $this->instanceId = $instanceId;
        $this->method = $method;
    }

    public function setMethod($args, $action, $returns)
    {
        ShortifyPunit::setWhenMockResponse($this->className, $this->instanceId, $this->method, $args, $action, $returns);
    }

    public function __call($method, $args)
    {
        if (empty($this->method))
        {
            if (method_exists($this->className, $method)) {
                $this->method = $method;
                $this->args = $args;
            }
            else {
                static::throwException("`{$method}` method doesn't exist in {$this->className} !");
            }
        }
        else
        {
            if ( ! isset($args[0])) {
                static::throwException("Invalid call to ShortifyPunitWhenCase!");
            }

            $value = $args[0];

            switch($method)
            {
                case 'throws':
                case 'returns':
                    $this->setMethod($this->args, $method, $value);
                    break;

                default:
                    static::throwException("`{$method}` no such action!");
                    break;
            }
        }

        return $this;
    }
}

/**
 * Interface ShortifyPunitMockInterface
 * @package ShortifyPunit
 * @desc interface for mocked classes & interfaces
 */
interface ShortifyPunitMockInterface
{
}

/**
 * Class ShortifyPunitClassOnTheFly
 * @package ShortifyPunit
 * @desc used on `when_concat` function, creating anonymous functions on-the-fly
 */
class MockClassOnTheFly
{
    use ShortifyPunitExceptionFactory;

    private $methods = [];

    public function __call($key, $args)
    {
        if ( ! isset($this->methods[$key])) {
            static::throwException("`{$key}` no such method!");
        }

        return call_user_func_array($this->methods[$key], $args);
    }

    public function __set($key, $val)
    {
        $this->methods[$key] = $val;
    }
}