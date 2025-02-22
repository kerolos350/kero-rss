<?php
require_once('../../../../wp-config.php');

if (isset($_POST['delete_feed'])) {
    function delete_feed_by_title($feed_title) {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'kero_rss_feeds';
    
        $delete_result = $wpdb->delete(
            $table_name,
            array('feed_title' => $feed_title),
            array('%s')
        );
    
        if ($delete_result) {
            return "Feed deleted successfully!";
        } else {
            return "Failed to delete the feed or the feed isn't exist!" . $wpdb->last_error;
        }
    }

    delete_feed_by_title($_POST['feed_title']);

    wp_redirect(admin_url('admin.php?page=kero-rss-import'));
    exit;
}

if (isset($_POST['fetch_feed'])) {
    // Add error handling for the main function call
    try {
        $result = post_creator($_POST['feed_url'], );
        if ($result) {
            echo $result;
        } else {
            echo "Failed to process RSS feed.";
        }
    } catch (Exception $e) {
        error_log("Main script error: " . $e->getMessage());
        echo "An error occurred while processing the RSS feed.";
    }

    // wp_redirect(admin_url('admin.php?page=kero-rss-import'));
    // exit;
}

if (isset($_POST['import_all_feeds'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kero_rss_feeds';
    $feeds = $wpdb->get_results("SELECT * FROM $table_name");
    foreach ($feeds as $feed) {
         $feed_url = $feed->feed_url;
         post_creator($feed_url);
    }

    wp_redirect(admin_url('admin.php?page=kero-rss-import'));
    exit;
}
?>