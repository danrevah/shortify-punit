<?php
namespace ShortifyPunit;
use ShortifyPunit\Exceptions\ExceptionFactory;

/**
 * Class ShortifyPunitWhenCase
 * @package Spu
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