<?php
namespace ShortifyPunit\Mock;


trait MockClass
{

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
                    '$' . $param->getName() . ($param->isOptional() ? '=' . var_export($paramDefaultValue, 1) : '');
            }

            $methodParams = implode(',', $methodParams);


            $class .= <<<EOT
    public function $returnsByReference $methodName ({$methodParams}) {
        return {$namespace}\\{$basename}::__create_response('{$mockedObjectName}', \$this->mockInstanceId, '{$methodName}', func_get_args());
    }
EOT;

        }
        return $class;
    }


    /**
     * @param $namespaceDeclaration
     * @param $mockedObjectName
     * @param $extends
     * @param $className
     * @param $marker
     * @param $namespace
     * @param $basename
     * @param $methods
     * @return string
     */
    protected static function mockClass($namespaceDeclaration, $mockedObjectName, $extends, $className, $marker, $namespace, $basename, $methods)
    {
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
        return $class;
    }

} 