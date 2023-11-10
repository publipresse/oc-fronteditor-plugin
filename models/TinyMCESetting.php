<?php

namespace Publipresse\FrontEditor\Models;

use Model;

class TinyMCESetting extends \System\Models\SettingModel
{
    use \October\Rain\Database\Traits\Multisite;

    public $settingsCode = 'publipresse_fronteditor_tinymcesettings';
    public $settingsFields = 'fields.yaml';

    protected $propagatableSync = true;
    protected $propagatable = [
        'skin',
        'language',
        'flmngr',
        'toolbars',
        'styles',
        'forecolors',
        'backcolors',
    ];
}
