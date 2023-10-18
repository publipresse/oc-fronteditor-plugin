<?php

use Publipresse\FrontEditor\Models\TinyMCESetting;

Route::post('/flmngr', function () {
    \EdSDK\FlmngrServer\FlmngrServer::flmngrRequest(
        array(
            'dirFiles' => base_path() . parse_url(\Media\Classes\MediaLibrary::url(''))['path'].'/'.TinyMCESetting::get('subfolder'),
        )
    );
});