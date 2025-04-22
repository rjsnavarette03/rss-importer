<?php
// Hook to add settings page in the WordPress admin menu
add_action('admin_menu', 'rss_importer_settings_page');

function rss_importer_settings_page() {
    add_options_page(
        'RSS Importer Settings',
        'RSS Importer',
        'manage_options',
        'rss-importer',
        'rss_importer_settings_page_callback'
    );
}

function rss_importer_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>RSS Importer Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('rss_importer_options_group');
            do_settings_sections('rss-importer');
            submit_button();
            ?>
        </form>

        <!-- Fetch Now Button -->
        <?php rss_importer_fetch_now_button(); ?>
    </div>
    <?php
}

// Register the settings
add_action('admin_init', 'rss_importer_register_settings');

function rss_importer_register_settings() {
    register_setting('rss_importer_options_group', 'rss_importer_feed_url');
    register_setting('rss_importer_options_group', 'rss_importer_num_items');
    register_setting('rss_importer_options_group', 'rss_importer_fetch_frequency');

    add_settings_section('rss_importer_main_section', 'Main Settings', null, 'rss-importer');

    add_settings_field('rss_importer_feed_url', 'RSS Feed URL', 'rss_importer_feed_url_field', 'rss-importer', 'rss_importer_main_section');
    add_settings_field('rss_importer_num_items', 'Number of Items to Import', 'rss_importer_num_items_field', 'rss-importer', 'rss_importer_main_section');
    add_settings_field('rss_importer_fetch_frequency', 'Fetch Frequency', 'rss_importer_fetch_frequency_field', 'rss-importer', 'rss_importer_main_section');
}

function rss_importer_feed_url_field() {
    $feed_url = get_option('rss_importer_feed_url');
    echo "<input type='text' name='rss_importer_feed_url' value='" . esc_attr($feed_url) . "' class='regular-text' />";
}

function rss_importer_num_items_field() {
    $num_items = get_option('rss_importer_num_items', 5);
    echo "<input type='number' name='rss_importer_num_items' value='" . esc_attr($num_items) . "' min='1' />";
}

function rss_importer_fetch_frequency_field() {
    $frequency = get_option('rss_importer_fetch_frequency', 'hourly');
    ?>
    <select name="rss_importer_fetch_frequency">
        <option value="hourly" <?php selected($frequency, 'hourly'); ?>>Hourly</option>
        <option value="daily" <?php selected($frequency, 'daily'); ?>>Daily</option>
        <option value="twicedaily" <?php selected($frequency, 'twicedaily'); ?>>Twice Daily</option>
    </select>
    <?php
}

// Add "Fetch Now" button
function rss_importer_fetch_now_button() {
    ?>
    <form method="post" action="">
        <?php wp_nonce_field('rss_importer_fetch_now_action', 'rss_importer_fetch_now_nonce'); ?>
        <input type="submit" name="rss_importer_fetch_now" value="Fetch Now" class="button button-primary" />
    </form>
    <?php
}

// Handle "Fetch Now" POST action properly
add_action('admin_init', 'rss_importer_handle_fetch_now');

function rss_importer_handle_fetch_now() {
    if (isset($_POST['rss_importer_fetch_now'], $_POST['rss_importer_fetch_now_nonce']) && 
        wp_verify_nonce($_POST['rss_importer_fetch_now_nonce'], 'rss_importer_fetch_now_action')) {
        
        rss_importer_import_feed(); // Import the feed

        // Show admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Feed imported successfully!</p></div>';
        });
    }
}