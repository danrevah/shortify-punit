<?php
namespace ShortifyPunit;

class ShortifyPunit
{
    private static $instanceId = 1;
    private static $classBasePrefix = 'ShortifyPunit';
    private static $returnValues = [];

    public static function __callStatic($name, $arguments)
    {
        $class = get_called_class();

        if ( ! method_exists($class, $name)) {
            self::throwException("{$class} has no such method!");
        }
            $reflection = new \ReflectionMethod($class, $name);

            if ($reflection->isPublic()) {

            }

        }
    }

    private static function throwException($exceptionString)
    {
        if (class_exists(PHPUnit))
    }

    public static function mock($mockedClass)
    {
        if ( ! class_exists($mockedClass) and ! interface_exists($mockedClass)) {
            throw new \Exception("Mocking failed `{$mockedClass}` No such class or interface");
        }

        $reflection = new \ReflectionClass($mockedClass);

        if ($reflection->isFinal()) {
            throw new \Exception("Unable to mock class {$mockedClass} declared as final");
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

    public static function __create_response($className, $instanceId, $methodName, $args)
    {
        if (isset(self::$returnValues[$className][$instanceId][$methodName][$args])) {
            return self::$returnValues[$className][$instanceId][$methodName][$args];
        }

        return NULL;
    }

   // public static function when()
}

// Identifying mocks
interface ShortifyPunitMockInterface
{
}

$class = ShortifyPunit::mock('Exception');
if ( ! $class instanceof \Exception) {
    die('Not instance of Exception!');
}
var_dump($class->__toString());

