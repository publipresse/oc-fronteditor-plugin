<?php namespace Publipresse\FrontEditor;

use Backend;
use System\Classes\PluginBase;

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
    public function pluginDetails()
    {
        return [
            'name' => 'FrontEditor',
            'description' => 'Edit your content directly from frontend.',
            'author' => 'Publipresse',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        //
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        //
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'Publipresse\FrontEditor\Components\TinyMCE' => 'TinyMCE',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
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

    public function registerSettings()
    {
        return [
            'tinymcesettings' => [
                'label' => 'TinyMCE Settings',
                'description' => 'Manage TinyMCE Settings.',
                'icon' => 'icon-cog',
                'class' => 'Publipresse\FrontEditor\Models\TinyMCESetting',
                'category' => 'Front editor',
                'order' => 500,
                'permissions' => ['publipresse.fronteditor.access_settings']
            ]
        ];
    }

}
