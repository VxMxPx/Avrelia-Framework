--TEST--
system/core/functions
--FILE--
<?php
include('../../../init.php');

echo dump_r(0);
echo dump_r(1.2);
echo dump_r('Hello kitty!');
echo dump_r(-1);
echo dump_r(false);
echo dump_r(array('-1', -2, false));
echo dump_r(dump_r(true));
echo dump_r(42);
dump(-12);
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