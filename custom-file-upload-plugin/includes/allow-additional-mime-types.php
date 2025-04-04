<?php
/*
Plugin Name: Allow Additional MIME Types
Description: Enables uploading of PDFs, documents, images, archives, audio/video, text files, and more.
Version: 1.2
Author: Your Name
*/

// Debug log to verify plugin load
error_log('Allow Additional MIME Types plugin loaded.');

// Add custom MIME types to WordPress
function custom_mime_types($mimes)
{
    // Documents
    $mimes['pdf'] = 'application/pdf';
    $mimes['doc'] = 'application/msword';
    $mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    $mimes['xls'] = 'application/vnd.ms-excel';
    $mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $mimes['ppt'] = 'application/vnd.ms-powerpoint';
    $mimes['pptx'] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    $mimes['csv'] = 'text/csv';

    // Images
    $mimes['svg'] = 'image/svg+xml';
    $mimes['psd'] = 'image/vnd.adobe.photoshop';

    // Archives
    $mimes['zip'] = 'application/zip';
    $mimes['rar'] = 'application/x-rar-compressed';
    $mimes['7z'] = 'application/x-7z-compressed';

    // Audio/Video
    $mimes['mp3'] = 'audio/mpeg';
    $mimes['mp4'] = 'video/mp4';
    $mimes['mov'] = 'video/quicktime';

    // Text Files & Others
    $mimes['txt'] = 'text/plain';
    $mimes['log'] = 'text/plain';
    $mimes['md'] = 'text/markdown';
    $mimes['json'] = 'application/json';
    $mimes['xml'] = 'application/xml';
    $mimes['yml'] = 'text/yaml';
    $mimes['ini'] = 'text/plain';

    // Log the MIME types for debugging
    error_log('Updated MIME types: ' . print_r($mimes, true));
    return $mimes;
}
add_filter('upload_mimes', 'custom_mime_types');

// Additional filter to ensure file type and extension are properly detected
function allow_custom_filetypes($data, $file, $filename, $mimes)
{
    // If either file extension or MIME type is missing, re-check them.
    if (empty($data['ext']) || empty($data['type'])) {
        $filetype = wp_check_filetype($filename, $mimes);
        $data['ext'] = $filetype['ext'];
        $data['type'] = $filetype['type'];
    }
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'allow_custom_filetypes', 10, 4);
