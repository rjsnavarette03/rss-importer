<?php
// Add the plugin's settings page to the WordPress admin menu
add_action('admin_menu', 'rss_importer_add_admin_menu');
function rss_importer_add_admin_menu() {
    add_options_page(
        'RSS Importer Settings',
        'RSS Importer',
        'manage_options',
        'rss-importer',
        'rss_importer_settings_page'
    );
}

// Display the settings page with a manual fetch button
function rss_importer_settings_page() {
    ?>
    <div class="wrap">
        <h1>RSS Importer Settings</h1>
        <p>Click the button below to manually fetch RSS feed items.</p>
        
        <!-- Form to trigger manual feed import -->
        <form method="post">
            <?php wp_nonce_field('rss_importer_manual_fetch', 'rss_importer_nonce'); ?>
            <input type="submit" name="rss_importer_manual_fetch_button" class="button button-primary" value="Fetch RSS Feeds Now">
        </form>

        <?php
        // Handle the manual fetch button click
        if (isset($_POST['rss_importer_manual_fetch_button']) && check_admin_referer('rss_importer_manual_fetch', 'rss_importer_nonce')) {
            // Manually trigger the feed import function
            rss_importer_import_feed();
            echo '<div class="updated"><p>Feeds have been successfully fetched.</p></div>';
        }
        ?>
    </div>
    <?php
}