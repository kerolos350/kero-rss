<?php
    if (!defined('ABSPATH')) {
        exit;
    }
?>
<div class="container">
    <h1>Kero RSS settings</h1>
    <div class="settings-container sub-container">
        <form class="settings-table" method="post">
            <table class="feed-table">
                <tr>
                    <th scope="row">Enable/Disable auto-update</th>
                    <?php
                        global $wpdb;

                        $table_name = $wpdb->prefix . 'kero_rss';
                        $row = $wpdb->get_row("SELECT auto_update FROM $table_name");
                    
                        if ($row->auto_update == 1) {
                            echo "<td><input type='checkbox' name='auto_update' id='auto_update' value='1' checked></td>";
                        } else {
                            echo "<td><input type='checkbox' name='auto_update' id='auto_update' value='1'></td>";
                        }
                    ?>
                </tr>
                <tr>
                    <th scope="row">Auto update interval</th>
                    <td>
                        <select name="update_frequency">
                            <?php
                                global $wpdb;

                                $table_name = $wpdb->prefix . 'kero_rss';
                                $row = $wpdb->get_row("SELECT auto_update_interval FROM $table_name");
                            
                                $options = [1, 3, 6, 12, 'daily', 'weekly'];
                                foreach ($options as $option) {
                                    $selected = ($option == $row->auto_update_interval) ? 'selected' : '';
                                    echo "<option value=\"$option\" $selected>$option</option>";
                                }

                                if (!in_array($row->auto_update_interval, $options)) {
                                    echo "<option value=\"{$row->auto_update_interval}\" selected>{$row->auto_update_interval}</option>";
                                }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Default image URL</th>
                    <td>
                        <input type="text" name="default_image_url" class="regular-text" placeholder="Enter Default Image URL:">
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <?php
                            global $wpdb;

                            $table_name = $wpdb->prefix . 'kero_rss';
                            $row = $wpdb->get_row("SELECT default_image_url FROM $table_name");

                            if ($row->default_image_url !== "") {
                                echo "<div style='height: fit-content; width: calc(100% - 20px); padding:10px; overflow-x: hidden;
                                    background-color:rgb(240, 240, 241); border-radius: 5px; font-weight: 600'><p>" . $row->default_image_url . "</p></div>";
                            }
                        ?>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="update_settings" id="submit"value="Update Settings">
            </p>
            <?php
                function update_settings($auto_update, $auto_update_interval, $default_image_url='') {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'kero_rss'; 

                    $auto_update = (int) filter_var($auto_update, FILTER_VALIDATE_BOOLEAN);
                    $default_image_url = esc_url_raw($default_image_url);

                    if ($auto_update_interval == "daily") {
                        $auto_update_interval = 24;
                    } elseif ($auto_update_interval == "weekly") {
                        $auto_update_interval = 168;
                    } else {
                        $auto_update_interval = intval($auto_update_interval);
                    }

                    $row = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

                    if ($row) {
                        $update_result = $wpdb->update(
                            $table_name,
                            array(
                                'auto_update'          => $auto_update,
                                'auto_update_interval' => $auto_update_interval,
                                'default_image_url'    => $default_image_url,
                            ),
                            array('id' => $row->id),
                            array('%d', '%d', '%s'), 
                            array('%d')
                        );

                        if ($update_result !== false) {
                            echo 'The settings have been updated successfully!';
                        } else {
                            echo 'Failed to update the row! ' . $wpdb->last_error;
                        }
                    } else {
                        $insert_result = $wpdb->insert(
                            $table_name,
                            array(
                                'auto_update'          => $auto_update,
                                'auto_update_interval' => $auto_update_interval,
                                'default_image_url'    => $default_image_url,
                            ),
                            array('%d', '%d', '%s')
                        );

                        if ($insert_result) {
                            echo 'New row added successfully!';
                        } else {
                            echo 'Failed to add new row! ' . $wpdb->last_error;
                        }
                    }

                    if (wp_next_scheduled('custom_schedules_job_hook')) {
                        wp_clear_scheduled_hook('custom_schedules_job_hook');
                        wp_schedule_event(time(), 'custom_schedules', 'custom_schedules_job_hook');
                    } else {
                        echo 'Custom schedule is NOT active. see it in wp cron control plugin';
                    }

                    echo "<script>location.reload();</script>";
                }

                if (isset($_POST['update_settings'])) {
                    if (empty($_POST['auto_update']) || !isset($_POST['auto_update'])) {
                        if (empty($_POST['default_image_url']) || !isset($_POST['default_image_url'])) {
                            update_settings(0, $_POST['update_frequency'], '');
                        }else {
                            update_settings(0, $_POST['update_frequency'], $_POST['default_image_url']);
                        }
                    }else {
                        update_settings($_POST['auto_update'], $_POST['update_frequency'], $_POST['default_image_url']);
                    }
                    
                }
            ?>
        </form>
    </div>
</div>
