--TEST--
system/core/functions.php
--FILE--
<?php
include('../../../init.php');

avrelia_error_handler(E_COMPILE_WARNING, 'Some silly warn', 'file.4', 65);
avrelia_error_handler(E_ERROR, 'Error here', 'file.2', 42);
?>
--EXPECTF--
Compile Warning:
Some silly warn
%s %d
----------------------------------------
Error:
Error here

%s %d
----------------------------------------