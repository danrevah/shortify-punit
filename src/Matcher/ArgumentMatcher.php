<?php

namespace ShortifyPunit\Matcher;

use Hamcrest\AssertionError;
use Hamcrest\DiagnosingMatcher;
use Hamcrest\Matcher;

trait ArgumentMatcher
{

    /**
     * Checking if there is a matching Hamcrest Function
     * in case of founding returns the Hamcrest object otherwise returns NULL
     *
     * @param $returnValues - Current return values hierarchy with the current method name
     * @param $arguments - Arguments to match
     *
     * @return NULL or Hamcrest
     */
    protected static function checkMatchingArguments($returnValues, $arguments)
    {
        // if doesn't have exactly the arguments check for Hamcrest-PHP functions to validate
        foreach ($returnValues as $currentMethodArguments => $currentMethod)
        {
            // if not its not an Hamcrest Function
            if (strpos($currentMethodArguments, 'Hamcrest\\') === false) {
                continue;
            }

            $hamcrest = unserialize($currentMethodArguments);

            try
            {
                // Loop both hamcrest and arguments
                foreach ($arguments as $index => $arg)
                {
                    if ( ! array_key_exists($index, $hamcrest)) {
                        throw new AssertionError('not enough hamcrest indexes');
                    }

                    // @throws Assertion error on failure
                    if ($hamcrest[$index] instanceof Matcher) {
                        assertThat($arg, $hamcrest[$index]);

                    } else {
                        if ($arg != $hamcrest[$index]) {
                            throw new AssertionError();
                        }
                    }

                }
            }
            catch(AssertionError $e) {
                continue;
            }

            // if didn't cached assert error then its matching an hamcrest
            return $currentMethodArguments;
        }

        return NULL;
    }
}