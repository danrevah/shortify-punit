<?php
namespace ShortifyPunit\Mock;
use ShortifyPunit\Exceptions\ExceptionFactory;

/**
 * Class MockClassOnTheFly
 * @package ShortifyPunit\Mock
 * @desc used on `when_chain` function, creating anonymous functions on-the-fly
 */
class MockClassOnTheFly
{
    use ExceptionFactory;

    private $methods = [];

    public function __call($key, $args)
    {
        if ( ! isset($this->methods[$key])) {
            // Returns NULL if method not found
            return NULL;
        }

        return call_user_func_array($this->methods[$key], $args);
    }


    public function __set($key, $val)
    {
        $this->methods[$key] = $val;
    }
}