<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_theme_support('post-thumbnails');

add_action('admin_init', 'yourstoryz_plugin_register_settings');
// Create settings page and fields
function yourstoryz_plugin_register_settings() {
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_autoplay');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_filter_on_department');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_plugin_change_token');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_cron_interval');
    register_setting('yourstoryz_plugin_settings_group', 'yourstoryz_enable_cron');
    
    add_settings_field(
        'yourstoryz_plugin_filter_on_department',
        'Filter on department',
        'yourstoryz_plugin_filter_on_department_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_section(
        'yourstoryz_plugin_settings_section',
        'Plugin settings',
        '',
        'yourstoryz-plugin-settings'
    );

    add_settings_field(
        'yourstoryz_plugin_autoplay',
        'Autoplay videos',
        'yourstoryz_plugin_autoplay_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_enable_cron',
        'Enable automatic post updates',
        'yourstoryz_enable_cron_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );

    add_settings_field(
        'yourstoryz_cron_interval',
        'Automatically get posts timer',
        'yourstoryz_cron_interval_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );
    
    add_settings_field(
        'yourstoryz_plugin_change_token',
        'Change API token',
        'yourstoryz_plugin_change_token_callback',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_section'
    );
}

// Connect to api and find available departments
function yourstoryz_fetch_departments() {
    $token = get_option('yourstoryz_plugin_change_token');
    $api_url = 'https://dashboard.yourstoryz.com/api/v1/departments';

    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    return is_array($data) ? $data : [];
}

// Dropdown to select a department from api data
function yourstoryz_plugin_filter_on_department_callback() {
    $departments = yourstoryz_fetch_departments();
    $selected_department = get_option('yourstoryz_plugin_filter_on_department');
    ?>
    <select name="yourstoryz_plugin_filter_on_department" id="yourstoryz_plugin_filter_on_department">
        <option value="" <?php selected('', $selected_department); ?>>Main company</option>
        <?php foreach ($departments as $department): ?>
            <option value="<?php echo esc_attr($department->id); ?>" <?php selected($selected_department, $department->id); ?>>
                <?php echo esc_html($department->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">Select the department to filter posts from. Choosing 'Main company' will not apply any filter.</p>
    <?php
}

// Store autoplay value in options.php (on/off switch)
function yourstoryz_plugin_autoplay_callback() {
    // Haal de optie op, standaard is autoplay uitgeschakeld ('0')
    $autoplay = esc_attr(get_option('yourstoryz_plugin_autoplay', '0'));
    ?>
    <label for="yourstoryz_plugin_autoplay">
        <input type="checkbox" name="yourstoryz_plugin_autoplay" id="yourstoryz_plugin_autoplay" value="1" <?php checked(1, $autoplay); ?> />
        Enable autoplay for videos in your blog posts.
    </label>
    <?php
}


// Store cron job value in options.php (on/off switch)
function yourstoryz_enable_cron_callback() {
    // Haal de optie op, standaard is uitgeschakeld ('0')
    $enable_cron = esc_attr(get_option('yourstoryz_enable_cron', '0'));
    ?>
    <label for="yourstoryz_enable_cron">
        <input type="checkbox" name="yourstoryz_enable_cron" id="yourstoryz_enable_cron" value="1" <?php checked(1, $enable_cron); ?> />
        Enable automatic fetching of posts from the API at your selected interval (default is hourly).
    </label>
    <?php
}

// Dropdown to set update timer for cron job
function yourstoryz_cron_interval_callback() {
    // Haal de geselecteerde cron-interval optie op, standaard 'hourly'
    $cron_interval = esc_attr(get_option('yourstoryz_cron_interval', 'hourly'));
    ?>
    <select name="yourstoryz_cron_interval" id="yourstoryz_cron_interval">
        <option value="<?php echo esc_attr('hourly'); ?>" <?php selected($cron_interval, 'hourly'); ?>>Hourly</option>
        <option value="<?php echo esc_attr('twicedaily'); ?>" <?php selected($cron_interval, 'twicedaily'); ?>>Twice Daily</option>
        <option value="<?php echo esc_attr('daily'); ?>" <?php selected($cron_interval, 'daily'); ?>>Daily</option>
        <option value="<?php echo esc_attr('weekly'); ?>" <?php selected($cron_interval, 'weekly'); ?>>Weekly</option>
    </select>
    <p class="description">Set an interval after which to automatically fetch posts from the API.</p>
    <?php
}


// Input for changing bearer token (api token)
function yourstoryz_plugin_change_token_callback() {
    // Haal het token op en escape het om HTML-injecties te voorkomen
    $token = esc_attr(get_option('yourstoryz_plugin_change_token'));
    ?>
    <input type="text" name="yourstoryz_plugin_change_token" id="yourstoryz_plugin_change_token" class="regular-text" value="<?php echo esc_attr($token); ?>" placeholder="Enter your API token here" />
    <p class="description">Change your API token. An invalid token will remove access to this page.</p>
    <?php
}

