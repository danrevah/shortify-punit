<?php
namespace ShortifyPunit\Mock;

use ShortifyPunit\Enums\MockTypes;

trait MockTrait
{
    /**
     * @param $methods
     * @param $namespace
     * @param $basename
     * @param $mockedObjectName
     * @param $class
     * @param $mockType
     * @return string
     */
    protected static function mockClassMethods($methods, $namespace, $basename, $mockedObjectName, $class, $mockType)
    {
        /* Mocking methods */
        foreach ($methods as $method)
        {
            /** @var $method \ReflectionMethod */
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


            if ($mockType == MockTypes::PARTIAL)
            {
                $class .= <<<EOT
                public function $returnsByReference $methodName ({$methodParams}) {
                    \$args = func_get_args();
                    \$methodStubbed = {$namespace}\\{$basename}::_is_method_stubbed('{$mockedObjectName}', \$this->mockInstanceId, '{$methodName}', \$args);

                    if (\$methodStubbed) {
                        return {$namespace}\\{$basename}::_create_response('{$mockedObjectName}', \$this->mockInstanceId, '{$methodName}', \$args);
                    } else {
                        return call_user_func_array(array('parent', __FUNCTION__), \$args);
                    }
                }
EOT;
            }
            else
            {
                $class .= <<<EOT
                public function $returnsByReference $methodName ({$methodParams}) {
                    return {$namespace}\\{$basename}::_create_response('{$mockedObjectName}', \$this->mockInstanceId, '{$methodName}', func_get_args());
                }
EOT;
            }

        }
        return $class;
    }


    /**
     * @param \ReflectionClass $reflection
     * @param $namespace
     * @param $basename
     * @param string $mockType
     * @return mixed
     */
    protected static function mockClass(\ReflectionClass $reflection, $namespace, $basename, $mockType = MockTypes::FULL)
    {
        $mockedObjectName = $reflection->getShortName().(MockTypes::PARTIAL ? 'PARTIAL' : 'MOCK');

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
        $class = self::mockClassMethods($methods, $namespace, $basename, $mockedObjectName, $class, $mockType);
        $class .= '}';

        eval($class);

        return new $mockedObjectName();
    }

} 