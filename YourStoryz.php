<?php
/*
Plugin Name: YourStoryz
Description: A plugin to create posts with data from YourStoryz API.
Version: 1.3.2
Author: Concept7
Author URI: https://concept7.nl/
Developer: Nathan Kruit
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include all files
include_once plugin_dir_path(__FILE__) . 'includes/parsedown.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
include_once plugin_dir_path(__FILE__) . 'includes/cron-job.php';
include_once plugin_dir_path(__FILE__) . 'includes/api-functions.php';
include_once plugin_dir_path(__FILE__) . 'includes/settings.php';
include_once plugin_dir_path(__FILE__) . 'includes/scripts.php';
include_once plugin_dir_path(__FILE__) . 'includes/styles.php';