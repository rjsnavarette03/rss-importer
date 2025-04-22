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
    $feed_url = get_option('rss_importer_feed_url');
    $num_items = get_option('rss_importer_num_items', 5);
    $rss = fetch_feed($feed_url);

    if (is_wp_error($rss)) {
        return;
    }

    $maxitems = $rss->get_item_quantity($num_items);
    $rss_items = $rss->get_items(0, $maxitems);

    foreach ($rss_items as $item) {
        $post_title = $item->get_title();
        $post_content = $item->get_content();
        $post_date = $item->get_date('Y-m-d H:i:s');

        if (post_exists($post_title)) {
            continue;
        }

        wp_insert_post([
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_status'  => 'publish',
            'post_date'    => $post_date,
            'post_author'  => 2,
            'post_category'=> [1] // Default category
        ]);
    }
}

// Add deactivation hook
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('rss_importer_fetch_event');
});