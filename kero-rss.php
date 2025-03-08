<?php

/**
 * 
 * Plugin Name: Kero RSS
 * Plugin URI: https://github.com/kerolos350/kero-rss
 * Description: Kero RSS is a simple plugin that allows you to import RSS feeds and display them as posts on your WordPress site. And it also allows you to scrape the content of the feed items and display them as posts content.
 * Version: 1.0
 * Author: Kerolos Emad
 * Author URI: https://github.com/kerolos350
 * License: Proprietary
 * License URI: https://github.com/kerolos350/kero-rss/blob/kero-rss/License.txt
 * 
 */

define('KERO_RSS_PATH', plugin_dir_path(__FILE__));
define('KERO_RSS_URL', plugin_dir_url(__FILE__));

function create_kero_rss_table() {
    global $wpdb;

   $table_name = $wpdb->prefix . 'kero_rss';

   $charset_collate = $wpdb->get_charset_collate();

   $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        auto_update BOOLEAN DEFAULT 0,
        auto_update_interval INT DEFAULT 12,
        default_image_url VARCHAR(255) DEFAULT '',
        PRIMARY KEY (id)
   ) $charset_collate;";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);

   $feeds_table_name = $wpdb->prefix . 'kero_rss_feeds';

   $sql_feeds = "CREATE TABLE $feeds_table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        feed_url TEXT NOT NULL,
        auto_update_interval INT DEFAULT 12,
        last_fetch_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        feed_title VARCHAR(255) NOT NULL,
        custom_id_class VARCHAR(255) DEFAULT '',
        PRIMARY KEY (id)
   ) $charset_collate;";

   dbDelta($sql_feeds);

   $insert_result = $wpdb->insert(
     $table_name,
     array(
         'auto_update'          => 0,
         'auto_update_interval' => 12,
         'default_image_url'    => '',
     ),
     array('%d', '%d', '%s')
   );
}

function kero_rss_menu() {
   $hook = add_menu_page(
        'Kero RSS Feeds',
        'Kero RSS',
        'manage_options',
        'kero-rss-import',
        'kero_rss_import_feeds_page',
        KERO_RSS_URL . 'assets/rss-icon.png',
        6
   );

   add_submenu_page(
        'kero-rss-import',
        'Import Feeds',
        'Import Feeds',
        'manage_options',
        'kero-rss-import',
        'kero_rss_import_feeds_page'
    );

   add_submenu_page(
        'kero-rss-import',
        'RSS Settings',
        'Settings',
        'manage_options',
        'kero-rss-settings',
        'kero_rss_settings_page'
   );
}

function kero_rss_import_feeds_page() {
   include KERO_RSS_PATH . "include/import-feeds.php";
}

function kero_rss_settings_page() {
   include KERO_RSS_PATH . "include/settings.php";
}

function enqueue_kero_rss_scripts() {
   $plugin_url = KERO_RSS_URL;
    
   $custom_icon_css = "
   #toplevel_page_kero-rss-import .wp-menu-image {
        background: url('{$plugin_url}assets/icons/rss.png') no-repeat center !important;
        background-size: 20px 20px !important;
   }";
   wp_add_inline_style('dashicons', $custom_icon_css);
}

function kero_rss_admin_styles() {
    wp_enqueue_style('kero-rss-admin-styles', KERO_RSS_URL . 'assets/css/style.css');
}
register_activation_hook(__FILE__, 'create_kero_rss_table');

add_action('admin_menu', 'kero_rss_menu');

add_action('admin_enqueue_scripts', 'enqueue_kero_rss_scripts');

add_action('admin_enqueue_scripts', 'kero_rss_admin_styles');

function custom_schedules($schedules) {
     global $wpdb;

     $table_name = $wpdb->prefix . 'kero_rss';
     $auto_update = $wpdb->get_row("SELECT * FROM $table_name");
     if ($auto_update) {
         $interval = $auto_update->auto_update_interval;
     } else {
         $interval = 20000;
     }

     $schedules['custom_schedules'] = array(
         'interval' => $interval * 60 * 60, 
         'display'  => __('custom_schedules')
     );
     return $schedules;
}

