<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process deployment for a given environment.
 *
 * This function generates partner, artist, art, venue, family, page content, and website options JSON files
 * and writes them to the corresponding directory.
 *
 * @param string $env The environment type ('staging' or 'prod').
 */
function process_deployment($env)
{
    $upload_dir = wp_upload_dir();
    $base_upload_path = $upload_dir['basedir'];
    $target_dir = $base_upload_path . '/shared_data/' . $env . '/';

    if (!file_exists($target_dir) && !wp_mkdir_p($target_dir)) {
        $msg = "Unable to create directory: " . $target_dir;
        error_log($msg);
        echo "<div class='error'><p>" . esc_html($msg) . "</p></div>";
        return;
    }

    // Generate partner JSON file.
    $partner_json = generate_partner_data_json_file($target_dir);
    if (false === $partner_json) {
        error_log("Partner JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Partner JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Partner JSON file generated successfully during {$env} deployment.");
    }

    // Generate artist JSON file.
    $artist_json = generate_artist_data_json_file($target_dir);
    if (false === $artist_json) {
        error_log("Artist JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Artist JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Artist JSON file generated successfully during {$env} deployment.");
    }

    // Generate art JSON file.
    $art_json = generate_art_data_json_file($target_dir);
    if (false === $art_json) {
        error_log("Art JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Art JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Art JSON file generated successfully during {$env} deployment.");
    }

    // Generate venue JSON file.
    $venue_json = generate_venue_data_json_file($target_dir);
    if (false === $venue_json) {
        error_log("Venue JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Venue JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Venue JSON file generated successfully during {$env} deployment.");
    }

    // Generate family JSON file.
    $family_json = generate_family_data_json_file($target_dir);
    if (false === $family_json) {
        error_log("Family JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Family JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Family JSON file generated successfully during {$env} deployment.");
    }

    // Generate page content JSON file.
    $page_contents_json = generate_page_content_json_file($target_dir);
    if (false === $page_contents_json) {
        error_log("Page Contents JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Page Contents JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Page Contents JSON file generated successfully during {$env} deployment.");
    }

    // Generate website options JSON file.
    $website_options_json = generate_website_options_json_file($target_dir);
    if (false === $website_options_json) {
        error_log("Website Options JSON file generation failed during {$env} deployment.");
        echo "<div class='error'><p>Website Options JSON file generation failed during {$env} deployment.</p></div>";
    } else {
        error_log("Website Options JSON file generated successfully during {$env} deployment.");
    }

    echo "<div class='updated'><p>" . ucfirst($env) . " deployment completed successfully and partner, artist, art, venue, family, page content & website options data generated.</p></div>";
}

/**
 * Process staging deployment.
 */
function process_staging_deployment()
{
    process_deployment('staging');
}

/**
 * Process production deployment.
 */
function process_production_deployment()
{
    process_deployment('prod');
}
