--TEST--
system/core/functions
--FILE--
<?php
include('../../../init.php');

var_dump(ds('/home/', 'base', 'dir/2'));
var_dump(ds('\dir///name'));
var_dump(ds('c:\system32\drivers\driver.dll'));
var_dump(ds('///'));
var_dump(ds(''));
var_dump(ds('\/'));
var_dump(ds('/home/'));
var_dump(ds('\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\'));
?>
--EXPECTF--
string(%d) "/home/base/dir/2"
string(%d) "/dir/name"
string(%d) "c:/system32/drivers/driver.dll"
string(%d) "/"
NULL
string(%d) "/"
string(%d) "/home/"
string(%d) "/"