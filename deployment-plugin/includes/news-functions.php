<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a JSON file containing all published news posts with full ACF details.
 * Each news entry includes:
 *  - "id": the post's ID.
 *  - "slug_box": generated from the 'title_box' ACF field using generate_slug_box().
 *  - "name": the post title.
 *  - All ACF fields (retrieved via get_fields()).
 *
 * The JSON file is saved to the provided directory.
 *
 * @param string $dir The directory where the JSON file will be saved.
 * @return string|bool The JSON data on success, false on failure.
 */
function generate_news_data_json_file($dir)
{
    $args = array(
        'post_type' => 'news',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    $data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_date = get_post_datetime();
            $acf_fields = function_exists('get_fields') ? get_fields($post_id) : array();

            // Generate slug_box using the provided utility and the 'title_box' field.
            $slug_box = generate_slug_box(isset($acf_fields['title_box']) ? $acf_fields['title_box'] : '');

            $data[] = array_merge(
                array(
                    'id' => $post_id,
                    'date' => $post_date->format('Y-m-d H:i:s'),
                    'slug_box' => $slug_box,
                ),
                $acf_fields
            );
        }
        wp_reset_postdata();
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Ensure the provided directory exists, or try to create it.
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("News JSON: Unable to create directory: " . $dir);
        return false;
    }

    $filename = trailingslashit($dir) . 'news.json';
    if (file_put_contents($filename, $json) === false) {
        $error = error_get_last();
        error_log("News JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }

    error_log("News JSON file generated successfully at: " . $filename);
    return $json;
}
