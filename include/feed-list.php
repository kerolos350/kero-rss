<?php
     global $wpdb;
     $feeds_table = $wpdb->prefix . 'kero_rss_feeds';
    
     $feeds = $wpdb->get_results("SELECT * FROM $feeds_table ORDER BY last_fetch_time DESC", ARRAY_A);
    
     if (!empty($feeds)) {
        foreach ($feeds as $feed) {
            
                echo "<tr>";
                    echo "<th scope='col' class='manage-column'>". esc_html($feed['feed_title']) ."</th>";
                    echo "<th scope='col' class='manage-column'>" . esc_html($feed['custom_id_class']) . "</th>";
                    echo "<th scope='col' class='manage-column'>" . esc_url($feed['feed_url']) . "</th>";
                    echo "<th scope='col' class='manage-column'>" . esc_html($feed['last_fetch_time']) . "</th>";
                    echo "<th scope='col' class='manage-column'>";
                        echo "<form class='fetch-feed' method='post' action='". plugin_dir_url(__FILE__) . 'feed_handling.php' ."'>";
                            echo "<input type='submit' name='fetch_feed' value='Fetch'>";
                            echo "<input type='hidden' name='feed_title' value='". $feed['feed_title'] ."'>";
                            echo "<input type='hidden' name='feed_url' value='". $feed['feed_url'] ."'>";
                            echo "<input type='submit' name='delete_feed' value='Delete'>";
                        echo "</form>";
                    echo "</th>";
                echo "</tr>";
        }
     } else {
        echo "<tr>";
            echo "<th scope='col' class='manage-column'>No feed available</th>";
            echo "<th scope='col' class='manage-column'>---</th>";
            echo "<th scope='col' class='manage-column'>---</th>";
            echo "<th scope='col' class='manage-column'>---</th>";
            echo "<th scope='col' class='manage-column'>---</th>";
        echo "</tr>";
     }
?>