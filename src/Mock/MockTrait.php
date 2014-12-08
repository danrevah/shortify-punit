<?php
namespace ShortifyPunit\Mock;

use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Enums\MockTypes;
use ShortifyPunit\Exceptions\ExceptionFactory;

trait MockTrait
{
    use ExceptionFactory;
    /**
     * @param \ReflectionMethod[] $methods
     * @param string $namespace
     * @param string $basename
     * @param string $mockedObjectName
     * @param $class
     * @param string $mockType
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
                    \$methodStubbed = {$namespace}\\{$basename}::isMethodStubbed('{$mockedObjectName}', \$this->shortifyPunitInstanceId, '{$methodName}');

                    if (\$methodStubbed) {
                        return {$namespace}\\{$basename}::createResponse('{$mockedObjectName}', \$this->shortifyPunitInstanceId, '{$methodName}', \$args);
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
                    return {$namespace}\\{$basename}::createResponse('{$mockedObjectName}', \$this->shortifyPunitInstanceId, '{$methodName}', func_get_args());
                }
EOT;
            }

        }
        return $class;
    }


    /**
     * @param \ReflectionClass $reflection
     * @param string $namespace
     * @param string $basename
     * @param string $mockType
     * @return mixed
     */
    protected static function mockClass(\ReflectionClass $reflection, $namespace, $basename, $mockType = MockTypes::FULL)
    {
        $mockedObjectName = $reflection->getShortName().($mockType == MockTypes::PARTIAL ? 'PARTIAL' : 'MOCK');

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
   public \$shortifyPunitInstanceId;

   public function __construct() {
        \$this->shortifyPunitInstanceId = {$namespace}\\{$basename}::generateInstanceId();
   }

   public function getShortifyPunitInstanceId() { return \$this->shortifyPunitInstanceId; }
EOT;
        $class = self::mockClassMethods($methods, $namespace, $basename, $mockedObjectName, $class, $mockType);
        $class .= '}';

        eval($class);

        return new $mockedObjectName();
    }

    /**
     * Creating response by response options (Return/Throw/Callback)
     *
     * @param $mockResponse
     * @param $arguments
     * @return mixed
     */
    protected static function generateResponse(&$mockResponse, $arguments)
    {
        self::updateCallCounter($mockResponse);

        list($action, $value) = self::extractResponseValues($mockResponse);

        if ($action == MockAction::THROWS) {
            throw is_object($value) ? $value : new $value;
        }
        else if ($action == MockAction::CALLBACK) {
            return call_user_func_array($value, $arguments);
        }

        return $value;
    }

    /**
     * @param $response
     */
    public static function updateCallCounter(&$response)
    {
        if ( ! array_key_exists('counter', $response)) {
            $response['counter'] = 0;
        }

        $response['counter']++;
    }

    /**
     * @param $response
     * @return array
     */
    private static function extractResponseValues($response)
    {
        if ( ! array_key_exists('action', $response) || !array_key_exists('value', $response)) {
            throw self::generateException('Create chain response corrupt response return values');
        }

        $action = $response['action'];
        $value = $response['value'];

        return array($action, $value);
     }

} 