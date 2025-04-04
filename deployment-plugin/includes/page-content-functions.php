<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'utils.php';

/**
 * Generates a JSON file with the normalized raw "page-content" ACF fields
 * for all published page-content posts.
 *
 * @param string $dir Directory where the JSON file will be saved.
 * @return string|bool The JSON string on success, or false on error.
 */
function generate_page_content_json_file($dir)
{
    $args = array(
        'post_type' => 'page-content',  // Use the custom post type "page-content"
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

            // Normalize the image_box field if present.
            if (isset($acf_fields['image_box']) && is_array($acf_fields['image_box'])) {
                if (isset($acf_fields['image_box']['portrait'])) {
                    $acf_fields['image_box']['portrait'] = filter_image_data($acf_fields['image_box']['portrait']);
                }
                if (isset($acf_fields['image_box']['landscape'])) {
                    $acf_fields['image_box']['landscape'] = filter_image_data($acf_fields['image_box']['landscape']);
                }
            }

            // Convert newlines to <br> in description_box if it exists.
            if (isset($acf_fields['description_box'])) {
                $acf_fields['description_box'] = convert_newlines_to_br($acf_fields['description_box']);
            }

            // Generate a slug_box from title_box if available.
            $slug_box = generate_slug_box(isset($acf_fields['title_box']) ? $acf_fields['title_box'] : '');

            // Normalize event dates if event field exists.
            if (isset($acf_fields['event']) && is_array($acf_fields['event'])) {
                $acf_fields['event'] = normalize_event_dates($acf_fields['event']);
            }

            // Remove acf_fc_layout key from each element of flexible_page_structure.
            if (isset($acf_fields['flexible_page_structure']) && is_array($acf_fields['flexible_page_structure'])) {
                foreach ($acf_fields['flexible_page_structure'] as &$layout) {
                    if (isset($layout['acf_fc_layout'])) {
                        unset($layout['acf_fc_layout']);
                    }
                }
            }

            // Merge the slug_box and post ID into the ACF fields.
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

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS);

    // Ensure the provided directory exists.
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("Page Content JSON: Unable to create directory: " . $dir);
        return false;
    }

    // Save as page-contents.json (plural)
    $filename = trailingslashit($dir) . 'page-contents.json';
    if (file_put_contents($filename, $json) === false) {
        $error = error_get_last();
        error_log("Page Content JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }
    error_log("Page Content JSON file generated successfully at: " . $filename);
    return $json;
}
