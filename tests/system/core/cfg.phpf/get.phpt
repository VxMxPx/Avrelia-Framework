--TEST--
system/core/cfg
--FILE--
<?php
include('../../../init.php');

Cfg::overwrite('system/test', 'test');
dump(Cfg::get('system/test'));
?>
--EXPECTF--
string[4]: test