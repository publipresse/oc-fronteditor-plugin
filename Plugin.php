<?php namespace Publipresse\FrontEditor;

use Backend;
use Artisan;
use Flash;
use Validator;
use ValidationException;

use System\Classes\PluginBase;

use System\Controllers\Settings;

use Publipresse\FrontEditor\Console\Copy;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails() {
        return [
            'name' => 'FrontEditor',
            'description' => 'Edit your content directly from frontend.',
            'author' => 'Publipresse',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents() {
        return [
            'Publipresse\FrontEditor\Components\TinyMCE' => 'TinyMCE',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions() {
        return [
            'publipresse.fronteditor.editor' => [
                'tab' => 'Frontend Editor',
                'label' => 'Allow to use frontend editor'
            ],
            'publipresse.fronteditor.access_settings' => [
                'tab' => 'Frontend Editor',
                'label' => 'Access frontend editor settings'
            ],
        ];
    }

    public function registerSettings() {
        return [
            'generalsettings' => [
                'label' => 'General Settings',
                'description' => 'General Settings.',
                'icon' => 'icon-cog',
                'class' => 'Publipresse\FrontEditor\Models\GeneralSetting',
                'category' => 'Front editor',
                'order' => 400,
                'permissions' => ['publipresse.fronteditor.access_settings']
            ],
            'tinymcesettings' => [
                'label' => 'TinyMCE Settings',
                'description' => 'Manage TinyMCE Settings.',
                'icon' => 'icon-cog',
                'class' => 'Publipresse\FrontEditor\Models\TinyMCESetting',
                'category' => 'Front editor',
                'order' => 500,
                'permissions' => ['publipresse.fronteditor.access_settings']
            ],
        ];
    }

    public function register() {
        $this->registerConsoleCommand('fronteditor.copy', \Publipresse\FrontEditor\Console\Copy::class);
    }

    public function boot() {
        Settings::extend(function($controller) {
            $controller->addDynamicMethod('onCopyWebsiteContent', function() use ($controller) {
                // Validate fields
                $data = post('GeneralSetting');

                $rules = [
                    'copy_from' => 'required|string',
                    'copy_to' => 'required|string',
                ];
                $validator = Validator::make($data, $rules);
                $validator->setAttributeNames([
                    'copy_from' => __('Source'),
                    'copy_to' => __('Target'),
                ]);
                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                // Trigger the copy
                $ignoreDirs = array_filter(array_map('trim', explode("\n", $data['copy_ignore'])));
                $errorMessages = Copy::copyFiles($data['copy_from'], $data['copy_to'], $data['copy_overwrite'], $ignoreDirs);
                if (!empty($errorMessages)) {
                    Flash::error('There were some errors during file copy, see event log for details.');
                } else {
                    Flash::success("All files copied successfully.");
                }
            });
      });
    }

}
