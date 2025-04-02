<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates a JSON file containing website options retrieved using ACF.
 * The JSON file is saved to the provided directory.
 *
 * @param string $dir The directory where the JSON file will be saved.
 * @return string|bool The JSON data on success, false on failure.
 */
function generate_website_options_json_file($dir)
{
    $options = get_website_options();
    if (is_wp_error($options)) {
        error_log("Website Options JSON: " . $options->get_error_message());
        return false;
    }

    $json = json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Ensure the provided directory exists, or try to create it.
    if (!file_exists($dir) && !wp_mkdir_p($dir)) {
        error_log("Website Options JSON: Unable to create directory: " . $dir);
        return false;
    }

    $filename = trailingslashit($dir) . 'website-options.json';
    if (file_put_contents($filename, $json) === false) {
        $error = error_get_last();
        error_log("Website Options JSON: Failed to write file ($filename): " . print_r($error, true));
        return false;
    }

    error_log("Website Options JSON file generated successfully at: " . $filename);
    return $json;
}


/**
 * Retrieve website options using ACF.
 *
 * @return array|WP_Error Array of options or a WP_Error if ACF is missing.
 */
function get_website_options()
{
    // Ensure that ACF is loaded.
    if (!function_exists('get_field')) {
        return new WP_Error('acf_missing', 'ACF plugin is not active or not loaded yet.');
    }

    // Retrieve option fields.
    $main_date = get_field('main_date', 'option');
    $front_page_artists_list = get_field('front_page_artists_list', 'option');
    $banners = get_field('banners', 'option');
    $main_menu_item = get_field('main_menu_item', 'option');

    // Clean up front_page_artists_list: return only IDs.
    if (is_array($front_page_artists_list)) {
        $front_page_artists_list = array_map(function ($item) {
            if (is_object($item) && isset($item->ID)) {
                return $item->ID;
            } elseif (is_array($item) && isset($item['ID'])) {
                return $item['ID'];
            }
            return $item;
        }, $front_page_artists_list);
    }

    // Transform main_menu_item if it's an array.
    if (is_array($main_menu_item)) {
        foreach ($main_menu_item as &$menu_item) {
            $menu_item = transform_menu_item($menu_item);
        }
        unset($menu_item);
    }

    // Return the website options.
    return array(
        'main_date' => $main_date,
        'front_page_artists_list' => $front_page_artists_list,
        'banners' => $banners,
        'main_menu_item' => $main_menu_item,
    );
}

/**
 * Transform a single menu item.
 *
 * - If 'connected_page' is an array, extract the ID of the first element.
 * - Adds a new 'slug_box' field based on the 'en' and 'fi' values.
 * - Recursively processes any 'child_menu_item' entries.
 *
 * @param array $menu_item The menu item to transform.
 * @return array The transformed menu item.
 */
function transform_menu_item($menu_item)
{
    // Process connected_page: if it's an array, extract the first item's ID.
    if (isset($menu_item['connected_page'])) {
        if (is_array($menu_item['connected_page'])) {
            if (!empty($menu_item['connected_page'])) {
                $first_item = reset($menu_item['connected_page']);
                if (is_object($first_item) && isset($first_item->ID)) {
                    $menu_item['connected_page'] = $first_item->ID;
                } elseif (is_array($first_item) && isset($first_item['ID'])) {
                    $menu_item['connected_page'] = $first_item['ID'];
                } else {
                    $menu_item['connected_page'] = '';
                }
            } else {
                $menu_item['connected_page'] = '';
            }
        }
        // If connected_page is not an array, leave it as is.
    }

    // Create slug_box using the provided generate_slug_box() utility.
    $menu_item['slug_box'] = generate_slug_box(array(
        'en' => isset($menu_item['en']) ? $menu_item['en'] : '',
        'fi' => isset($menu_item['fi']) ? $menu_item['fi'] : '',
    ));

    // Process child_menu_item recursively, if it exists.
    if (isset($menu_item['child_menu_item']) && is_array($menu_item['child_menu_item'])) {
        foreach ($menu_item['child_menu_item'] as &$child_item) {
            $child_item = transform_menu_item($child_item);
        }
        unset($child_item);
    }

    return $menu_item;
}
