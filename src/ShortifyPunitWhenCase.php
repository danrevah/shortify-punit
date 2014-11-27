<?php
namespace ShortifyPunit;
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Exceptions\ExceptionFactory;

/**
 * Class ShortifyPunitWhenCase
 * @package ShortifyPunit
 * @desc When Case, is used to set up mocking response using specific call arguments
 *       and return action (throw exception, return value, ..)
 */
class ShortifyPunitWhenCase
{
    use ExceptionFactory;

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
                throw static::generateException("`{$method}` method doesn't exist in {$this->className} !");
            }
        }
        else
        {
            if ( ! isset($args[0])) {
                throw static::generateException("Invalid call to ShortifyPunitWhenCase!");
            }

            $value = $args[0];

            switch($method)
            {
                case MockAction::THROWS:
                case MockAction::RETURNS:
                    $this->setMethod($this->args, $method, $value);
                    break;

                default:
                    throw static::generateException("`{$method}` no such action!");
            }
        }

        return $this;
    }
}
