<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . 'utils.php';

/**
 * Generates a JSON file containing all published art posts.
 * The JSON file is saved to the provided directory.
 *
 * @param string $dir The directory where the JSON file will be saved.
 * @return string|bool The JSON data on success, false on failure.
 */
function generate_art_data_json_file($dir)
{
    $args = array(
        'post_type' => 'art',
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

            if (isset($acf_fields['image_box']) && is_array($acf_fields['image_box'])) {
                if (isset($acf_fields['image_box']['portrait'])) {
                    $acf_fields['image_box']['portrait'] = filter_image_data($acf_fields['image_box']['portrait']);
                }
                if (isset($acf_fields['image_box']['landscape'])) {
                    $acf_fields['image_box']['landscape'] = filter_image_data($acf_fields['image_box']['landscape']);
                }
            }

            if (isset($acf_fields['description_box'])) {
                $acf_fields['description_box'] = convert_newlines_to_br($acf_fields['description_box']);
            }

            $slug_box = generate_slug_box(isset($acf_fields['title_box']) ? $acf_fields['title_box'] : '');

            if (isset($acf_fields['event']) && is_array($acf_fields['event'])) {
                $acf_fields['event'] = normalize_event_dates($acf_fields['event']);
            }

            $data[] = array_merge(
                array(
                    'id' => $post_id,
                    'slug_box' => $slug_box,
                ),
                $acf_fields
            );
        }
        wp_reset_postdata();
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS);

    // Ensure the provided directory exists, or try to create it.
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("Art JSON: Unable to create directory: " . $dir);
        return false;
    }
    $filename = trailingslashit($dir) . 'arts.json';
    if (file_put_contents($filename, data: $json) === false) {
        $error = error_get_last();
        error_log("Art JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }
    error_log("Art JSON file generated successfully at: " . $filename);
    return $json;
}
