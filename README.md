#ShortifyPunit &nbsp;  
[![Build Status](https://scrutinizer-ci.com/g/danrevah/ShortifyPunit/badges/build.png?b=master)](https://scrutinizer-ci.com/g/danrevah/ShortifyPunit/build-status/master) ![Code Quality](https://scrutinizer-ci.com/g/danrevah/ShortifyPunit/badges/quality-score.png?b=master) [![Coverage Status](https://coveralls.io/repos/danrevah/ShortifyPunit/badge.png?branch=master)](https://coveralls.io/repos/danrevah/ShortifyPunit/badge.png?branch=master) [![Latest Stable Version](https://poser.pugx.org/danrevah/shortifypunit/v/stable.svg)](https://packagist.org/packages/danrevah/shortifypunit) [![Total Downloads](https://poser.pugx.org/danrevah/shortifypunit/downloads.svg)](https://packagist.org/packages/danrevah/shortifypunit)
  
> PHP Mocking Framework, 
> inspired by Mockito library for Java

## Table of contents

 * [Installation](#installation)
 * [Mocking](#mocking-examples)
 * [Stubbing](#stubbing)
 * [Spies](#spies)
 * [Stubbing Method Chaining](#stubbing-method-chaining)
 * [Verifying](#verifying)
 * [Argument Matcher](#argument-matcher)

## Installation

The following instructions outline installation using Composer. If you don't
have Composer, you can download it from [http://getcomposer.org/](http://getcomposer.org/)

 * Run either of the following commands, depending on your environment:

```
$ composer require "danrevah/shortifypunit":"1.0.*" 
$ php composer.phar require "danrevah/shortifypunit":"1.0.*"
```

## Mocking Examples
```php
// Creating a new mock for SimpleClassForMocking
$mock = ShortifyPunit::mock('SimpleClassForMocking');

// Returns NULL, was not stubbed yet
$mock->first_method();
```

Basic mocking example, if a function wasn't stubbed the return value will always be `NULL`.

## Stubbing
```php
// Creating a new mock for SimpleClassForMocking
$mock = ShortifyPunit::mock('SimpleClassForMocking');

// Stubbing first_method() function without arguments
ShortifyPunit::when($mock)->first_method()->returns(1);
echo $mock->first_method(); // prints '1'

// Stubbing first_method() function with arguments
ShortifyPunit::when($mock)->first_method(1,2)->returns(2);
echo $mock->first_method(); // still prints '1'
echo $mock->first_method(1,2); // prints '2'

// Stubbing callback
ShortifyPunit::when($mock)->first_method()->callback(function() { echo 'Foo Bar'; });
echo $mock->first_method(); // prints 'Foo Bar'

// Stubbing throws exception
ShortifyPunit::when($mock)->second_method()->throws(new Exception());
$mock->second_method(); // throws Exception
```
The `when` function is used to stubbing methods with specific parameters, following a `throws`, `returns` or a `callback` action.

Methods:
* `throws($exception)` - Throws an exception
* `returns($response)` - Returns a $response
* `callback(function() { /*...*/ })` - Calling a callback

## Spies

Spies are a partial mock, sometimes you need some of the methods to behave normally except for the one method that you need to test. That so called partial mocking can be done using the spy method

```php
class Foo {
  function bar() { return 'bar'; }
}

$mock = ShortifyPunit::mock('Foo');
$spy = ShortifyPunit::spy('Foo');

$mock->bar(); // returns NULL
echo $spy->bar(); // prints 'bar'

ShortifyPunit::when($spy)->bar()->returns('foo'); // stubbing spy
echo $spy->bar(); // prints 'foo'
```

## Stubbing Method Chaining
```php
 // Creating a new mock for SimpleClassForMocking
 $mock = ShortifyPunit::mock('SimpleClassForMocking');

  ShortifyPunit::when($mock)->first_method()->second_method(1)->returns(1);
  ShortifyPunit::when($mock)->first_method()->second_method(2)->returns(2);
  ShortifyPunit::when($mock)->first_method(1)->second_method(1)->returns(3);
  ShortifyPunit::when($mock)->first_method(2)->second_method(2)->third_method()->returns(4);
  
  echo $mock->first_method()->second_method(1); // prints '1'
  echo $mock->first_method()->second_method(2); // prints '2'
  echo $mock->first_method(1)->second_method(1); // prints '3'
  echo $mock->first_method(2)->second_method(2)->third_method(); // prints '4'
```
`when` function is also used to stub chained methods, follows the same actions as the single function stubbing `return`, `throw` or `callback`.

## Verifying

Once created, mock will remember all invocations. Then you can selectively verify some interaction you are inserted in.

```php
    $mock = ShortifyPunit::mock('SimpleClassForMocking');

    ShortifyPunit::when($mock)->first_method()->returns(1);
    echo $mock->first_method(); // method called once

    ShortifyPunit::verify($mock)->first_method()->neverCalled(); // returns FALSE
    ShortifyPunit::verify($mock)->first_method()->atLeast(2); // returns FALSE
    ShortifyPunit::verify($mock)->first_method()->calledTimes(1); // returns TRUE

    echo $mock->first_method(); // method has been called twice

    ShortifyPunit::verify($mock)->first_method()->neverCalled(); // returns FALSE
    ShortifyPunit::verify($mock)->first_method()->atLeast(2); // returns TRUE
    ShortifyPunit::verify($mock)->first_method()->calledTimes(2); // returns TRUE
```

Methods:
* `atLeast($times)` - Verify called at least $times
* `atLeastOnce()` - Alias of atLeast(1)
* `calledTimes($times)` - Verify called exactly $times
* `neverCalled()` - Alias of calledTimes(0)
* `lessThan($times)` - Verify called less than $times

## Argument Matcher

ShortifyPunit allows the use of Hamcrest PHP (https://github.com/hamcrest/hamcrest-php) matcher on any argument. Hamcrest is a library of "matching functions" that, given a value, return true if that value
matches some rule.

Hamcrest matchers are included by default.

Examples:

```php
class Foo
{
  function bar($arg){}
}

$stub = ShortifyPunit::mock('Foo');
ShortifyPunit::when($stub)->bar(anything())->return('FooBar');
```

Some common Hamcrest matchers:

- Core
	* `anything` - always matches, useful if you don't care what the object under test is
- Logical
	* `allOf` - matches if all matchers match, short circuits (like PHP &&)
	* `anyOf` - matches if any matchers match, short circuits (like PHP ||)
	* `not` - matches if the wrapped matcher doesn't match and vice versa
- Object
	* `equalTo` - test object equality using the == operator
	* `anInstanceOf` - test type
	* `notNullValue`, `nullValue` - test for null
- Number
	* `closeTo` - test floating point values are close to a given value
	* `greaterThan`, `greaterThanOrEqualTo`, `lessThan`, `lessThanOrEqualTo` - test ordering
- Text
	* `equalToIgnoringCase` - test string equality ignoring case
	* `equalToIgnoringWhiteSpace` - test string equality ignoring differences in runs of whitespace
	* `containsString`, `endsWith`, `startsWith` - test string matching
