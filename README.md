# Fronteditor for OctoberCMS

Edit content directly from front-end with TinyMCE. It allow to edit texts, and also images or videos.

http://octobercms.com/plugin/publipresse-fronteditor

It was inspired by the excellent [content editor](https://octobercms.com/plugin/samuell-contenteditor) plugin by Samuell, but with extra features.

## How to use

* Add the TinyMCE component to your page or layout
* Check that you have `{% framework %}` and `{% scripts %}` inside layout for working ajax requests and `{% styles %}` for additional css
* Add `{% placeholder frontEditor %}` in your layout where you want to inject fronteditor buttons
* Call the component where you want to have an editable zone.

*Minimal example:*
```twig
{% component 'TinyMCE' file="myfile" %}
```

### Properties

* **file**: Content block filename to edit. If doesnt exists it will autocreate *required*
* **tag**: The HTML tag that will wrap your content (default : div)
* **toolbar**: List of enabled tools, you can directly use list of tool name (separated by a space), or the name of a preset defined in settings. List of tools available [here](https://www.tiny.cloud/docs/tinymce/6/available-toolbar-buttons/). By default, the plugin provide a "full" preset including all available tools.
* **class**: Class for the wrapper element
* **styles**: Name of the preset of styles (defined in settings) to use for that block. More informations about styles [here](https://www.tiny.cloud/docs/tinymce/6/user-formatting-options/#style_formats)
* **shared=true**: Content will be shared between different sites in multisite mode. Useful when using media mode and want to define the same image for all your locales.

*Full example:*
```twig
{% component 'TinyMCE' file="myfile" tag="p" toolbar="h1 h2 p" styles="my-preset" class="my-class"  %}
```

### Media mode

This plugin can also be used to manage images or videos. In media mode, user can only add or edit one media in the editable block, all keyboard key are blocked, so you can't add text, press enter, etc... This is an useful feature when you want to allow your end user to have control over images, without leaving the frontend.

The cool thing is that your image will use the [|resize](https://docs.octobercms.com/3.x/markup/filter/resize.html) filter, so you can control image dimensions and your end user can't break you layout by using wrong image format.

The other cool thing is that you will be able to manage your image using the [flmngr](https://flmngr.com/) media manager. This tool have premium featured to edit images, pick images from unsplash etc... but the file management part is free.

#### Here is the extra needed properties when using media mode:

* **media**: Enable media mode for this block. Set value to "image" or "video" to activate corresponding tool, any other value activate both. If you define a toolbar manually, media setting will be ignored.
* **width**: Width of the image. If empty, image will not be resized
* **height**: Height of the image. If empty, image will not be resized
* **mode**: Resize mode 
* **loading**: Loading mode for image, by default "lazy"

*Media mode example:*
```twig
{% component 'TinyMCE' file="myfile" tag="p" media=true width=500 height=500 mode="crop" %}
```

### Extra properties

This plugin support any extra properties passed to the component. Can be useful for advanced use case.

*Extra property example:*
```twig
{% component 'TinyMCE' file="myfile" toolbar="h1 h2 p" class="my-class" prop1="myprop" prop2="myotherprop" %}
```

It will output something like this :
```html
<div class="my-class" data-prop1="myprop" data-prop2="myotherprop"></div>
```
