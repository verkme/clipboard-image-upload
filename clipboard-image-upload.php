<?php
/*
Plugin Name: Clipboard Image Upload
Plugin URI: https://verkme.com
Description: This is a plugin that allows users to easily upload images from their clipboard.
Version: 1.0.0
Author: VERKME
Author URI: https://verkme.com
License: GPLv2
Text Domain: clipboard-image-upload
Domain Path: /languages
*/

add_action('admin_enqueue_scripts', 'clipboard_image_upload_enqueue_scripts');
add_action('wp_ajax_clipboard_image_upload', 'clipboard_image_upload_handle');
add_action( 'plugins_loaded', 'clipboard_image_upload_load_textdomain' );

function clipboard_image_upload_enqueue_scripts() {
    // Only load our script on the post editing screen
    if (get_current_screen()->base !== 'post') {
        return;
    }

    // Enqueue our script
    wp_enqueue_script(
        'clipboard-image-upload',
        plugins_url('clipboard-image-upload.js', __FILE__),
        ['jquery'],
        filemtime(plugin_dir_path(__FILE__) . 'clipboard-image-upload.js'),
        true
    );

    wp_localize_script('clipboard-image-upload', 'ClipboardImageUpload', [
        'nonce' => wp_create_nonce('clipboard-image-upload'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'upload_successful_message' => __('File successfully uploaded', 'clipboard-image-upload'),
        'paste_or_drag_files_message' => __('Paste files from clipboard or drag files here', 'clipboard-image-upload'),
    ]);

    add_action('admin_footer', 'clipboard_image_upload_change_text');
}

function clipboard_image_upload_change_text() {
    echo "<script>
    jQuery(document).ready(function() {
        var uploader = jQuery('.media-modal-content .uploader-inline .instructions span');
        if (uploader.length) {
            uploader.text(ClipboardImageUpload.paste_or_drag_files_message);
        }
    });
    </script>";
}

function clipboard_image_upload_handle() {
    check_ajax_referer('clipboard-image-upload');

    $file = $_FILES['image'];
    $file['name'] = 'image-'.time().'.png';

    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $filename = $movefile['file'];

        $wp_filetype = wp_check_filetype(basename($filename), null);

        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $filename);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
        wp_update_attachment_metadata($attach_id, $attach_data);

        echo json_encode([
            'id' => $attach_id,
            'filename' => basename($filename),
        ]);
    }
    wp_die();
}

function clipboard_image_upload_load_textdomain() {
    load_plugin_textdomain( 'clipboard-image-upload', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
