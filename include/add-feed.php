<?php

    require_once('../../../../wp-config.php');
    function kero_rss_feed_add($feedUrl, $feedTitle, $idClass) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kero_rss_feeds';

        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'feed_url' => esc_url_raw($feedUrl),
                'last_fetch_time' => 0,
                'feed_title' => sanitize_text_field($feedTitle),
                'custom_id_class' => sanitize_text_field($idClass),
            ),
            array('%s', '%s', '%s', '%s')
        );

        if ($insert_result) {
            wp_redirect(admin_url('admin.php?page=kero-rss-import'));
            exit;
        } else {
            echo "Failed to import the feed!" . $wpdb->last_error;
        }
    }
    
    kero_rss_feed_add($_POST['feed_urls'], $_POST['feed_name'], $_POST['Scrape_id_class']);

?>