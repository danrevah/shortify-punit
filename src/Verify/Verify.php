<?php
namespace ShortifyPunit\Verify;


use ShortifyPunit\Exceptions\ExceptionFactory;
use ShortifyPunit\Matcher\ArgumentMatcher;
use ShortifyPunit\Mock\MockInterface;
use ShortifyPunit\ShortifyPunit;

class Verify
{
    use ExceptionFactory, ArgumentMatcher;

    /**
     * @var string
     */
    private $mockedClass;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var integer
     */
    private $instanceId;

    /**
     * @param MockInterface $class
     */
    public function __construct($class)
    {
        $this->mockedClass = get_class($class);
        $this->instanceId = $class->getShortifyPunitInstanceId();
    }

    /**
     * If function does not exist, its a mocked object function.
     * collecting it into array $methods.
     *
     * @param $method
     * @param $args
     * @return $this
     */
    public function __call($method, $args)
    {
        $this->methods[] = [$method => $args];
        return $this;
    }

    /**
     * Validating if chained stubbing has been called
     * at least $count times
     *
     * @param $count
     * @return bool
     */
    public function atLeast($count)
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter >= $count);
    }

    /**
     * Alias for atLeast(1)
     *
     * @return bool
     */
    public function atLeastOnce()
    {
        return $this->atLeast(1);
    }

    /**
     * Validating if chained stubbing has been called
     * less than $count times
     *
     * @param $count
     * @return bool
     */
    public function lessThan($count)
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter < $count);
    }

    /**
     * Validating if chained stubbing has been called
     * exactly $count times
     *
     * @param $count
     * @return bool
     */
    public function calledTimes($count)
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter == $count);
    }

    /**
     * Alias for calledTimes(0)
     *
     * @return bool
     */
    public function neverCalled()
    {
        return $this->calledTimes(0);
    }

    /**
     * Getting the call counter for the specific chained
     * stubbing methods
     *
     * @param $methods
     * @return int
     */
    private function getChainedMockCounter($methods)
    {
        $mockReturnValues = ShortifyPunit::getReturnValues();

        $mockResponse = $mockReturnValues[$this->mockedClass][$this->instanceId];

        foreach ($methods as $method)
        {
            $methodName = key($method);
            $args = $method[$methodName];
            $serializedArgs = serialize($args);

            if ( ! isset($mockResponse[$methodName][$serializedArgs]))
            {
                if ( ! isset($mockResponse[$methodName])) {
                    break;
                }

                // try to finding matching Hamcrest-API Function (anything(), equalTo())
                $serializedArgs = static::checkMatchingArguments($mockResponse[$methodName], $args);

                if (is_null($serializedArgs)) {
                    break;
                }
            }

            $mockResponse = $mockResponse[$methodName][$serializedArgs];
        }

        return isset($mockResponse['response']['counter']) ? $mockResponse['response']['counter'] : 0;
    }
    
    /**
     * reset counter to 0
     *
     * @return bool
     */
    public function resetCounter()
    {
        self::doResetCounter($this->methods);

    }


    function doResetCounter($methods) {
        
        $mockReturnValues = ShortifyPunit::getReturnValues();

        $mockResponse = $mockReturnValues[$this->mockedClass][$this->instanceId];

        foreach ($methods as $method)
        {
            $methodName = key($method);
            $args = $method[$methodName];
            $serializedArgs = serialize($args);

            if ( ! isset($mockResponse[$methodName][$serializedArgs]))
            {
                if ( ! isset($mockResponse[$methodName])) {
                    break;
                }

                // try to finding matching Hamcrest-API Function (anything(), equalTo())
                $serializedArgs = static::checkMatchingArguments($mockResponse[$methodName], $args);

                if (is_null($serializedArgs)) {
                    break;
                }
            }
            if ( isset($mockResponse[$methodName][$serializedArgs]['response'] ['counter'])) {
                $mockReturnValues[$this->mockedClass][$this->instanceId][$methodName][$serializedArgs]['response'] ['counter'] = 0;
            }
        }

        ShortifyPunit::setReturnValues($mockReturnValues);
    }
    
} 
