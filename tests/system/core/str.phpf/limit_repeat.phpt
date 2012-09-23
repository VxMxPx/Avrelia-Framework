--TEST--
system/core/str
--FILE--
<?php
include('../../../init.php');

var_dump(Str::limit_repeat('hello world!!!', '!', 1));
var_dump(Str::limit_repeat('hello        world!!!!?????', array(' ', '!', '?'), 1));
var_dump(Str::limit_repeat('hello///world|||there???are', array('/', '|', '?'), 1));
var_dump(Str::limit_repeat('hello world!', ' ', 1));
var_dump(Str::limit_repeat('', ' ', 1));
try {
	var_dump(Str::limit_repeat('A          B', '', 1));
}
catch (\Avrelia\Exception\ValueError $e) {
	var_dump($e->getMessage());
}
try {
	var_dump(Str::limit_repeat('A          B', ' ', 0));
}
catch (\Avrelia\Exception\ValueError $e) {
	var_dump($e->getMessage());
}
?>
--EXPECTF--
string(%d) "hello world!"
string(%d) "hello world!?"
string(%d) "hello/world|there?are"
string(%d) "hello world!"
string(%d) ""
string(%d) "Expected parameter is string, long at least one character."
string(%d) "Expected parameter is integer higher than one."