--TEST--
system/core/cfg
--FILE--
<?php
include('../../../init.php');

Cfg::overwrite('core/test', 'test');
echo dump_r(Cfg::get('core/test'));
?>
--EXPECTF--
string[4]: test