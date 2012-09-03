--TEST--
system/core/functions.php
--FILE--
<?php
include('../../../init.php');

var_dump(sys_path() === ds(SYSPATH.'/'));
var_dump(sys_path('\files') === ds(SYSPATH.'\files'));

var_dump(app_path() === ds(APPPATH.'/'));
var_dump(app_path('\files') === ds(APPPATH.'\files'));

var_dump(pub_path() === ds(PUBPATH.'/'));
var_dump(pub_path('\files') === ds(PUBPATH.'\files'));

var_dump(dat_path() === ds(DATPATH.'/'));
var_dump(dat_path('\files') === ds(DATPATH.'\files'));
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)