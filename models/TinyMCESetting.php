<?php

namespace Publipresse\FrontEditor\Models;

use Model;

class TinyMCESetting extends \System\Models\SettingModel {
    use \October\Rain\Database\Traits\Validation;

    public $settingsCode = 'publipresse_fronteditor_tinymcesettings';
    public $settingsFields = 'fields.yaml';

    public $rules = [
        'skin' => 'required',
        'language' => 'required',
        'toolbar_mode' => 'required',
        'flmngr' => 'required',
    ];
}
