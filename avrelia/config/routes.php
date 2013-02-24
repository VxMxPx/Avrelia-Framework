<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

Route::on('@404', function() {

    Http::status_404_not_found();
    out('Wooops, we have 404 on: ' . Input::get_path_info());

});

Route::on('@OFFLINE', function() {
    
    Http::status_503_service_unavailable();
    out('Page is offline!');

});