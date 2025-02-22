<?php
    if (!defined('ABSPATH')) {
        exit;
    }
?>
<div class="container">
    <h1>Import RSS Feeds</h1>
    <div class="sub-container">
        <form method="post" action="<?php echo plugin_dir_url(__FILE__) . 'add-feed.php'; ?>">
            <table class="feed-table">
                <tr>
                    <th scope="row">Feed Name</th>
                    <td><input type="text" name="feed_name"  placeholder="Feed Name:"></td>
                </tr>
                <tr>
                    <th scope="row">Feed URLs</th>
                    <td>
                        <input type="text" name="feed_urls" rows="5" placeholder="Enter RSS Feed URL:"></input>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Scrape ID/Class</th>
                    <td>
                        <input type="text" name="Scrape_id_class"  placeholder="Enter the ID/calss to scrape:">
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="import_feed" id="submit" value="Import Feeds">
            </p>
        </form>
        <div class="check-url">
            <h2>
                <a href="https://validator.w3.org/feed/">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M432 320H400a16 16 0 0 0 -16 16V448H64V128H208a16 16 0 0 0 16-16V80a16 16 0 0 0 -16-16H48A48 48 0 0 0 0 112V464a48 48 0 0 0 48 48H400a48 48 0 0 0 48-48V336A16 16 0 0 0 432 320zM488 0h-128c-21.4 0-32.1 25.9-17 41l35.7 35.7L135 320.4a24 24 0 0 0 0 34L157.7 377a24 24 0 0 0 34 0L435.3 133.3 471 169c15 15 41 4.5 41-17V24A24 24 0 0 0 488 0z"/></svg>
                    Check if the URL is rss feed
                </a>
            </h2>
        </div>
    </div>

    <div class="imported-feeds">
        <h2>Imported Feeds</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Feed Name</th>
                    <th scope="col" class="manage-column">Scrape ID/Class</th>
                    <th scope="col" class="manage-column">Feed URLs</th>
                    <th scope="col" class="manage-column">Last Updated</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    function kero_rss_feed_list_menu() {
                        include KERO_RSS_PATH . "include/feed-list.php";
                    }

                    kero_rss_feed_list_menu();
                ?>
            </tbody>
        </table>
        <form class="fetch-all" method="post" action="<?php echo plugin_dir_url(__FILE__) . 'feed_handling.php'; ?>">
            <input type="submit" name="import_all_feeds" class="button button-secondary" value="Fetch All Feeds">
        </form>
    </div>
</div>