function check_post_exists_by_title($title) {
     $args = array(
         'post_type' => array('post', 'page'),
         'post_status' => 'publish',
         'posts_per_page' => 1,
         'title' => $title,
     );
     
     $query = new WP_Query($args);
     return $query->have_posts();
 }
 
 function scraper($link, $classtype = '') {
     $ch = curl_init();
     curl_setopt_array($ch, [
         CURLOPT_URL => $link,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_TIMEOUT => 30,
         CURLOPT_SSL_VERIFYPEER => false,
         CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
         CURLOPT_ENCODING => '',
     ]);
 
     $html = curl_exec($ch);
 
     if (curl_errno($ch)) {
         error_log('Curl Error: ' . curl_error($ch));
         curl_close($ch);
         return false;
     }
 
     curl_close($ch);
 
     if (!$html) {
         error_log('Empty response from URL: ' . $link);
         return false;
     }
 
     $dom = new DOMDocument();
     libxml_use_internal_errors(true);
     $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
     libxml_clear_errors();
 
     $xpath = new DOMXPath($dom);
     $content = '';
 
     $selectors = array(
         "//*[contains(@class, '$classtype')]",
         "//*[@id='$classtype']",
         "//article",
         "//div[contains(@class, '$classtype')]",
         "//div[contains(@class, '$classtype')]"
     );
 
     foreach ($selectors as $selector) {
         $elements = $xpath->query($selector);
         if ($elements->length > 0) {
             foreach ($elements as $element) {
                 foreach ($xpath->query('.//script | .//style | .//iframe | .//comment()', $element) as $node) {
                     $node->parentNode->removeChild($node);
                 }
                 $content .= $element->nodeValue . "\n";
             }
             break;
         }
     }
 
     return trim($content);
 }
 
 function post_creator($url) {
     try {
         global $wpdb;
         $table_name = $wpdb->prefix . 'kero_rss_feeds';
         $classid = $wpdb->get_results("SELECT custom_id_class FROM $table_name WHERE feed_url = '$url'", ARRAY_A);
         $classid = $classid[0]['custom_id_class'];
 
         $default_table = $wpdb->prefix .'kero_rss';
 
         $rss = @simplexml_load_file($url);
         if (!$rss) {
             throw new Exception("Failed to load RSS feed: $url");
         }
 
         $processed = 0;
         $max_posts = 100; // Limit number of posts per run

         $items = $rss->channel->item;
         foreach ($items as $item) {
             if ($processed >= $max_posts) break;
 
             $title = wp_strip_all_tags((string)$item->title);
             if (!check_post_exists_by_title($title)) {
                    $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $table_name LIMIT 1");
                    if (isset($item->enclosure)) {
                         $imageUrl = (string)$item->enclosure['url'];
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif (isset($item->children('media', true)->content)) {
                         $media = $item->children('media', true);
                         $imageUrl = (string)$media->content->attributes()->url;
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif (isset($item->children('itunes', true)->content)) {
                         $imageUrl = (string)$rss->channel->children('itunes', true)->image->attributes()->href;
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif (empty($item->children('itunes', true)->content) && isset($rss->channel->image->url)) {
                         $imageUrl = (string)$rss->channel->image->url;
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif ($item->children('http://search.yahoo.com/mrss/')) {
                         if ($item->children('http://search.yahoo.com/mrss/')->thumbnail) {
                              $imageUrl = $item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()->url;
                              $text = $imageUrl;
                              $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';
     
                              if (preg_match($patterns, $text, $matches)) {
                                   $imageUrl = $text;
                              } else {
                                   $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                              }
                         } elseif ($item->children('http://search.yahoo.com/mrss/')->group) {
                              foreach ($item->children('http://search.yahoo.com/mrss/')->group->children('http://search.yahoo.com/mrss/') as $content) {
                                   $imageUrl = $content->attributes()->url;
                                   $text = $imageUrl;
                                   $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';
          
                                   if (preg_match($patterns, $text, $matches)) {
                                        $imageUrl = $text;
                                   } else {
                                        $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                                   }
                                   break;
                              }
                         }
                    } elseif (isset($item->enclosure) && strpos($item->enclosure['type'], 'image') !== false) {
                         $imageUrl = $item->enclosure['url'];
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif (preg_match('/<img.*?src=["\'](.*?)["\']/i', $item->description, $matches)) {
                         $imageUrl = $matches[1];
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif ($item->image) {
                         $imageUrl = $item->Image->url;
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    } elseif ($item->enclosure) {
                         $enclosure_url = (string)$item->enclosure['url'];
                         $imageUrl = $enclosure_url;
                         $text = $imageUrl;
                         $patterns = '/\b(?:jpg|jpeg|png|gif|bmp|tiff|webp|heic|svg)\b/i';

                         if (preg_match($patterns, $text, $matches)) {
                              $imageUrl = $text;
                         } else {
                              $imageUrl = $wpdb->get_var("SELECT default_image_url FROM $default_table LIMIT 1");
                         }
                    }
          
                    if ($classid === '') {
                         $content = (string)$item->description;
                    } else {
                         $content = scraper((string)$item->link, $classid);
                    }
                    
                    if (!$content) {
                         error_log("Failed to scrape content for: " . $item->link);
                         continue;
                    }
          
                    $post_data = array(
                         'post_title'    => $title,
                         'post_content'  => $content,
                         'post_status'   => 'publish',
                         'post_author'   => 1,
                         'post_type'     => 'post',
                         'post_category' => array(1),
                         'post_date'     => isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime($item->pubDate)) : null,
                    );
          
                    $post_id = wp_insert_post($post_data);
                    if (is_wp_error($post_id)) {
                         error_log("Failed to create post: " . $post_id->get_error_message());
                         continue;
                    }
          
                    if ($post_id && !empty($imageUrl)) {
                         require_once(ABSPATH . 'wp-admin/includes/media.php');
                         require_once(ABSPATH . 'wp-admin/includes/file.php');
                         require_once(ABSPATH . 'wp-admin/includes/image.php');
          
                         $image_id = media_sideload_image($imageUrl, $post_id, '', 'id');
                         if (!is_wp_error($image_id)) {
                              set_post_thumbnail($post_id, $image_id);
                         }
                    }
          
                    $processed++;
             } else {
               exit('Post with this title exist');
             }

         }
         
          $last_update_date = $wpdb->get_results("SELECT last_fetch_time FROM $table_name WHERE feed_url = '$url'", ARRAY_A);
          $last_update_date = $last_update_date[0]['last_fetch_time'];

          if ($last_update_date) {
               $update_result = $wpdb->update(
                    $table_name,
                    array(
                         'last_fetch_time' => current_time('mysql'),
                    ),
                    array('feed_url' => $url),
                    array('%s'),
                    array('%s')
               );

               if ($update_result !== false) {
                    echo 'The feed has been updated successfully!';
               } else {
                    echo 'Failed to update the date! ' . $wpdb->last_error;
               }
          }
 
         return "Processed $processed posts successfully.";
     } catch (Exception $e) {
         error_log("RSS Scraper Error: " . $e->getMessage());
         return false;
     }
}

function custom_schedules_activation() {
     if (!wp_next_scheduled('custom_schedules_job_hook')) {
         wp_schedule_event(time(), 'custom_schedules', 'custom_schedules_job_hook');
     }
}
function update_event() {
     global $wpdb;
     $table_name = $wpdb->prefix . 'kero_rss_feeds';
     $feeds = $wpdb->get_results("SELECT * FROM $table_name");
     foreach ($feeds as $feed) {
         $feed_url = $feed->feed_url;
         post_creator($feed_url);
     }
}

add_filter('cron_schedules', 'custom_schedules');

add_action('init', 'custom_schedules_activation');
 
add_action('custom_schedules_job_hook', 'update_event');
?>