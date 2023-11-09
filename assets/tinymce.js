function initEditors() {
    const wrapper = document.querySelector('#fe-wrapper');
    const folder = wrapper.querySelector('#fe-folder').value
    const flmngrapi = wrapper.querySelector('#fe-flmngr').value;
    const skin = wrapper.querySelector('#fe-skin').value;
    const language = wrapper.querySelector('#fe-language').value;
    const editors = document.querySelectorAll('[data-editable]');
    
    // Don't display infinite loading if there is no editable content
    if(editors.length > 0) {
        wrapper.classList.add('fe-loading');
    }
    
    // Preload file manager
    Flmngr.load({
        apiKey: flmngrapi,
        urlFileManager: '/flmngr',
        urlFiles: folder,
    });

    editors.forEach(function(el) {
        const plugins = ['link', 'lists', 'media', 'code', 'table', 'emoticons', 'autolink', 'image', 'accordion', 'visualblocks'];
        const type = el.dataset.type;
        
        // If toolbar is a preset : load it
        let toolbar = el.dataset.toolbar;
        if(toolbar.trim().indexOf(' ') == -1 == true) {
            let toolbarPreset = wrapper.querySelector('#fe-toolbar-'+toolbar.split(' ')[0]);
            if(toolbarPreset) {
                toolbar = toolbarPreset.value;
            }
        }

        // If styles is a preset : load it
        let styles = el.dataset.styles;
        if(styles.trim().indexOf(' ') == -1 == true) {
            let stylesPreset = wrapper.querySelector('#fe-style-'+styles);
            if(stylesPreset) {
                styles = stylesPreset.value;
                try {
                    styles = JSON.parse(styles);
                } catch(e) {
                    console.log(e);
                }
            }
        }

        // Init TinyMCE
        tinymce.init({
            target: el,
            inline: true,
            skin: skin,
            language: language,
            plugins: plugins,
            menubar: false,
            toolbar: toolbar,
            link_context_toolbar: true,
            table_appearance_options: false,
            table_toolbar: '',
            extended_valid_elements: 'img[src|width|height|alt|style|loading=lazy],iframe[src|allowfullscreen]',
            preview_styles: false,
            relative_urls: true,
            object_resizing: false,
            image_dimensions: false,
            media_dimensions: false,
            save_enablewhendirty: false,
            paste_block_drop: true,
            style_formats: styles,
            style_formats_merge: false,
            style_formats_autohide: true,
            end_container_on_empty_block: true,
            setup: function(editor) {

                editor.on('init', function(e) {
                    // Add buttons loading and open options
                    wrapper.classList.remove('fe-loading');
                    wrapper.classList.add('fe-open');
                });
                
                editor.on('BeforeExecCommand', function(e) {
                    switch(e.command) {
                        // Remove previous class when switching format.
                        case 'mceToggleFormat':
                            var target = editor.selection.getNode();
                            target.className = '';
                            break;
                    }
                });
                
                editor.on('execCommand', function(e) {
                    switch(e.command) {
                        // Set image aspect ratio to be the same than the one defined in component
                        case 'mceUpdateImage':
                            var target = editor.selection.getNode();
                            target.style.aspectRatio = el.dataset.width + '/' + el.dataset.height;
                            break;
                    }

                });

                // Prevent editor with only just images and media to allow something else
                editor.on('keydown', function (e) {
                    if(type == 'media') {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });

                // Add paragraph
                editor.ui.registry.addButton('p', {
                    icon: 'paragraph',
                    tooltip: 'Paragraph',
                    onAction: function(api) { 
                       editor.formatter.apply('p');
                    }
                });

                // Add edit image floating button
                editor.ui.registry.addButton('image-edit', {
                    icon: 'edit-image',
                    tooltip: 'Edit',
                    onAction: function(api) { 
                        const target = editor.selection.getNode();
                        Flmngr.editAndUpload({
                            url: target.getAttribute('src'),
                            onSave: function(urlNew) {
                                target.setAttribute('src', Flmngr.getNoCacheUrl(urlNew));
                            }
                        });

                    }
                });
                
                // Add edit image button to context toolbar
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
                        const url = Flmngr.getNoCacheUrl(files[0].url);
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
    const folder = wrapper.querySelector('#fe-folder').value;
    const editors = tinymce.get();
    let isDirty = false;
    
    wrapper.classList.add('fe-loading');

    // Check if at least one editor has changed;
    editors.forEach(function(editor) {
        if(editor.isDirty() == true) {
            isDirty = true;
        }
    });

    if(isDirty === false) {
        tinyMCE.remove();
        wrapper.classList.remove('fe-loading', 'fe-open');
        return;
    }

    editors.forEach(function(editor) {
        
        const element = editor.getElement();
        const alias = element.dataset.editable;
        const file = element.dataset.file;
        let content = editor.getContent();

        // Conver images markup
        if(editor.isDirty()) {
            let dom = new DOMParser().parseFromString(content, "text/html").body.firstElementChild;
            if(dom) {
                dom.querySelectorAll('img').forEach(function(el) {
                    const width = element.dataset.width;
                    const height = element.dataset.height;
                    const mode = element.dataset.mode;
                    el.removeAttribute('style');
                    let src = new URL(el.src).pathname.replace(folder, '');
                    if(!src.includes('/resize/')) {
                        el.src = decodeURI("{{ '" + src + "'|media|resize("+width+", "+height+", '"+mode+"') }}");
                        content = dom.outerHTML;
                    }
                });
            }
        }

        // Save data
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