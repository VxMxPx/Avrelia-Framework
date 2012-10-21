<?php if (!defined('AVRELIA')) die('Access is denied!');

// Run index action if exists
Route::on('@INDEX', 'home->index');

// On 404
Route::on('@404', 'home->error_404');