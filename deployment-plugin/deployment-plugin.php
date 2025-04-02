<?php
/**
 * Plugin Name: Deployment Plugin
 * Description: Provides a deployment tool in the WordPress admin menu for staging and production. The production form requires a confirmation checkbox before deploying. Staging deployment now generates a partners JSON file.
 * Version: 1.0
 * Author: Sami Tekce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
require_once plugin_dir_path( __FILE__ ) . 'includes/utils.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/deployment-ui.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/deployment-processor.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/partner-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/artist-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/art-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/family-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/page-content-functions.php';

