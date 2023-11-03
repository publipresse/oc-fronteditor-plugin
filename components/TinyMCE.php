<?php namespace Publipresse\FrontEditor\Components;

use Artisan;
use BackendAuth;
use File;
use Site;
use Twig;
use Cms\Classes\ComponentBase;
use Cms\Classes\Content;
use System\Classes\SiteManager;

use Publipresse\FrontEditor\Models\TinyMCESetting;

/**
 * TinyMCE Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class TinyMCE extends ComponentBase
{
    public $path;
    public $file;
    public $language;
    public $content;
    public $toolbar;
    public $toolbarPresets;
    public $tag;
    public $type;
    public $class;
    public $folder;
    public $styles;
    public $stylesPresets;
    public $flmngr;
    public $skin;
    public $access;
    public $editable;
    public $extras;

    public $width;
    public $height;
    public $mode;

    public $renderCount;

    public function componentDetails()
    {
        return [
            'name' => 'TinyMCE',
            'description' => 'Edit frontend content using TinyMCE'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        if(!$this->checkEditor()) return;

        // Load TinyMCE
        $this->addJs('assets/vendor/tinymce/tinymce.min.js', ['defer' => true]);
        $this->addJs('assets/vendor/tinymce/langs/fr_FR.js', ['defer' => true]);

        // Load Flmngr
        $this->addJs('https://unpkg.com/flmngr', ['defer' => true]);

        // Load custom assets
        $this->addCss('assets/fronteditor.css');
        $this->addJs('assets/tinymce.js', ['defer' => true]);

        // Define global variables
        $this->language = TinyMCESetting::get('language');
        $this->toolbarPresets = TinyMCESetting::get('toolbars');
        $this->stylesPresets = TinyMCESetting::get('styles');
        $this->flmngr = TinyMCESetting::get('flmngr');
        $this->skin = TinyMCESetting::get('skin');
        $this->folder = parse_url(\Media\Classes\MediaLibrary::url(''))['path'].'/'.TinyMCESetting::get('subfolder').TinyMCESetting::get('folder');
        $this->access = true;
    }

    public function onRender() 
    {
        $properties = $this->getProperties();

        // Render buttons first time only
        $this->renderCount = $this->page['renderCount'] += 1;
        if($this->renderCount == 1) $this->renderPartial('@buttons.htm');
        
        // Define all default tags
        $this->tag = $this->property('tag'); unset($properties['tag']);
        $this->file = $this->property('file'); unset($properties['file']);
        $this->class = $this->property('class'); unset($properties['class']);
        $this->editable = ($this->checkBypass())?? $this->property('editable');
        $this->content = null;

        // Get file path and load content
        if(!$this->file) return;
        $this->path = $this->getFilePath($this->file);
        if(!$this->path) return;

        if(Content::load($this->getTheme(), $this->path)) {
            $content = $this->renderContent($this->path);
            $this->content = Twig::parse($content);
        }
        
        // If admin, get all useful variables
        if(!$this->checkEditor()) return;
        $this->toolbar = $this->property('toolbar'); unset($properties['toolbar']);
        $this->styles = $this->property('styles'); unset($properties['styles']);
        $this->mode = $this->property('mode'); unset($properties['mode']);
        $this->width = $this->property('width'); unset($properties['width']);
        $this->height = $this->property('height'); unset($properties['height']);
        
        // Add all others properties
        foreach($properties as $key => $property) {
            $this->extras[$key] = $property;
        }

        // Media mode detection
        $this->type = '';
        $toolbarCount = count(explode(' ', $this->toolbar));
        if($toolbarCount <= 2 && (str_contains($this->toolbar, 'image') || str_contains($this->toolbar, 'media'))) {
            $this->type = 'media';
        }

    }

    // Save data to content
    public function onSave()
    {
        if(!$this->checkEditor()) return;
        $fileName = post('file');
        $filePath = $this->getFilePath($fileName);

        if ($load = Content::load($this->getTheme(), $filePath)) {
            // load existed content file
            $fileContent = $load;
        } else {
            // create new content file if not exists
            $fileContent = Content::inTheme($this->getTheme());
        }

        $fileContent->fill([
            'fileName' => $filePath,
            'markup' => post('content')
        ]);

        $fileContent->save();
    }

    // Clear image cache
    public function onClearCache() {
        Artisan::call('responsive-images:clear');
    }

    // Generate filepath as follow (Site group name / Site name / File name)
    public function getFilePath($filename) 
    {
        $activeSite = Site::getSiteFromContext();
        $filename = explode('.', $filename);
        $filename[1] = isset($filename[1])? $filename[1] : 'htm';
        if($activeSite->group) {
            $filepath = $activeSite->group->code.'/'.$activeSite->code.'/'.$filename[0].'.'.$filename[1];
        } else {
            $filepath = $activeSite->code.'/'.$filename[0].'.'.$filename[1];
        }
        return $filepath;
    }

    // Check editor access
    public function checkEditor()
    {
        $backendUser = BackendAuth::getUser();
        return $backendUser && $backendUser->hasAccess('publipresse.fronteditor.editor');
    }

    // Check editor bypass access
    public function checkBypass()
    {
        $backendUser = BackendAuth::getUser();
        return $backendUser && $backendUser->hasAccess('publipresse.fronteditor.bypass');
    }


}
