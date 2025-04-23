<?php
/*
 * Plugin Name:       RSS Importer
 * Description:       Automatically import RSS feeds and publish them as posts.
 * Version:           1.0
 * Author:            Raven
 * Author URI:        https://www.rjsnavarette.com
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include admin settings page
include('admin-settings.php');

// Main RSS import function
add_action('init', 'rss_importer_fetch_feed');
function rss_importer_fetch_feed() {
    if (!wp_next_scheduled('rss_importer_fetch_event')) {
        wp_schedule_event(time(), 'hourly', 'rss_importer_fetch_event');
    }
}

add_action('rss_importer_fetch_event', 'rss_importer_import_feed');
function rss_importer_import_feed() {
    $feeds = [
        'https://www.clickorlando.com/arc/outboundfeeds/rss/category/health/?outputType=xml&size=10' => 12,
        'https://www.clickorlando.com/arc/outboundfeeds/rss/category/sports/?outputType=xml&size=10' => 13,
        'https://www.clickorlando.com/arc/outboundfeeds/rss/category/news/?outputType=xml&size=10' => 14,
        'https://www.clickorlando.com/arc/outboundfeeds/rss/category/entertainment/?outputType=xml&size=10' => 15,
    ];

    $num_items = 5;

    foreach ($feeds as $feed_url => $category_id) {
        $rss = fetch_feed($feed_url);

        if (is_wp_error($rss)) {
            continue;
        }

        $maxitems = $rss->get_item_quantity($num_items);
        $rss_items = $rss->get_items(0, $maxitems);

        foreach ($rss_items as $item) {
            $guid = $item->get_id();

            // Check if a post with this GUID already exists
            $existing_posts = get_posts([
                'meta_key' => 'rss_importer_guid',
                'meta_value' => $guid,
                'post_type' => 'post',
                'post_status' => 'any',
                'numberposts' => 1
            ]);

            if (!empty($existing_posts)) {
                continue;
            }

            // Prepare the post title
            $post_title = wp_strip_all_tags($item->get_title());

            // Prepare the post content
            $post_content = $item->get_content();
            if (empty($post_content)) {
                $post_content = $item->get_description();
            }

            // Handle the feed's publish date and convert to local timezone
            $feed_date_raw = $item->get_date('Y-m-d H:i:s');
            if ($feed_date_raw) {
                $feed_date_utc = new DateTime($feed_date_raw, new DateTimeZone('UTC'));
                $feed_date_utc->setTimezone(wp_timezone());
                $post_date = $feed_date_utc->format('Y-m-d H:i:s');
            } else {
                $post_date = current_time('mysql');
            }

            // Create the post
            $post_id = wp_insert_post([
                'post_title'    => $post_title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_type'     => 'post',
                'post_date'    => $post_date,
                'post_author'  => 2,
                'post_category'=> [$category_id],
                'comment_status' => 'closed',
                'ping_status'    => 'open',
                'tags_input'    => ['www.clickorlando.com'],
            ]);

            // Save the GUID as post meta to prevent future duplicates
            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, 'rss_importer_guid', $guid);

                // Extract the image URL from <media:content>
                $media_content = $item->get_item_tags('http://search.yahoo.com/mrss/', 'content');
                if (!empty($media_content)) {
                    $image_url = $media_content[0]['attribs']['']['url'];

                    // Set the featured image if the URL is valid
                    if (!empty($image_url)) {
                        $image_id = media_sideload_image($image_url, $post_id, null, 'id');

                        if (!is_wp_error($image_id)) {
                            set_post_thumbnail($post_id, $image_id);
                        }
                    }
                }
            }
        }
    }
}

// Add deactivation hook
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('rss_importer_fetch_event');
});

add_action('admin_enqueue_scripts', 'rss_importer_enqueue_admin_styles');
function rss_importer_enqueue_admin_styles($hook) {
    // Only load on our plugin settings page
    if ($hook != 'settings_page_rss-importer') {
        return;
    }

    wp_enqueue_style(
        'rss-importer-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/admin.css',
        array(),
        '1.0.0'
    );
}