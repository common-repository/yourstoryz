<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

set_time_limit(300);

function yourstoryz_add_orientation_class($orientation = 'horizontal') {
    if($orientation == 'vertical') {
        return 'yourstoryz-video-vertical';
    } else {
        return 'yourstoryz-video-horizontal';
    }
}

function yourstoryz_getThumbnail($post_id, $thumbnail_url) {
    if (!empty($thumbnail_url)) {
        $tmp_thumbnail_file = download_url($thumbnail_url);

        if (is_wp_error($tmp_thumbnail_file)) {
            error_log('Error downloading thumbnail: ' . $tmp_thumbnail_file->get_error_message());
        }

        $thumbnail_file = [
            'name'     => basename($thumbnail_url),
            'type'     => mime_content_type($tmp_thumbnail_file),
            'tmp_name' => $tmp_thumbnail_file,
            'error'    => 0,
            'size'     => filesize($tmp_thumbnail_file),
        ];

        $overrides = [
            /*
            * Tells WordPress to not look for the POST form fields that would
            * normally be present, default is true, we downloaded the file from
            * a remote server, so there will be no form fields
            */
            'test_form' => false,
            'test_size' => true,
            'test_upload' => true,
        ];

        $thumbnail_movefile = wp_handle_sideload($thumbnail_file, $overrides);

        if (isset($thumbnail_movefile['error'])) {
            error_log('Error handling thumbnail sideload: ' . $thumbnail_movefile['error']);
        }

        $thumbnail_attachment = array(
            'guid'           => $thumbnail_movefile['url'],
            'post_mime_type' => $thumbnail_movefile['type'],
            'post_title'     => sanitize_file_name($thumbnail_file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert thumbnail into media
        $thumbnail_attach_id = wp_insert_attachment($thumbnail_attachment, $thumbnail_movefile['file']);
        $thumbnail_attach_data = wp_generate_attachment_metadata($thumbnail_attach_id, $thumbnail_movefile['file']);
        wp_update_attachment_metadata($thumbnail_attach_id, $thumbnail_attach_data);

        // Set the thumbnail as the featured image for the post
        set_post_thumbnail($post_id, $thumbnail_attach_id);
    }
}

function yourstoryz_getVideo($video_url) {
    // Download video to temp dir
    $tmp_video_file = download_url($video_url);

    if (is_wp_error($tmp_video_file)) {
        error_log('Error downloading video: ' . $tmp_video_file->get_error_message());
        return;
    }

    // Array based on $_FILE as seen in PHP file uploads
    $file = [
        'name'     => basename($video_url),
        'type'     => mime_content_type($tmp_video_file),
        'tmp_name' => $tmp_video_file,
        'error'    => 0,
        'size'     => filesize($tmp_video_file),
    ];

    $overrides = [
        /*
        * Tells WordPress to not look for the POST form fields that would
        * normally be present, default is true, we downloaded the file from
        * a remote server, so there will be no form fields
        */
        'test_form' => false,
        'test_size' => true,
        'test_upload' => true,
    ];
    // Move file to uploads dir
    $movefile = wp_handle_sideload($file, $overrides);

    if (isset($movefile['error'])) {
        error_log('Error handling sideload: ' . $movefile['error']);
    }

    // Prepare media video upload
    $attachment = array(
        'guid'           => $movefile['url'],
        'post_mime_type' => $movefile['type'],
        'post_title'     => sanitize_file_name($file['name']),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    // Insert video into media
    $attach_id = wp_insert_attachment($attachment, $movefile['file']);
    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}

// Create posts from API data
function yourstoryz_call_api() {
    // Call required files for video upload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $autoplay = get_option('yourstoryz_plugin_autoplay') ? 'autoplay' : '';
    $token = get_option('yourstoryz_plugin_change_token');
    $api_videos = 'https://dashboard.yourstoryz.com/api/v1/videos/';
    $api_url = get_option('yourstoryz_plugin_filter_on_department') ?
        'https://dashboard.yourstoryz.com/api/v1/departments/' . get_option('yourstoryz_plugin_filter_on_department') . '/videos' :
        $api_videos;
    // Set and get stored id's from serialized data options.php
    $option_key = 'yourstoryz_processed_ids';
    $stored_ids = get_option($option_key) ?? ['videos' => [], 'contents' => []];
    $response = wp_remote_get($api_url, ['headers' => ['Authorization' => $token, 'Content-Type' => 'application/json']]);

    $parsedown = new Parsedown();

    if (is_wp_error($response)) {
        error_log('API request error: ' . $response->get_error_message());
        wp_send_json_error($response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if ($data === null) {
        wp_send_json_error('Error decoding JSON response');
        return;
    }

    foreach ($data as $api_data) {
        if (isset($stored_ids['videos'][$api_data->id])) { // Check for duplicates based on stored id's
            continue; 
        }

        $post_title = $api_data->title . ' - ' . substr($api_data->created_at, 0, 10);

       $attach_id = yourstoryz_getVideo($api_data->video_url);

        // Prepare post content
        $post_content = '
        <!-- wp:heading {"level":1} -->
        <h1 class="wp-block-heading">' . esc_html($api_data->title) . '</h1>
        <!-- /wp:heading -->
        
        <!-- wp:heading {"level":3} -->
        <h3 class="wp-block-heading">' . esc_html($api_data->description) . '</h3>
        <!-- /wp:heading -->
        
        <!-- wp:video -->
        <figure class="wp-block-video"><video class="' . yourstoryz_add_orientation_class($api_data->orientation ?? 'horizontal') . '" controls ' . esc_attr($autoplay) . ' src="' . esc_url(wp_get_attachment_url($attach_id)) . '"></video></figure>
        <!-- /wp:video -->
        ';
        // Insert post
        $new_post = wp_insert_post([
            'post_author'   => get_current_user_id(),
            'post_content'  => $post_content,
            'post_title'    => $post_title,
            'post_status'   => 'draft',
            'post_type'     => 'post'
        ]);

        if (is_wp_error($new_post)) {
            error_log('Error creating post: ' . $new_post->get_error_message());
            continue;
        }
        // Store id's for checking duplicates
        $stored_ids['videos'][$api_data->id] = ['post_id' => $new_post];

        if (isset($api_data->contents) && is_array($api_data->contents)) {
            foreach ($api_data->contents as $content) {
                if (isset($stored_ids['contents'][$content->id])) {
                    continue;
                }

                // Set variable for hashtags
                $hashtags_data = '';
                // Put hashtags in a paragraph
                if (isset($content->hashtags) && is_array($content->hashtags)) {
                    $hashtags_data .= '<!-- wp:paragraph --><p>';
                    $hashtags_data .= implode(' ', $content->hashtags);
                    $hashtags_data .= '</p><!-- /wp:paragraph -->';
                }
                // Set title with date, leave out unnecessary data
                $post_title = $api_data->title . ' - ' . substr($content->created_at, 0, 10);
                // Prepare post content
                $post_content = '
                <!-- wp:heading {"level":1} -->
                <h1 class="wp-block-heading">' . esc_html($content->title) . '</h1>
                <!-- /wp:heading -->
                
                ' . $parsedown->text($content->content) . '
                
                <!-- wp:video -->
                <figure class="wp-block-video"><video class="' . yourstoryz_add_orientation_class($api_data->orientation ?? 'horizontal') . '" controls ' . esc_attr($autoplay) . ' src="' . esc_url(wp_get_attachment_url($attach_id)) . '"></video></figure>
                <!-- /wp:video -->

                ' . $hashtags_data . '
                ';
                // Insert post
                $new_post_content = wp_insert_post([
                    'post_author'   => get_current_user_id(),
                    'post_content'  => $post_content,
                    'post_title'    => $post_title,
                    'post_status'   => 'draft',
                    'post_type'     => 'post',
                    'tags_input'    => $content->lang
                ]);

                if (is_wp_error($new_post_content)) {
                    error_log('Error creating content post: ' . $new_post_content->get_error_message());
                    continue;
                }
                // Store id's for checking duplicates
                $stored_ids['contents'][$content->id] = ['post_id' => $new_post_content];
            }
        }

        yourstoryz_getThumbnail($new_post, $api_data->thumbnail_url);

        update_option($option_key, $stored_ids);
    }
}

add_action('wp_ajax_call_api', 'yourstoryz_call_api');
