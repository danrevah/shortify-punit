<?php
namespace ShortifyPunit\Exceptions;

trait ExceptionFactory
{
    /**
     * Throwing PHPUnit Assert Exception if exists otherwise throwing regular PHP Exception
     * @param $exceptionString
     * @throws \Exception | \PHPUnit_Framework_AssertionFailedError
     */
    protected static function generateException($exceptionString)
    {
        $exceptionClass = class_exists('\\PHPUnit_Framework_AssertionFailedError') ? '\\PHPUnit_Framework_AssertionFailedError' : '\\Exception';
        return new $exceptionClass($exceptionString);
    }
}