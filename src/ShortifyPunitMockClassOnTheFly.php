<?php
namespace ShortifyPunit;
use ShortifyPunit\Exceptions\ExceptionFactory;

/**
 * Class ShortifyPunitClassOnTheFly
 * @package Spu
 * @desc used on `when_concat` function, creating anonymous functions on-the-fly
 */
class ShortifyPunitMockClassOnTheFly
{
    use ExceptionFactory;

    private $methods = [];

    /**
     * @param $key
     * @param $args
     * @throws static
     * @return mixed
     */
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