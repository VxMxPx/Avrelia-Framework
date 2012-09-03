--TEST--
system/core/functions.php
--FILE--
<?php
include('../../../init.php');

dump(0, false);
dump(1.2, false);
dump('Hello kitty!', false);
dump(-1, false);
dump(false, false);
dump(array('-1', -2, false), false);
dump(dump(true, false, true), false);
echo dump(42, false, true);
dump(-12, true);
echo 'NOT VISIBLE';
?>
--EXPECT--
integer: 0

double: 1.2

string[12]: Hello kitty!

integer: -1

boolean: false

array: Array
(
    [0] => -1
    [1] => -2
    [2] => 
)


string[15]: 
boolean: true


integer: 42

integer: -12