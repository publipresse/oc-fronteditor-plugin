<?php namespace Publipresse\FrontEditor\Components;

use BackendAuth;
use File;
use Site;
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
    public $toolbar;
    public $tag;
    public $class;
    public $folder;
    public $styles;
    public $flmngr;
    public $skin;
    public $access;

    public $renderCount;

    public function componentDetails()
    {
        return [
            'name' => 'TinyMCE Component',
            'description' => 'No description provided yet...'
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

        $this->addCss('assets/fronteditor.css');
        $this->addJs('assets/vendor/tinymce/tinymce.min.js', ['defer' => true]);
        $this->addJs('assets/vendor/tinymce/langs/fr_FR.js', ['defer' => true]);
        $this->addJs('assets/vendor/flmngr/flmngr.js', ['defer' => true]);
        $this->addJs('assets/tinymce.js', ['defer' => true]);

        $this->toolbar = TinyMCESetting::get('toolbar');
        $this->flmngr = TinyMCESetting::get('flmngr');
        $this->skin = TinyMCESetting::get('skin');
        $this->styles = json_encode(TinyMCESetting::get('styles'));
        $this->folder = parse_url(\Media\Classes\MediaLibrary::url(''))['path'].'/'.TinyMCESetting::get('subfolder').TinyMCESetting::get('folder');
        $this->access = true;
    }

    public function onRender() 
    {
        $this->renderCount = $this->page['renderCount'] += 1;

        if($this->renderCount == 1) $this->renderPartial('@buttons.htm');
        
        $this->tag = $this->property('tag');
        $this->file = $this->property('file');
        $this->class = $this->property('class');
        
        if(!$this->file) return;
        $this->path = $this->getFilePath($this->file);
        if(!$this->path) return;


        if(!$this->checkEditor()) return;
        $this->toolbar = $this->property('toolbar');
        $this->styles = ($this->property('styles'))? json_encode($this->property('styles')) : null;
    }

    public function onSave()
    {
        if(!$this->checkEditor()) return;

        $fileName = post('file');
        $filePath = $this->getFilePath($fileName);

        if ($load = Content::load($this->getTheme(), $filePath)) {
            $fileContent = $load; // load existed content file
        } else {
            $fileContent = Content::inTheme($this->getTheme()); // create new content file if not exists
        }

        $fileContent->fill([
            'fileName' => $filePath,
            'markup' => post('content')
        ]);

        $fileContent->save();
    }

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

    public function checkEditor()
    {
        $backendUser = BackendAuth::getUser();
        return $backendUser && $backendUser->hasAccess('publipresse.fronteditor.editor');
    }
}
