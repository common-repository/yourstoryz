<?php
function yourstoryz_enqueue_admin_scripts() {
    wp_enqueue_script(
        'yourstoryz-admin-js',
        esc_url(plugin_dir_url(__FILE__) . 'js/yourstoryz-admin.js'),
        [],
        null,
        true
    );
}

add_action('admin_enqueue_scripts', 'yourstoryz_enqueue_admin_scripts');