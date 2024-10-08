<?php

namespace Publipresse\FrontEditor\Models;

use Model;
use Site;

class GeneralSetting extends \System\Models\SettingModel {
    use \October\Rain\Database\Traits\Validation;

    public $settingsCode = 'publipresse_fronteditor_generalsettings';
    public $settingsFields = 'fields.yaml';

    public $rules = [
        
    ];

    public function getCopyFromOptions() {
        return $this->getCopyOptions();
    }

    public function getCopyToOptions() {
        return $this->getCopyOptions();
    }

    public function getCopyOptions() {
        $sites = Site::listSites();
        $options = collect();
        foreach($sites as $site) {
            $options->put($site->id, (($site->group)? '['.$site->group->name.'] ' : '') . $site->name);
        }
        return $options->toArray();
    }

}
