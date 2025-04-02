<?php
/**
 * Plugin Name: Custom Event ID Generator
 * Description: Generates a random event ID for event custom fields.
 * Version: 1.0
 * Author: Your Name
 */

add_action('acf/save_post', 'set_random_event_ids', 20);
function set_random_event_ids($post_id)
{
    // Optionally, check if the post type is one of your custom types.
    $allowed_post_types = ['post', 'artist', 'family', 'art', 'talk'];
    if (!in_array(get_post_type($post_id), $allowed_post_types)) {
        return;
    }

    // Check if the repeater field 'event' exists for this post.
    if (!have_rows('event', $post_id)) {
        return;
    }

    // Retrieve all repeater rows.
    $rows = get_field('event', $post_id);

    // Process each row if rows exist.
    if ($rows) {
        foreach ($rows as $index => $row) {
            // If the Event ID sub-field is empty, generate a new ID.
            if (empty($row['event_id'])) {
                // Generate a random ID: either a UUID or uniqid() fallback.
                $rows[$index]['event_id'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid();
            }
        }

        // Update the repeater field with the new rows.
        update_field('event', $rows, $post_id);
    }
}
