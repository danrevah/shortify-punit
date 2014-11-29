#ShortifyPunit &nbsp; [![Build Status](https://travis-ci.org/danrevah/ShortifyPunit.svg?branch=master)](https://travis-ci.org/danrevah/ShortifyPunit)  [![Coverage Status](https://coveralls.io/repos/danrevah/ShortifyPunit/badge.png?branch=master)](https://coveralls.io/repos/danrevah/ShortifyPunit/badge.png?branch=master) ![Code Quality](https://scrutinizer-ci.com/g/danrevah/ShortifyPunit/badges/quality-score.png?b=master)
> PHP Simple mocking library, **v0.1.3**

 * [Installation](#installation)
 * [Mocking](#mocking-examples)
 * [Stubbing](#stubbing)
 * [Stubbing Method Chaning](#stubbing-method-chaining)
 * [Argument Matcher](#argument-matcher)

## Installation

The following instructions outline installation using Composer. If you don't
have Composer, you can download it from [http://getcomposer.org/](http://getcomposer.org/)

 * Run either of the following commands, depending on your environment:

```
$ composer global require danrevah/shortifypunit:dev-master
$ php composer.phar global require danrevah/shortifypunit:dev-master
```

## Mocking Examples
```php
// Creating a new mock for SimpleClassForMocking
$mock = ShortifyPunit::mock('SimpleClassForMocking');

// returns null, was not stubbed yet
$mock->first_method();
```

Basic mocking example, if a function wasn't stubbed the return value will always be `NULL`.

## Stubbing
```php
// Creating a new mock for SimpleClassForMocking
$mock = ShortifyPunit::mock('SimpleClassForMocking');

// Stubbing first_method() function without arguments
ShortifyPunit::when($mock)->first_method()->returns(1);

// returns 1
$mock->first_method();

// Stubbing first_method() function with arguments
ShortifyPunit::when($mock)->first_method(1,2)->returns(2);

// still returns 1
$mock->first_method();

// returns 2
$mock->first_method(1,2);

// Stubbing callback
$mockCallback = ShortifyPunit::mock('SimpleClassForMocking');
ShortifyPunit::when($mockCallback)->first_method()->callback(function() { echo 'Foo Bar'; });
$mockCallback->first_method(); // prints 'Foo Bar'

// Stubbing throws exception
ShortifyPunit::when($mock)->second_method()->throws(new \Exception);

try {
  // Excpetion will be thrown
  $mock->second_method();
} catch(Exception $e) {
  echo 'Exception Was Caught!';
}
```
The `when` function is used to stubbing methods with specific parameters, using throw or return action.

## Stubbing Method Chanining
```php
 // Creating a new mock for SimpleClassForMocking
 $mock = ShortifyPunit::mock('SimpleClassForMocking');

  ShortifyPunit::when($mock)->first_method()->second_method(2,3)->returns(1);
  ShortifyPunit::when($mock)->first_method()->second_method(2,3,4)->returns(2);
  ShortifyPunit::when($mock)->first_method(1)->second_method(2,3,4)->returns(3);
  ShortifyPunit::when($mock)->first_method(1,2,3)->second_method(1,2)->third_method()->returns(4);
  
  $mock->first_method()->second_method(2,3); // returns 1
  $mock->first_method()->second_method(2,3,4); // returns 2
  $mock->first_method(1)->second_method(2,3,4); // returns 3
  $mock->first_method(1,2,3)->second_method(1,2)->third_method(); // return 4
```
The `when` function is also used chain methods for stubbing, using the same actions as the single function stubbing `return` `throw` or `callback`.


## Argument Matcher

ShortifyPunit allows the use of Hamcrest PHP (https://github.com/hamcrest/hamcrest-php) matcher on any argument. Hamcrest is a library of "matching functions" that, given a value, return true if that value
matches some rule.

ShortifyPunit matchers are included by default.

Examples:

```php
class Foo
{
	public function Bar($arg){
	}
}

$stub = ShortifyPunit::mock('Foo');
ShortifyPunit::when($stub)->Bar(anything())->return('FooBar');
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
