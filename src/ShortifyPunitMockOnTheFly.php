<?php
namespace spu;
use spu\Exceptions\ExceptionFactory;

/**
 * Class ShortifyPunitClassOnTheFly
 * @package Spu
 * @desc used on `when_concat` function, creating anonymous functions on-the-fly
 */
class ShortifyPunitMockClassOnTheFly
{
    use ExceptionFactory;

    private $methods = [];

    public function __call($key, $args)
    {
        if ( ! isset($this->methods[$key])) {
            static::throwException("`{$key}` no such method!");
        }

        return call_user_func_array($this->methods[$key], $args);
    }

    public function __set($key, $val)
    {
        $this->methods[$key] = $val;
    }
}