<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Create cron event
function yourstoryz_manage_cron_event() {
    $cron_interval = get_option('yourstoryz_cron_interval', 'hourly'); // Hourly by default, wont be scheduled unless cron is enabled
    $enable_cron = get_option('yourstoryz_enable_cron', '0'); // Disabled by default

    if ($enable_cron === '1') {
        if (!wp_next_scheduled('yourstoryz_cron_event')) {
            wp_schedule_event(time(), $cron_interval, 'yourstoryz_cron_event');
        }
    } else {
        yourstoryz_disable_cron();
    }
}

add_action('wp', 'yourstoryz_manage_cron_event');
add_action('yourstoryz_cron_event', 'call_api');

// Removes cron event
function yourstoryz_disable_cron() {
    $timestamp = wp_next_scheduled('yourstoryz_cron_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'yourstoryz_cron_event');
    }
}

// Removes cron event on plugin deactivation
function yourstoryz_plugin_deactivation() {
    yourstoryz_disable_cron();
}
register_deactivation_hook(__FILE__, 'yourstoryz_plugin_deactivation');

// Update cron job settings after settings page is updated
function yourstoryz_update_cron_schedule() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'yourstoryz_plugin_settings_group') {
        if (isset($_POST['yourstoryz_plugin_nonce_field']) && check_admin_referer('yourstoryz_plugin_nonce_action', 'yourstoryz_plugin_nonce_field')) {
            yourstoryz_manage_cron_event();
        }

    }
}
add_action('admin_init', 'yourstoryz_update_cron_schedule');