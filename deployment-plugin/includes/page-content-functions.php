<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a JSON file with the raw "page-content" ACF fields for all published page-content posts.
 *
 * @param string $dir Directory where the JSON file will be saved.
 * @return string|bool The JSON string or false on error.
 */
function generate_page_content_json_file($dir)
{
    $args = array(
        'post_type' => 'page-content', // Use the custom post type "page-content"
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    $query = new WP_Query($args);
    $data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $acf_fields = function_exists('get_fields') ? get_fields($post_id) : array();

            // Store the entire ACF array so you get all the custom data
            $data[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'slug' => get_post_field('post_name', $post_id),
                'acf' => $acf_fields
            );
        }
        wp_reset_postdata();
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Ensure directory exists
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("Page Content JSON: Failed to create directory: " . $dir);
        return false;
    }

    $filename = trailingslashit($dir) . 'page-content.json';
    if (file_put_contents($filename, $json) === false) {
        $error = error_get_last();
        error_log("Page Content JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }

    error_log("Page Content JSON file created: $filename");
    return $json;
}
