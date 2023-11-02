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

        $this->toolbarPresets = TinyMCESetting::get('toolbars');
        $this->stylesPresets = TinyMCESetting::get('styles');
        
        $this->flmngr = TinyMCESetting::get('flmngr');
        $this->skin = TinyMCESetting::get('skin');
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
        $this->editable = ($this->checkBypass())?? $this->property('editable');
        $this->content = null;
        
        if(!$this->file) return;
        $this->path = $this->getFilePath($this->file);
        if(!$this->path) return;

        if(Content::load($this->getTheme(), $this->path)) {
            $content = $this->renderContent($this->path);
            $this->content = Twig::parse($content);
        }
        
        if(!$this->checkEditor()) return;
        $this->toolbar = $this->property('toolbar');
        $this->styles = $this->property('styles');
        $this->mode = $this->property('mode');
        $this->width = $this->property('width');
        $this->height = $this->property('height');
        
        $toolbarCount = count(explode(' ', $this->toolbar));
        if($toolbarCount <= 2 && (str_contains($this->toolbar, 'image') || str_contains($this->toolbar, 'media'))) {
            $this->type = 'media';
        } else {
            $this->type = 'content';
        }
    }

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

    public function onClearCache() {
        Artisan::call('responsive-images:clear');
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

    public function checkBypass()
    {
        $backendUser = BackendAuth::getUser();
        return $backendUser && $backendUser->hasAccess('publipresse.fronteditor.bypass');
    }


}
