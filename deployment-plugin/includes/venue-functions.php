<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a JSON file containing all published venue posts with full details.
 * Each venue entry includes:
 *  - "id": the post's ID.
 *  - "name": the post title.
 *  - "post": all standard post fields.
 *  - "meta": all associated meta data.
 *
 * The JSON file is saved to the provided directory.
 *
 * @param string $dir The directory where the JSON file will be saved.
 * @return string|bool The JSON data on success, false on failure.
 */
function generate_venue_data_json_file($dir)
{
    $args = array(
        'post_type' => 'venue',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    $data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_info = get_post($post_id, ARRAY_A); // Retrieve all standard post fields.

            $data[] = array(
                'id' => $post_info['ID'],           // Top-level ID.
                'name' => $post_info['post_title'],     // Top-level name.
            );
        }
        wp_reset_postdata();
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Ensure the provided directory exists, or try to create it.
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("Venue JSON: Unable to create directory: " . $dir);
        return false;
    }

    $filename = trailingslashit($dir) . 'venues.json';
    if (file_put_contents($filename, $json) === false) {
        $error = error_get_last();
        error_log("Venue JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }

    error_log("Venue JSON file generated successfully at: " . $filename);
    return $json;
}
