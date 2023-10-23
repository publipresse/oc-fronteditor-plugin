function initEditors() {
    const wrapper = document.querySelector('#fe-wrapper');
    const header = document.querySelector('#header');
    const editors = document.querySelectorAll('[data-editable]');
    const folder = wrapper.querySelector('#fe-folder').value
    const flmngrapi = wrapper.querySelector('#fe-flmngr').value;
    const skin = wrapper.querySelector('#fe-skin').value;

    wrapper.classList.add('fe-loading');
    
    Flmngr.load({
        apiKey: flmngrapi,
        urlFileManager: '/flmngr',
        urlFiles: folder,
    });

    editors.forEach(function(el) {
        let styles = JSON.parse((el.dataset.styles)?? wrapper.querySelector('#fe-styles').value);
        let toolbar = (el.dataset.toolbar)?? wrapper.querySelector('#fe-toolbar').value;
        let plugins = ['link', 'lists', 'media', 'code', 'table', 'emoticons', 'autolink', 'image', 'accordion', 'visualblocks'];

        tinymce.init({
            target: el,
            inline: true,
            skin: skin,
            language: 'fr_FR',
            plugins: plugins,
            menubar: false,
            toolbar: toolbar,
            toolbar_sticky_offset: header.offsetHeight,
            link_context_toolbar: true,
            table_appearance_options: false,
            table_toolbar: '',
            extended_valid_elements: 'img[class=lazy|src|width|height|alt|loading=lazy]',
            preview_styles: false,
            relative_urls: true,
            object_resizing: false,
            save_enablewhendirty: false,
            style_formats: styles,
            style_formats_merge: false,
            style_formats_autohide: true,
            end_container_on_empty_block: true,
            
            setup: function(editor) {
                editor.on('init', function(e) {
                    // Add buttons loading and open options
                    wrapper.classList.remove('fe-loading');
                    wrapper.classList.add('fe-open');
                    // Remove unwanted images attributes
                    /*
                    el.querySelectorAll('img').forEach(function(el) {
                        el.removeAttribute('srcset');
                        el.removeAttribute('sizes');
                    });
                    */

                });
                // Fix issue where the toolbar does not position correctly with several editors and scroll
                editor.on('blur', function(e) {
                    // window.dispatchEvent(new Event('resize'));
                });
                
                editor.on('BeforeExecCommand', function(e) {
                    switch(e.command) {
                        // Remove previous class when switching format.
                        case 'mceToggleFormat':
                            const target = editor.selection.getNode();
                            target.className = '';
                            break;
                    }
                });
                // Prevent editor with only just images and media to allow something else
                editor.on('keydown', function (e) {
                    const toolbarCount = toolbar.split(' ').length;
                    if(toolbarCount <= 2 && (toolbar.includes('image') || toolbar.includes('media'))) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
                editor.ui.registry.addButton('image-edit', {
                    icon: 'edit-image',
                    tooltip: 'Edit',
                    onAction: function(api) { 
                        const target = editor.selection.getNode();
                        Flmngr.editAndUpload({
                            url: target.getAttribute('src'),
                            onSave: function(urlNew) {
                                // Do anything you want with the new image 
                                // target.setAttribute('src', Flmngr.getNoCacheUrl(urlNew));
                                target.setAttribute('src', urlNew);
                                target.removeAttribute('data-mce-src');
                            }
                        });

                    }
                });
                editor.ui.registry.addButton('image-upload', {
                    icon: 'upload',
                    tooltip: 'Upload',
                    onAction: function(api) { 
                        console.log(api);
                    }
                });
                editor.ui.registry.addContextToolbar('image', {
                    predicate: (node) => node.nodeName.toLowerCase() === 'img',
                    items: 'link image image-edit',
                    position: 'node',
                    scope: 'node'
                });
            },
            file_picker_callback: function(callback, value, meta) {
                Flmngr.open({
                    isMultiple: false,
                    //urlFiles: folder,
                    onFinish: function(files) {
                        //const timestamp = Date.now();
                        const url = files[0].url;
                        callback(url);
                    },
                });
            },
        });
        
    });
}

function destroyEditors() {
    const wrapper = document.querySelector('#fe-wrapper');
    wrapper.classList.add('fe-loading');
    const editors = tinymce.get();
    editors.forEach(function(editor) {
        editor.resetContent();
        editor.remove();
    });
    wrapper.classList.remove('fe-loading', 'fe-open');
}

function saveEditors() {
    const wrapper = document.querySelector('#fe-wrapper');
    wrapper.classList.add('fe-loading');
    const editors = tinymce.get();
    editors.forEach(function(editor) {
        const element = editor.getElement();
        const alias = element.dataset.editable;
        const file = element.dataset.file;
        let toolbar = (element.dataset.toolbar)?? wrapper.querySelector('#fe-toolbar').value;
        let content = editor.getContent();
        console.log(content);
        
        const toolbarCount = toolbar.split(' ').length;
        if(toolbarCount <= 2 && (toolbar.includes('image') || toolbar.includes('media'))) {
            content = content.replace(/<p>(.*?)<\/p>/, '$1');
        }
        
        oc.ajax(alias+'::onSave', {
            data: {
                file: file,
                content: content,
            },
            success: function() {
                editor.remove();
                wrapper.classList.remove('fe-loading', 'fe-open');
            }
        });
    });
}