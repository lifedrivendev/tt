<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter the sizes array to include only allowed keys.
 */
function filter_sizes_data($sizes)
{
    $allowed_keys = array('thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048');
    $filtered = array();
    if (is_array($sizes)) {
        foreach ($allowed_keys as $key) {
            if (isset($sizes[$key])) {
                $filtered[$key] = $sizes[$key];
            }
        }
    }
    return $filtered;
}

/**
 * Filter image data to include only specific keys, including filtered sizes.
 */
function filter_image_data($images)
{
    $filtered = array();
    if (is_array($images)) {
        foreach ($images as $image) {
            if (is_array($image)) {
                $filtered[] = array(
                    'filename' => isset($image['filename']) ? $image['filename'] : '',
                    'url' => isset($image['url']) ? $image['url'] : '',
                    'alt' => isset($image['alt']) ? $image['alt'] : '',
                    'description' => isset($image['description']) ? $image['description'] : '',
                    'caption' => isset($image['caption']) ? $image['caption'] : '',
                    'menu_order' => isset($image['menu_order']) ? $image['menu_order'] : '',
                    'mime_type' => isset($image['mime_type']) ? $image['mime_type'] : '',
                    'type' => isset($image['type']) ? $image['type'] : '',
                    'subtype' => isset($image['subtype']) ? $image['subtype'] : '',
                    'width' => isset($image['width']) ? $image['width'] : '',
                    'height' => isset($image['height']) ? $image['height'] : '',
                    'sizes' => isset($image['sizes']) ? filter_sizes_data($image['sizes']) : array(),
                );
            } else {
                $filtered[] = $image;
            }
        }
    }
    return $filtered;
}

/**
 * Generate slug_box from a title_box field.
 * Returns an array with language keys.
 */
function generate_slug_box($title_box)
{
    $slug_box = array();
    if (is_array($title_box)) {
        foreach ($title_box as $lang => $title) {
            $slug_box[$lang] = sanitize_title($title);
        }
    } elseif (is_string($title_box)) {
        $slug_box['en'] = sanitize_title($title_box);
    }
    return $slug_box;
}


/**
 * Convert newline characters in a string or in each string of an array to <br> tags.
 *
 * @param mixed $text The input text, which can be a string or an array.
 * @return mixed The modified text with newline characters replaced by <br> tags.
 */
function convert_newlines_to_br($text)
{
    if (is_string($text)) {
        return str_replace(array("\r\n", "\n", "\r"), '<br>', $text);
    } elseif (is_array($text)) {
        foreach ($text as $key => $value) {
            if (is_string($value)) {
                $text[$key] = str_replace(array("\r\n", "\n", "\r"), '<br>', $value);
            }
        }
        return $text;
    }
    return $text;
}

function normalize_event_dates(array $events): array
{
    foreach ($events as &$event) {
        if (!empty($event['date'])) {
            // Check if it's an object (e.g., WP_Post)
            if (is_object($event['date']) && isset($event['date']->post_title)) {
                $event['date'] = $event['date']->post_title;
            }
            // Or if it's an array with post_title
            elseif (is_array($event['date']) && isset($event['date']['post_title'])) {
                $event['date'] = $event['date']['post_title'];
            }
        }
        // If 'date' is false or invalid, leave as-is
    }
    return $events;
}
