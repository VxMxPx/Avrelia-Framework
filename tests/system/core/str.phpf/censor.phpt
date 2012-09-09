--TEST--
system/core/str
--FILE--
<?php
include('../../../init.php');

var_dump(Str::censor('Hello world!', 'world', '*', 2));
var_dump(Str::censor('Hello world!', 'world', '+', array(1, 2)));
var_dump(Str::censor(
	array('January, February, March, April, May, June, July, August, September, October, November, December',
		  'JANUARY, FEBRUARY, MARCH, APRIL, MAY, JUNE, JULY, AUGUST, SEPTEMBER, OCTOBER, NOVEMBER, DECEMBER',
		  'january, february, march, april, may, june, july, august, september, october, november, december'), 
	array('january', 'july'),
	'*', array(2, 1)));
var_dump(Str::censor('hello world!', 'hell'));
var_dump(Str::censor('Hello!', 'hello', '*', array(1, 8)));
var_dump(Str::censor('Hello!', 'hello', 'hello'));
?>
--EXPECTF--
string(%d) "Hello wo***!"
string(%d) "Hello w++ld!"
array(3) {
  [0]=>
  string(96) "Ja****y, February, March, April, May, June, Ju*y, August, September, October, November, December"
  [1]=>
  string(96) "JA****Y, FEBRUARY, MARCH, APRIL, MAY, JUNE, JU*Y, AUGUST, SEPTEMBER, OCTOBER, NOVEMBER, DECEMBER"
  [2]=>
  string(96) "ja****y, february, march, april, may, june, ju*y, august, september, october, november, december"
}
string(12) "hello world!"
string(6) "*****!"
string(18) "Hehellohellohello!"