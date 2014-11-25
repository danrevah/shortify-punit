<?php
namespace spu\Exceptions;

trait ExceptionFactory
{
    /**
     * Throwing PHPUnit Assert Exception if exists otherwise throwing regular PHP Exception
     * @param $exceptionString
     */
    protected static function throwException($exceptionString)
    {
        $exceptionClass = class_exists('\\PHPUnit_Framework_AssertionFailedError') ? '\\PHPUnit_Framework_AssertionFailedError' : '\\Exception';
        throw new $exceptionClass($exceptionString);
    }
}