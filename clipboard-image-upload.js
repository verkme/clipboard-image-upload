jQuery(function ($) {
    $(document).on('paste', function (e) {
        var items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                var blob = items[i].getAsFile();

                var data = new FormData();
                data.append('image', blob);
                data.append('action', 'clipboard_image_upload');
                data.append('_wpnonce', ClipboardImageUpload.nonce);

                $.ajax({
                    url: ClipboardImageUpload.ajax_url,
                    type: 'POST',
                    data: data,
                    cache: false,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if(wp.media.frame.content.get().el.querySelector('.uploader-inline-content')!==null){
                            var messageDiv = document.createElement('div');
                            messageDiv.innerHTML = ClipboardImageUpload.upload_successful_message + ': ' + response.filename;
                            messageDiv.style.color = 'green';
                            wp.media.frame.content.get().el.querySelector('.uploader-inline-content').appendChild(messageDiv);
                        }
                    },
                });

                e.preventDefault();
                return;
            }
        }
    });
    // Change Upload Files tab text
    $(document).on('DOMNodeInserted', function(e) {
        var element = e.target;

        if (element.querySelector('.uploader-inline .upload-ui h2')) {
            var h2 = element.querySelector('.uploader-inline .upload-ui h2');
            h2.textContent = ClipboardImageUpload.paste_or_drag_files_message;
        }
    });
    // Refresh library when "Library" tab clicked
    $(document).on('click', '#menu-item-browse', function() {
        if(wp.media.frame.content.get()!==null){
            var library = wp.media.frame.content.get().collection;
            if (library) {
                library.props.set({ignore: (+ new Date())}); // Force update
                library.reset(library.models); // Force library refresh
            }
        }
    });
});
