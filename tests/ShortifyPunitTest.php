<?php
// @todo Switch to bootstrap autoloader!
require_once dirname(dirname(__FILE__)).'/src/ShortifyPunit.php';
use ShortifyPunit\ShortifyPunit;

class ShortifyPunitTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOfMock()
    {
        $exceptionMock = ShortifyPunit::mock('Exception');

        $this->assertInstanceOf('Exception', $exceptionMock);
    }
}