<?php
/**
 * Plugin Name: Avada Diff Logger
 * Description: Tracks Avada Builder changes and writes NDJSON logs. Includes a Network Admin dashboard for filtering changes.
 * Version: 1.1
 * Author: Your Team
 * Network: true
 */

// CONFIG: Which post types and meta keys to track
const ADL_POST_TYPES = ['page', 'fusion_template'];
const ADL_META_KEYS = ['_fusion_builder_content', '_fusion_builder_status', '_fusion_builder_raw_content'];

// CONFIG: How many historical snapshots to keep
const ADL_MAX_SNAPSHOTS = 20;

// CONFIG: NDJSON log retention (days)
const ADL_LOG_RETENTION_DAYS = 90;

// CONFIG: Log storage location under uploads
const ADL_LOG_DIR = 'adl-activity';

register_activation_hook(__FILE__, 'adl_activate');
register_deactivation_hook(__FILE__, 'adl_deactivate');

// MAIN HOOK
add_action('save_post', 'adl_check_for_avada_diff', 20, 3);
add_action('network_admin_menu', 'adl_register_network_menu');
add_action('adl_retention_cleanup', 'adl_cleanup_old_logs');

// Ensure scheduled cleanup
if (!wp_next_scheduled('adl_retention_cleanup')) {
    wp_schedule_event(time(), 'daily', 'adl_retention_cleanup');
}

function adl_activate() {
    if (!wp_next_scheduled('adl_retention_cleanup')) {
        wp_schedule_event(time(), 'daily', 'adl_retention_cleanup');
    }
}

function adl_deactivate() {
    $timestamp = wp_next_scheduled('adl_retention_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'adl_retention_cleanup');
    }
}

