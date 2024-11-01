<?php
function yourstoryz_enqueue_styles() {
    wp_enqueue_style('yourstoryz-frontend', plugin_dir_url(__FILE__) . 'css/yourstoryz-frontend.css');
}
add_action('wp_enqueue_scripts', 'yourstoryz_enqueue_styles');