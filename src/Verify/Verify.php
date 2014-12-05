<?php
namespace ShortifyPunit\Verify;


use ShortifyPunit\Exceptions\ExceptionFactory;
use ShortifyPunit\Mock\MockInterface;
use ShortifyPunit\ShortifyPunit;

class Verify
{
    use ExceptionFactory;

    /**
     * @var MockInterface
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
        $this->instanceId = $class->getInstanceId();
    }

    public function __call($method, $args)
    {
        $this->methods[] = [$method => $args];
        return $this;
    }

    public function neverCalled()
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter == 0);
    }

    public function atLeast($count)
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter >= $count);
    }

    public function lessThan($count)
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter < $count);
    }

    public function calledTimes($count)
    {
        $counter = self::getChainedMockCounter($this->methods);

        return ($counter == $count);
    }

    private function getChainedMockCounter($methods)
    {
        $mockReturnValues = ShortifyPunit::getReturnValues();

        if ( ! isset($mockReturnValues[$this->mockedClass][$this->instanceId])) {
            return 0;
        }

        $mockResponse = $mockReturnValues[$this->mockedClass][$this->instanceId];

        foreach ($methods as $method)
        {
            $methodName = key($method);
            $serializedArgs = serialize($method[$methodName]);

            if ( ! isset($mockResponse[$methodName][$serializedArgs])) {
                break;
            }

            $mockResponse = $mockResponse[$methodName][$serializedArgs];
        }

        $counter = isset($mockResponse['response']['counter']) ? $mockResponse['response']['counter'] : 0;

        return $counter;
    }
} 