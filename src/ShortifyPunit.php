<?php
namespace ShortifyPunit;

class ShortifyPunit
{
    /**
     * @var int - Last mock instance id (Counter)
     */
    private static $instanceId = 0;

    /**
     * @var string - Mocked classes base prefix
     */
    private static $classBasePrefix = 'ShortifyPunit';

    /**
     * @var array - return values of mocked functions by instance id
     */
    private static $returnValues = [];


    /**
     * Call static function is used to detect calls to protected & private methods
     * only friend classes are allowed to call private methods (C++ Style)
     *
     * + Friend Classes are those who Implement the mocking interface (ShortifyPunitMockInterface)
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

        $basename = $namespace = self::$classBasePrefix;
        $reflection = new \ReflectionClass($backTrace[2]['class']);

        if ( ! $reflection->implementsInterface("{$namespace}\\{$basename}MockInterface")) {
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

        $namespace = $basename = self::$classBasePrefix;

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

                // Get params
                $callParams[] = '$'.$param->getName();

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
            //$callParams = implode(',', $callParams);


            $class .=<<<EOT
    public function $returnsByReference $methodName ({$methodParams}) {
        return {$namespace}\\{$basename}::__create_response('{$mockedClass}', \$this->mockInstanceId, '{$methodName}', func_get_args());
    }
EOT;

        }

        $class .= '}';


        eval($class);

        $mockObject = new $mockedObjectName();

        return $mockObject;
    }

    /**
     * Throwing PHPUnit Assert Exception if exists otherwise throwing regular PHP Exception
     * @param $exceptionString
     */
    private static function throwException($exceptionString)
    {
        $exceptionClass = class_exists('\\PHPUnit_Framework_AssertionFailedError') ? '\\PHPUnit_Framework_AssertionFailedError' : '\\Exception';
        throw new $exceptionClass($exceptionString);
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
        if (isset(self::$returnValues[$className][$instanceId][$methodName][$args])) {
            return self::$returnValues[$className][$instanceId][$methodName][$args];
        }

        return NULL;
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

$class = ShortifyPunit::mock('Exception');
if ( ! $class instanceof \Exception) {
    die('Not instance of Exception!');
}
var_dump($class->__toString());

