<?php
namespace ShortifyPunit\Stub;
use ShortifyPunit\Enums\MockAction;
use ShortifyPunit\Exceptions\ExceptionFactory;
use ShortifyPunit\ShortifyPunit;

/**
 * Class WhenCase
 * @package ShortifyPunit
 * @desc When Case, is used to set up mocking response using specific call arguments
 *       and return action (throw exception, return value, ..)
 */
class WhenCase
{
    use ExceptionFactory;

    private $className;
    private $method;
    private $args;
    private $instanceId;

    public function __construct($className, $instanceId, $method = '')
    {
        $this->className = $className;
        $this->instanceId = $instanceId;
        $this->method = $method;
    }

    /**
     * @desc Setting up the mock response in the ShortifyPunit Return Values array
     *
     * @param $args
     * @param $action
     * @param $returns
     */
    public function setMethod($args, $action, $returns)
    {
        ShortifyPunit::setWhenMockResponse($this->className, $this->instanceId, $this->method, $args, $action, $returns);
    }

    public function __call($method, $args)
    {
        // set method if hasn't been set yet
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


            // set return / throw method
            if (in_array($method, array(MockAction::CALLBACK, MockAction::RETURNS, MockAction::CALLBACK))) {
                $this->setMethod($this->args, $method, $value);
            }
            else {
                throw static::generateException("`{$method}` no such action!");
            }
        }

        return $this;
    }
}
