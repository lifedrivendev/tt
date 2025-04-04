<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a JSON file containing all published partner posts.
 * The JSON file is saved to the provided directory.
 *
 * @param string $dir The directory where the JSON file will be saved.
 * @return string|bool The JSON data on success, false on failure.
 */
function generate_partner_data_json_file($dir)
{
    $args = array(
        'post_type' => 'partner',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    $data = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $data[] = array(
                'title' => get_the_title(),
                'partner_url' => get_post_meta($post_id, 'partner_url', true),
                'partner_level' => get_post_meta($post_id, 'partner_level', true),
            );
        }
        wp_reset_postdata();
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Ensure the provided directory exists, or try to create it.
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("Partner JSON: Unable to create directory: " . $dir);
        return false;
    }
    $filename = trailingslashit($dir) . 'partners.json';
    if (file_put_contents($filename, $json) === false) {
        $error = error_get_last();
        error_log("Partner JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }
    error_log("Partner JSON file generated successfully at: " . $filename);
    return $json;
}