function adl_check_for_avada_diff($post_id, $post, $update) {
    if (!in_array($post->post_type, ADL_POST_TYPES)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $old_hash = get_post_meta($post_id, '_adl_last_hash', true);
    $new_data = adl_get_snapshot_data($post_id);
    $new_hash = hash('sha256', json_encode($new_data));

    if ($new_hash === $old_hash) return; // No change

    // Save new snapshot
    $snapshots = get_post_meta($post_id, '_adl_snapshots', true);
    if (!is_array($snapshots)) $snapshots = [];

    $snapshots[] = [
        'ts' => current_time('mysql'),
        'user' => get_current_user_id(),
        'hash' => $new_hash,
        'data' => $new_data,
    ];
    if (count($snapshots) > ADL_MAX_SNAPSHOTS) {
        $snapshots = array_slice($snapshots, -ADL_MAX_SNAPSHOTS);
    }

    update_post_meta($post_id, '_adl_snapshots', $snapshots);
    update_post_meta($post_id, '_adl_last_hash', $new_hash);

    // Write NDJSON log event
    $site = get_current_blog_id();
    $user = wp_get_current_user();
    $summary = adl_build_summary($post_id, $new_data);

    $event = [
        'ts' => gmdate('c'),
        'site_id' => $site,
        'type' => 'post_update',
        'post_id' => $post_id,
        'post_type' => $post->post_type,
        'title' => get_the_title($post_id),
        'user_id' => (int) $user->ID,
        'user_login' => $user->user_login,
        'summary' => $summary,
        'hash' => $new_hash,
    ];

    adl_write_event($event);
}

function adl_get_snapshot_data($post_id) {
    $meta = [];
    foreach (ADL_META_KEYS as $key) {
        $val = get_post_meta($post_id, $key, true);
        if (!empty($val)) $meta[$key] = $val;
    }
    return [
        'content' => get_post_field('post_content', $post_id),
        'meta' => $meta
    ];
}

function adl_build_summary($post_id, $new_data) {
    $keys = array_keys($new_data['meta']);
    $keys_str = empty($keys) ? 'content only' : 'meta: ' . implode(', ', $keys);
    return sprintf('Updated %s (%s)', get_the_title($post_id), $keys_str);
}

function adl_get_log_dir($site_id = null) {
    $uploads = wp_upload_dir();
    $site_id = is_null($site_id) ? get_current_blog_id() : (int) $site_id;
    return trailingslashit($uploads['basedir']) . ADL_LOG_DIR . '/site-' . $site_id;
}

function adl_write_event($event) {
    $site_id = (int) $event['site_id'];
    $dir = adl_get_log_dir($site_id);
    if (!wp_mkdir_p($dir)) {
        return;
    }
    $filename = $dir . '/' . gmdate('Y-m-d') . '.ndjson';
    $line = wp_json_encode($event) . "\n";
    $fp = @fopen($filename, 'a');
    if (!$fp) {
        return;
    }
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function adl_register_network_menu() {
    add_menu_page(
        'Activity Monitor',
        'Activity Monitor',
        'manage_network',
        'adl-activity-monitor',
        'adl_render_network_page',
        'dashicons-visibility',
        80
    );
}

function adl_render_network_page() {
    if (!current_user_can('manage_network')) {
        wp_die('Insufficient permissions.');
    }

    $today = gmdate('Y-m-d');
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : $today;
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : $today;
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $site_id = isset($_GET['site_id']) ? absint($_GET['site_id']) : 0;

    $events = adl_query_events($start_date, $end_date, $type, $site_id);
    adl_handle_csv_export($events, $start_date, $end_date, $type, $site_id);

    echo '<div class="wrap">';
    echo '<h1>Activity Monitor</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="adl-activity-monitor" />';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="start_date">Start date</label></th><td><input type="date" id="start_date" name="start_date" value="' . esc_attr($start_date) . '" /></td></tr>';
    echo '<tr><th><label for="end_date">End date</label></th><td><input type="date" id="end_date" name="end_date" value="' . esc_attr($end_date) . '" /></td></tr>';
    echo '<tr><th><label for="type">Change type</label></th><td><input type="text" id="type" name="type" placeholder="post_update" value="' . esc_attr($type) . '" /></td></tr>';
    echo '<tr><th><label for="site_id">Site ID</label></th><td><input type="number" id="site_id" name="site_id" value="' . esc_attr($site_id) . '" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Filter');
    echo '</form>';

    $export_url = add_query_arg(
        [
            'page' => 'adl-activity-monitor',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'type' => $type,
            'site_id' => $site_id,
            'adl_export' => 'csv',
        ],
        network_admin_url('admin.php')
    );
    $export_url = wp_nonce_url($export_url, 'adl_export_csv');
    echo '<p><a class="button" href="' . esc_url($export_url) . '">Export CSV</a></p>';

    echo '<h2>Results</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>Date</th><th>Site</th><th>Type</th><th>User</th><th>Item</th><th>Summary</th><th>Details</th>';
    echo '</tr></thead><tbody>';
    if (empty($events)) {
        echo '<tr><td colspan="7">No events found for the selected filters.</td></tr>';
    } else {
        foreach ($events as $event) {
            $json = wp_json_encode($event, JSON_PRETTY_PRINT);
            echo '<tr>';
            echo '<td>' . esc_html($event['ts']) . '</td>';
            echo '<td>' . esc_html($event['site_id']) . '</td>';
            echo '<td>' . esc_html($event['type']) . '</td>';
            echo '<td>' . esc_html($event['user_login']) . ' (' . esc_html($event['user_id']) . ')</td>';
            echo '<td>' . esc_html($event['title']) . ' (#' . esc_html($event['post_id']) . ')</td>';
            echo '<td>' . esc_html($event['summary']) . '</td>';
            echo '<td><details><summary>JSON</summary><pre style="white-space: pre-wrap; max-width: 600px;">' . esc_html($json) . '</pre></details></td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    echo '</div>';
}

function adl_handle_csv_export($events, $start_date, $end_date, $type, $site_id) {
    if (!isset($_GET['adl_export']) || $_GET['adl_export'] !== 'csv') {
        return;
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'adl_export_csv')) {
        wp_die('Invalid export request.');
    }

    $filename = sprintf(
        'activity-%s-to-%s.csv',
        preg_replace('/[^0-9\-]/', '', $start_date),
        preg_replace('/[^0-9\-]/', '', $end_date)
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ts', 'site_id', 'type', 'user_login', 'user_id', 'post_id', 'post_type', 'title', 'summary', 'hash']);
    foreach ($events as $event) {
        fputcsv($out, [
            $event['ts'] ?? '',
            $event['site_id'] ?? '',
            $event['type'] ?? '',
            $event['user_login'] ?? '',
            $event['user_id'] ?? '',
            $event['post_id'] ?? '',
            $event['post_type'] ?? '',
            $event['title'] ?? '',
            $event['summary'] ?? '',
            $event['hash'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

function adl_query_events($start_date, $end_date, $type = '', $site_id = 0) {
    $events = [];
    $start = strtotime($start_date . ' 00:00:00 UTC');
    $end = strtotime($end_date . ' 23:59:59 UTC');
    if (!$start || !$end || $start > $end) {
        return $events;
    }

    $site_ids = [];
    if ($site_id > 0) {
        $site_ids = [$site_id];
    } else {
        $sites = get_sites(['fields' => 'ids']);
        $site_ids = is_array($sites) ? $sites : [];
    }

    for ($ts = $start; $ts <= $end; $ts = strtotime('+1 day', $ts)) {
        $date = gmdate('Y-m-d', $ts);
        foreach ($site_ids as $sid) {
            $file = adl_get_log_dir($sid) . '/' . $date . '.ndjson';
            if (!file_exists($file)) {
                continue;
            }
            $handle = @fopen($file, 'r');
            if (!$handle) {
                continue;
            }
            while (($line = fgets($handle)) !== false) {
                $event = json_decode($line, true);
                if (!is_array($event)) {
                    continue;
                }
                if (!empty($type) && $event['type'] !== $type) {
                    continue;
                }
                $events[] = $event;
            }
            fclose($handle);
        }
    }

    usort($events, function ($a, $b) {
        return strcmp($b['ts'], $a['ts']);
    });

    return $events;
}

function adl_cleanup_old_logs() {
    $uploads = wp_upload_dir();
    $base = trailingslashit($uploads['basedir']) . ADL_LOG_DIR;
    if (!is_dir($base)) {
        return;
    }
    $cutoff = strtotime('-' . ADL_LOG_RETENTION_DAYS . ' days');
    $site_dirs = glob($base . '/site-*', GLOB_ONLYDIR);
    if (!is_array($site_dirs)) {
        return;
    }
    foreach ($site_dirs as $dir) {
        $files = glob($dir . '/*.ndjson');
        if (!is_array($files)) {
            continue;
        }
        foreach ($files as $file) {
            $basename = basename($file, '.ndjson');
            $file_ts = strtotime($basename . ' 00:00:00 UTC');
            if ($file_ts && $file_ts < $cutoff) {
                @unlink($file);
            }
        }
    }
}
