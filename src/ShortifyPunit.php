<?php
namespace ShortifyPunit;

class ShortifyPunit
{
    /**
     * @var int - Last mock instance id (Counter)
     */
    private static $instanceId = 1;

    /**
     * @var string - Mocked classes base prefix
     */
    private static $classBasePrefix = 'ShortifyPunit';

    /**
     * @var array - return values of mocked functions by instance id 
     */
    private static $returnValues = [];

    /**
     * @desc Implementing allowed friend classes / interface in PHP
     */
    private static $friendClasses = ['ShortifyPunit\ShortifyPunit'];

    /**
     * Call static function is used to detect calls to protected & private methods
     * only friend classes are allowed to call private methods (C++ Style)
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

        if ( ! isset($backTrace[1]['class']) && in_array($backTrace[1]['class'], self::$friendClasses)) {
            self::throwException("Error while backtracking calling class");
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
        $instanceId = self::$instanceId++;
        $mockedObjectName = "{$basename}{$instanceId}";

        $className = $reflection->getName();
        $methods = $reflection->getMethods();
        $extends = $reflection->isInterface() ? 'implements' : 'extends';
        $marker = $reflection->isInterface() ? ", {$namespace}\\{$basename}MockInterface" : "implements {$namespace}\\{$basename}MockInterface";

        //if (class_exists($mockedObjectName, FALSE)) {
        //    return $mockedObjectName;
        //}


        $class =<<<EOT
  class $mockedObjectName $extends $className $marker {
EOT;

        foreach ($methods as $method)
        {
            if ( ! $method instanceof \ReflectionMethod) {
                continue;
            }

            // Ignoring final & private methods
            if ($method->isFinal() || $method->isPrivate()) {
                continue;
            }


            $methodName = $method->getName();
            $returnsByReference = $method->returnsReference() ? '&' : '';

            $methodParams = [];
            $callParams = [];

            // Get method params
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
            $callParams = implode(',', $callParams);


            $class .=<<<EOT
    public function $returnsByReference $methodName ({$methodParams}) {
        return {$namespace}\\{$basename}::__create_response('{$mockedClass}', {$instanceId}, '{$methodName}', func_get_args());
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

