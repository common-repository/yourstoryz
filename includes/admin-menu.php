<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Create plugin menu in sidebar
add_action('admin_menu', 'yourstoryz_plugin_create_menu');

function yourstoryz_plugin_create_menu() {
    add_menu_page(
        'YourStoryz Info',
        'YourStoryz',
        'manage_options',
        'yourstoryz-plugin-info',
        'yourstoryz_plugin_info_page',
        'dashicons-admin-generic',
        100
    );

    add_submenu_page(
        'yourstoryz-plugin-info',
        'YourStoryz Settings',
        'Settings',
        'manage_options',
        'yourstoryz-plugin-settings',
        'yourstoryz_plugin_settings_page'
    );
}
// Plugin explaination page with check for api key
function yourstoryz_plugin_info_page() {
    // Get api key from options.php
    $api_key = get_option('yourstoryz_plugin_change_token');
    $validation_message = '';

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yourstoryz_plugin_change_token'])) {
        if (isset($_POST['yourstoryz_plugin_nonce_field']) && check_admin_referer('yourstoryz_plugin_nonce_action', 'yourstoryz_plugin_nonce_field')) {
            $api_key = sanitize_text_field(wp_unslash($_POST['yourstoryz_plugin_change_token']));
            update_option('yourstoryz_plugin_change_token', $api_key);
    
            if (yourstoryz_validate_api_key($api_key)) {
                $validation_message = '<div class="notice notice-success"><p>API key validated successfully!</p></div>';
            } else {
                $validation_message = '<div class="notice notice-warning"><p>Invalid API key. Please check and try again.</p></div>';
            }
        }

    }
    // HTML for plugin explaination page
    ?>
    <div class="wrap">
        <h1>Welcome to YourStoryz</h1>
        <div class="notice notice-info alt">
            <p><strong>Warning:</strong> This plugin will download videos to your site. We are not responsible for any data usage or storage impacts.</p>
        </div>
        <p>To use this plugin, you need an active subscription to <a href="https://yourstoryz.com/" target="_blank">YourStoryz</a>.</p>
        <h2>How to Get Your API Token?</h2>
        <ol>
            <li>Create an Account:
                <p>First, create an account on the YourStoryz platform. You can sign up at <a href="https://yourstoryz.com/" target="_blank">yourstoryz.com</a> or via the YourStoryz app available on iOS and Android.</p>
                <p>
                    <a href="https://play.google.com/store/apps/details?id=com.yourstoryz.storyz&pcampaignid=web_share" target="_blank">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/get-it-on-google.png'); ?>" alt="Get it on Google Play" style="width: auto; height: 50px;">
                    </a>
                    <a href="https://apps.apple.com/nl/app/yourstoryz/id1544965290" target="_blank">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/download-on-the-appstore.svg'); ?>" alt="Download on the App Store" style="width: auto; height: 50px;">
                    </a>
                </p>
            </li>
            <li>Create Your API Token:
                <p>Once you have an account, you're ready to create your API token. Follow these steps:</p>
                <ol>
                    <li>Go to the YourStoryz dashboard: <a href="https://dashboard.yourstoryz.com/en/login" target="_blank">dashboard.yourstoryz.com/dashboard</a></li>
                    <li><strong>Navigate to My Companies > Tokens > API Tokens</strong></li>
                    <li>Create your API token.</li>
                    <li>After obtaining your API token, enter it in the provided field within the plugin settings to start using the plugin.</li>
                </ol>
            </li>
        </ol>
        <?php echo $validation_message; ?>
        <form method="post" action="" class="yourstoryz-form">
            <strong>API Token:</strong>
            <input type="text" name="yourstoryz_plugin_change_token" id="yourstoryz_plugin_change_token" class="regular-text" placeholder="Bearer 1234|abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwx" value="<?php echo esc_attr($api_key); ?>">
            <?php wp_nonce_field('yourstoryz_plugin_nonce_action', 'yourstoryz_plugin_nonce_field'); ?>
            <?php submit_button('Validate Token', 'primary', '', false); ?>
        </form>
        <h2>Need Help?</h2>
        <p>For further assistance, please <a href="https://yourstoryz.com/contact/" target="_blank">contact our support team</a>.</p>
    </div>
    <?php
}
// Settings page
function yourstoryz_plugin_settings_page() {
    $api_key = get_option('yourstoryz_plugin_change_token');
    
    if (empty($api_key) || !yourstoryz_validate_api_key($api_key)) {
        ?>
        <div class="notice notice-warning"><p>Please enter a valid API key on the YourStoryz Info page.</p></div>
        <?php
        return;
    }
    // Buttons and HTML for settings page
    ?>
    <div class="wrap">
        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/logo.svg'); ?>" alt="YourStoryz Logo">
        <div class="notice notice-info alt">
            <p><strong>Warning:</strong> This plugin will download videos to your site. We are not responsible for any data usage or storage impacts.</p>
        </div>
        <form method="post" action="options.php">
        <?php wp_nonce_field('yourstoryz_plugin_nonce_action', 'yourstoryz_plugin_nonce_field'); ?>
            <?php
            settings_fields('yourstoryz_plugin_settings_group');
            do_settings_sections('yourstoryz-plugin-settings');
            ?>
            <p class="submit">
                <?php
                submit_button('Update settings', 'link large', '', false);
                echo '&nbsp;';
                echo submit_button('Get YourStoryz', 'primary large', 'yourstoryz_getstoryz', false);
                ?>
            </p>
        </form>
    </div>
    <?php
}
// Connect to api for api validation
function yourstoryz_validate_api_key($api_key) {
    $response = wp_remote_get('https://dashboard.yourstoryz.com/api/v1/videos/', [
        'headers' => [
            'Authorization' => $api_key,
            'Content-Type'  => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    return !empty($data);
}