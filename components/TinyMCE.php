<?php namespace Publipresse\FrontEditor\Components;

use Artisan;
use BackendAuth;
use File;
use Site;
use Twig;
use Cms\Classes\ComponentBase;
use Cms\Classes\Content;
use Cms\Classes\Page;

use Publipresse\FrontEditor\Models\TinyMCESetting;

use \DOMDocument;

/**
 * TinyMCE Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class TinyMCE extends ComponentBase
{
    public $file;
    public $language;
    public $editable;
    public $content;
    public $toolbar;
    public $toolbarPresets;
    public $toolbarMode;
    public $tag;
    public $class;
    public $folder;
    public $styles;
    public $stylesPresets;
    public $foreColors;
    public $backColors;
    public $flmngr;
    public $skin;
    public $extras;

    public $media;
    public $mode;
    public $width;
    public $height;

    public $renderCount;

    public function componentDetails() {
        return [
            'name' => 'TinyMCE',
            'description' => 'Edit frontend content using TinyMCE'
        ];
    }

    public function onRun() {
        if(!$this->checkEditor()) return;

        // Load TinyMCE
        $this->addJs('assets/vendor/tinymce/tinymce.min.js', ['defer' => true]);
        $this->addJs('assets/vendor/tinymce/langs/fr_FR.js', ['defer' => true]);

        // Load Flmngr
        $this->addJs('https://unpkg.com/flmngr', ['defer' => true]);

        // Load custom assets
        $this->addCss('assets/fronteditor.css');

        // Define global variables
        $this->language = TinyMCESetting::get('language');
        $this->toolbarPresets = TinyMCESetting::get('toolbars');
        $this->toolbarMode = TinyMCESetting::get('toolbar_mode');
        $this->stylesPresets = $this->array_filter_recursive(TinyMCESetting::get('styles'));
        
        $this->foreColors = TinyMCESetting::get('forecolors');
        $this->backColors = TinyMCESetting::get('backcolors');
        $this->flmngr = TinyMCESetting::get('flmngr');
        $this->skin = TinyMCESetting::get('skin');
        $this->folder = parse_url(\Media\Classes\MediaLibrary::url(''))['path'];

    }
    
    public function onRender() {
        
        $properties = $this->getProperties();
        
        // Define all default tags
        $this->tag = $this->property('tag'); unset($properties['tag']);
        $this->file = $this->getFilePath($this->property('file')); unset($properties['file']);
        $this->class = $this->property('class'); unset($properties['class']);
        $this->content = null;
        
        // Get file path and load content
        if(!$this->file) return;

        if(Content::load($this->getTheme(), $this->file)) {
            $content = $this->renderContent($this->file);
            $this->content = Twig::parse($content);
        }
        
        // If user have access to the editor
        if($this->checkEditor()) {

            // Render script and buttons only first time
            $this->renderCount += 1;
            if($this->renderCount == 1) { 
                $this->renderPartial('@buttons.htm');
                $this->renderPartial('@scripts.htm');
            }

            // Define all useful variables
            $this->editable = true;
            $this->toolbar = $this->property('toolbar'); unset($properties['toolbar']);
            $this->styles = $this->property('styles'); unset($properties['styles']);
            $this->media = $this->property('media'); unset($properties['media']);
            $this->width = $this->property('width'); unset($properties['width']);
            $this->height = $this->property('height'); unset($properties['height']);
            $this->mode = $this->property('mode'); unset($properties['mode']);
        };

        // Reset properties
        foreach($this->getProperties() as $key => $property) {
            $this->setProperty($key, null);
        }

        // Add all others properties
        $this->extras = [];
        foreach($properties as $key => $property) {
            $this->extras[$key] = $property;
        }
        
    }

    // Save data to content
    public function onSave() {
        if(!$this->checkEditor()) return;

        $filePath = post('file');

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

    // Update page
    public function onUpdatePage() {
        $currentPage = Page::load($this->getTheme(), $this->page->baseFileName);
        $currentPage->save();
    }

    // Generate filepath as follow (Site group name / Site name / File name)
    // If shared = true, site name is ommitted.
    public function getFilePath($filename) {

        $activeSite = Site::getSiteFromContext();
        $filename = explode('.', $filename);
        $filename[1] = isset($filename[1])? $filename[1] : 'htm';

        $filepath = '';
        if($activeSite->group) {
            $filepath .= $activeSite->group->code.'/';
        }
        
        if($this->property('shared') !== true) {
            $filepath .= $activeSite->code.'/';
        }
        
        $filepath .= $filename[0].'.'.$filename[1];
        return $filepath;
    }

    // Check editor access
    public function checkEditor() {
        $backendUser = BackendAuth::getUser();
        return $backendUser && $backendUser->hasAccess('publipresse.fronteditor.editor');
    }

    public function array_filter_recursive($input) { 
        if(is_null($input)) return;
        foreach ($input as &$value) { 
            if (is_array($value)) { 
                $value = $this->array_filter_recursive($value); 
            } 
        } 
        return array_filter($input); 
    } 

}
