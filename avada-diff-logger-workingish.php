
<?php
/**
 * Plugin Name: Avada Diff Logger (MU)
 * Description: Tracks Avada Builder changes and writes NDJSON logs. Includes a Network Admin dashboard for filtering changes.
 * Version: 1.1
 * Author: Your Team
 */

// CONFIG: Which post types and meta keys to snapshot for Avada diffs
const ADL_AVADA_POST_TYPES = ['page', 'fusion_template'];
const ADL_META_KEYS = ['_fusion_builder_content', '_fusion_builder_status', '_fusion_builder_raw_content'];

// CONFIG: How many historical snapshots to keep
const ADL_MAX_SNAPSHOTS = 20;

// CONFIG: NDJSON log retention (days)
const ADL_LOG_RETENTION_DAYS = 90;

// CONFIG: Log storage location under uploads
const ADL_LOG_DIR = 'adl-activity';

// CONFIG: Disable noisy option logging (recommended)
const ADL_LOG_OPTIONS = false;

// CONFIG: Option prefixes to skip if option logging is enabled
const ADL_OPTION_SKIP_PREFIXES = [
    '_transient_',
    '_site_transient_',
    'cron',
    'rewrite_rules',
    'recently_edited',
    'theme_mods_',
    'tribe_',
    'fusion_',
];

// MAIN HOOKS (broad activity logging)
add_action('save_post', 'adl_log_post_save', 20, 3);
add_action('transition_post_status', 'adl_log_post_status_change', 10, 3);
add_action('deleted_post', 'adl_log_post_deleted', 10, 1);
add_action('trashed_post', 'adl_log_post_trashed', 10, 1);
add_action('untrashed_post', 'adl_log_post_untrashed', 10, 1);
add_action('add_attachment', 'adl_log_media_added', 10, 1);
add_action('edit_attachment', 'adl_log_media_edited', 10, 1);
add_action('delete_attachment', 'adl_log_media_deleted', 10, 1);
add_action('wp_insert_comment', 'adl_log_comment_added', 10, 2);
add_action('wp_set_comment_status', 'adl_log_comment_status', 10, 2);
add_action('switch_theme', 'adl_log_theme_switch', 10, 3);
add_action('activated_plugin', 'adl_log_plugin_activated', 10, 2);
add_action('deactivated_plugin', 'adl_log_plugin_deactivated', 10, 2);
add_action('wp_update_nav_menu', 'adl_log_menu_updated', 10, 2);
add_action('user_register', 'adl_log_user_created', 10, 1);
add_action('deleted_user', 'adl_log_user_deleted', 10, 1);
add_action('profile_update', 'adl_log_user_updated', 10, 2);
add_action('updated_option', 'adl_log_option_updated', 10, 3);
add_action('added_option', 'adl_log_option_added', 10, 2);
add_action('deleted_option', 'adl_log_option_deleted', 10, 1);
add_action('network_admin_menu', 'adl_register_network_menu');
add_action('adl_retention_cleanup', 'adl_cleanup_old_logs');

// Ensure scheduled cleanup
if (!wp_next_scheduled('adl_retention_cleanup')) {
    wp_schedule_event(time(), 'daily', 'adl_retention_cleanup');
}

function adl_log_post_save($post_id, $post, $update) {
    if (!is_object($post)) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $snapshots = get_post_meta($post_id, '_adl_snapshots', true);
    $old_data = adl_get_last_snapshot_data($snapshots);
    $new_data = adl_get_snapshot_data($post_id);
    $diff = adl_diff_avada_snapshots($old_data, $new_data);
    $content_diff = adl_compute_text_diff(
        is_array($old_data) ? ($old_data['content'] ?? '') : '',
        $new_data['content'] ?? ''
    );
    $meta_diff = adl_diff_avada_meta_texts($old_data, $new_data, $diff['avada_changed_meta_keys']);

    $summary = sprintf('Saved %s (%s)', get_the_title($post_id), $post->post_type);
    adl_log_event('post_update', $summary, array_merge([
        'post_id' => $post_id,
        'post_type' => $post->post_type,
        'title' => get_the_title($post_id),
        'status' => $post->post_status,
        'content_changed' => $content_diff !== '',
        'content_diff' => $content_diff,
        'avada_meta_diff' => $meta_diff,
    ], $diff));

    // Capture Avada snapshots/diffs for supported post types
    if (in_array($post->post_type, ADL_AVADA_POST_TYPES)) {
        adl_capture_avada_snapshot($post_id, $new_data, $old_data);
    }
}

function adl_capture_avada_snapshot($post_id, $new_data = null, $old_data = null) {
    if (!is_array($new_data)) {
        $new_data = adl_get_snapshot_data($post_id);
    }
    $old_hash = get_post_meta($post_id, '_adl_last_hash', true);
    $new_hash = hash('sha256', json_encode($new_data));

    if ($new_hash === $old_hash) return; // No change

    $snapshots = get_post_meta($post_id, '_adl_snapshots', true);
    if (!is_array($snapshots)) $snapshots = [];
    if (!is_array($old_data)) {
        $old_data = adl_get_last_snapshot_data($snapshots);
    }

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

    $summary = adl_build_summary($post_id, $new_data);
    $diff = adl_diff_avada_snapshots($old_data, $new_data);
    $meta_diff = adl_diff_avada_meta_texts($old_data, $new_data, $diff['avada_changed_meta_keys']);
    $element_changes = adl_diff_avada_elements($old_data, $new_data);
    adl_log_event('avada_builder_update', $summary, array_merge([
        'post_id' => $post_id,
        'post_type' => get_post_type($post_id),
        'title' => get_the_title($post_id),
        'hash' => $new_hash,
        'meta_keys' => array_keys($new_data['meta']),
        'content_diff' => adl_compute_text_diff(
            is_array($old_data) ? ($old_data['content'] ?? '') : '',
            $new_data['content'] ?? ''
        ),
        'avada_meta_diff' => $meta_diff,
        'avada_element_changes' => $element_changes,
    ], $diff));
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

function adl_diff_avada_meta_texts($old_data, $new_data, $changed_keys) {
    $diffs = [];
    if (!is_array($changed_keys)) {
        return $diffs;
    }
    $old_meta = is_array($old_data) && isset($old_data['meta']) && is_array($old_data['meta']) ? $old_data['meta'] : [];
    $new_meta = isset($new_data['meta']) && is_array($new_data['meta']) ? $new_data['meta'] : [];

    foreach ($changed_keys as $key) {
        $old_val = $old_meta[$key] ?? '';
        $new_val = $new_meta[$key] ?? '';
        $diffs[$key] = adl_compute_text_diff($old_val, $new_val);
    }
    return $diffs;
}

function adl_get_last_snapshot_data($snapshots) {
    if (!is_array($snapshots) || empty($snapshots)) {
        return null;
    }
    $last = end($snapshots);
    if (is_array($last) && isset($last['data']) && is_array($last['data'])) {
        return $last['data'];
    }
    return null;
}

function adl_compute_text_diff($old, $new) {
    $old = (string) $old;
    $new = (string) $new;
    if ($old === $new) {
        return '';
    }
    if (function_exists('wp_text_diff')) {
        $diff = wp_text_diff($old, $new, [
            'show_split_view' => true,
        ]);
        return adl_limit_text((string) $diff);
    }
    $fallback = "--- OLD ---\n" . $old . "\n--- NEW ---\n" . $new;
    return adl_limit_text($fallback);
}

function adl_limit_text($text, $max = 10000) {
    $text = (string) $text;
    if (strlen($text) <= $max) {
        return $text;
    }
    return substr($text, 0, $max) . "\n...[truncated]";
}

function adl_diff_avada_snapshots($old_data, $new_data) {
    $diff = [
        'avada_changed_meta_keys' => [],
        'avada_changed_fields' => [],
        'avada_element_types_added' => [],
        'avada_element_types_removed' => [],
        'avada_element_type_counts' => [],
        'avada_element_ids_added' => [],
        'avada_element_ids_removed' => [],
    ];

    if (!is_array($old_data) || !isset($old_data['meta']) || !is_array($old_data['meta'])) {
        $old_data = ['meta' => []];
    }
    if (!isset($new_data['meta']) || !is_array($new_data['meta'])) {
        return $diff;
    }

    $all_keys = array_unique(array_merge(array_keys($old_data['meta']), array_keys($new_data['meta'])));
    foreach ($all_keys as $key) {
        $old_val = $old_data['meta'][$key] ?? null;
        $new_val = $new_data['meta'][$key] ?? null;
        if ($old_val !== $new_val) {
            $diff['avada_changed_meta_keys'][] = $key;
        }
    }

    $builder_old = adl_extract_builder_elements($old_data['meta']);
    $builder_new = adl_extract_builder_elements($new_data['meta']);

    $diff['avada_element_type_counts'] = $builder_new['type_counts'];
    $diff['avada_element_types_added'] = array_values(array_diff($builder_new['types'], $builder_old['types']));
    $diff['avada_element_types_removed'] = array_values(array_diff($builder_old['types'], $builder_new['types']));
    $diff['avada_element_ids_added'] = array_values(array_diff($builder_new['ids'], $builder_old['ids']));
    $diff['avada_element_ids_removed'] = array_values(array_diff($builder_old['ids'], $builder_new['ids']));

    if (!empty($diff['avada_changed_meta_keys'])) {
        $diff['avada_changed_fields'][] = 'meta_keys:' . implode(',', $diff['avada_changed_meta_keys']);
    }
    if (!empty($diff['avada_element_types_added']) || !empty($diff['avada_element_types_removed'])) {
        $diff['avada_changed_fields'][] = 'builder_element_types';
    }
    if (!empty($diff['avada_element_ids_added']) || !empty($diff['avada_element_ids_removed'])) {
        $diff['avada_changed_fields'][] = 'builder_element_ids';
    }

    return $diff;
}

function adl_diff_avada_elements($old_data, $new_data) {
    $old_meta = is_array($old_data) && isset($old_data['meta']) && is_array($old_data['meta']) ? $old_data['meta'] : [];
    $new_meta = isset($new_data['meta']) && is_array($new_data['meta']) ? $new_data['meta'] : [];

    $old_elements = adl_flatten_builder_elements($old_meta);
    $new_elements = adl_flatten_builder_elements($new_meta);

    $changes = [];
    foreach ($new_elements as $id => $element) {
        $old_element = $old_elements[$id] ?? null;
        if (!$old_element) {
            $changes[] = [
                'change' => 'added',
                'id' => $id,
                'type' => $element['type'] ?? '',
                'label' => $element['label'] ?? '',
            ];
            continue;
        }

        $before = $old_element['raw_params'] ?? '';
        $after = $element['raw_params'] ?? '';
        if ($before !== $after) {
            $changes[] = [
                'change' => 'updated',
                'id' => $id,
                'type' => $element['type'] ?? '',
                'label' => $element['label'] ?? '',
                'params_diff' => adl_compute_text_diff($before, $after),
            ];
        }
    }

    foreach ($old_elements as $id => $element) {
        if (!isset($new_elements[$id])) {
            $changes[] = [
                'change' => 'removed',
                'id' => $id,
                'type' => $element['type'] ?? '',
                'label' => $element['label'] ?? '',
            ];
        }
    }

    return $changes;
}

function adl_flatten_builder_elements($meta) {
    $elements = [];
    $values = [];
    if (isset($meta['_fusion_builder_content'])) {
        $values[] = $meta['_fusion_builder_content'];
    }
    if (isset($meta['_fusion_builder_raw_content'])) {
        $values[] = $meta['_fusion_builder_raw_content'];
    }

    foreach ($values as $value) {
        $decoded = adl_normalize_builder_value($value);
        if (!is_array($decoded)) {
            continue;
        }
        adl_collect_builder_elements($decoded, $elements);
    }

    return $elements;
}

function adl_collect_builder_elements($data, &$elements) {
    if (!is_array($data)) {
        return;
    }
    foreach ($data as $value) {
        if (is_array($value)) {
            $type = adl_extract_builder_type($value);
            $id = adl_extract_builder_id($value);
            if ($id) {
                $label = '';
                if (isset($value['name']) && is_string($value['name'])) {
                    $label = $value['name'];
                } elseif (isset($value['title']) && is_string($value['title'])) {
                    $label = $value['title'];
                }
                $params = $value['params'] ?? $value['settings'] ?? $value;
                $elements[$id] = [
                    'id' => $id,
                    'type' => $type,
                    'label' => $label,
                    'raw_params' => adl_json_stringify($params),
                ];
            }
        }
        adl_collect_builder_elements($value, $elements);
    }
}

function adl_json_stringify($value) {
    if (is_string($value)) {
        return $value;
    }
    return wp_json_encode($value, JSON_UNESCAPED_SLASHES);
}

function adl_extract_builder_elements($meta) {
    $values = [];
    if (isset($meta['_fusion_builder_content'])) {
        $values[] = $meta['_fusion_builder_content'];
    }
    if (isset($meta['_fusion_builder_raw_content'])) {
        $values[] = $meta['_fusion_builder_raw_content'];
    }

    $types = [];
    $type_counts = [];
    $ids = [];

    foreach ($values as $value) {
        $decoded = adl_normalize_builder_value($value);
        if (!is_array($decoded)) {
            continue;
        }
        adl_walk_builder_tree($decoded, $types, $type_counts, $ids);
    }

    $types = array_values(array_unique($types));
    $ids = array_values(array_unique($ids));

    return [
        'types' => $types,
        'type_counts' => $type_counts,
        'ids' => $ids,
    ];
}

function adl_normalize_builder_value($value) {
    $value = maybe_unserialize($value);
    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
    }
    return $value;
}

function adl_walk_builder_tree($data, &$types, &$type_counts, &$ids) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $type = adl_extract_builder_type($value);
                if ($type) {
                    $types[] = $type;
                    if (!isset($type_counts[$type])) {
                        $type_counts[$type] = 0;
                    }
                    $type_counts[$type]++;
                }
                $id = adl_extract_builder_id($value);
                if ($id) {
                    $ids[] = $id;
                }
            }
            adl_walk_builder_tree($value, $types, $type_counts, $ids);
        }
    }
}

function adl_extract_builder_type($item) {
    $keys = ['type', 'element_type', 'component', 'element'];
    foreach ($keys as $key) {
        if (isset($item[$key]) && is_string($item[$key]) && $item[$key] !== '') {
            return $item[$key];
        }
    }
    return null;
}

function adl_extract_builder_id($item) {
    $keys = ['cid', 'id', 'uid', 'element_id'];
    foreach ($keys as $key) {
        if (isset($item[$key]) && (is_string($item[$key]) || is_numeric($item[$key]))) {
            return (string) $item[$key];
        }
    }
    return null;
}

function adl_build_summary($post_id, $new_data) {
    $keys = array_keys($new_data['meta']);
    $keys_str = empty($keys) ? 'content only' : 'meta: ' . implode(', ', $keys);
    return sprintf('Updated %s (%s)', get_the_title($post_id), $keys_str);
}

function adl_log_event($type, $summary, $extra = []) {
    $user = wp_get_current_user();
    $event = array_merge([
        'ts' => gmdate('c'),
        'site_id' => get_current_blog_id(),
        'type' => $type,
        'user_id' => (int) $user->ID,
        'user_login' => $user->user_login,
        'summary' => $summary,
    ], $extra);

    adl_write_event($event);
}

function adl_get_log_dir($site_id = null) {
    $site_id = is_null($site_id) ? get_current_blog_id() : (int) $site_id;
    $uploads = adl_get_upload_dir_for_site($site_id);
    return trailingslashit($uploads['basedir']) . ADL_LOG_DIR . '/site-' . $site_id;
}

function adl_get_upload_dir_for_site($site_id) {
    $site_id = (int) $site_id;
    $current = get_current_blog_id();
    if (is_multisite() && $site_id > 0 && $site_id !== $current) {
        switch_to_blog($site_id);
        $uploads = wp_upload_dir();
        restore_current_blog();
        return $uploads;
    }
    return wp_upload_dir();
}

function adl_write_event($event) {
    $site_id = (int) $event['site_id'];
    $dir = adl_get_log_dir($site_id);
    if (!wp_mkdir_p($dir)) {
        error_log('[AvadaDiff] Unable to create log directory: ' . $dir);
        return;
    }
    $filename = $dir . '/' . gmdate('Y-m-d') . '.ndjson';
    $line = wp_json_encode($event) . "\n";
    $fp = @fopen($filename, 'a');
    if (!$fp) {
        error_log('[AvadaDiff] Unable to open log file: ' . $filename);
        return;
    }
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

function adl_log_post_status_change($new_status, $old_status, $post) {
    if (!is_object($post)) return;
    if ($new_status === $old_status) return;
    $summary = sprintf('Status changed: %s → %s (%s)', $old_status, $new_status, get_the_title($post->ID));
    adl_log_event('post_status_change', $summary, [
        'post_id' => $post->ID,
        'post_type' => $post->post_type,
        'title' => get_the_title($post->ID),
        'old_status' => $old_status,
        'new_status' => $new_status,
    ]);
}

function adl_log_post_deleted($post_id) {
    $summary = sprintf('Deleted post #%d', $post_id);
    adl_log_event('post_deleted', $summary, ['post_id' => $post_id]);
}

function adl_log_post_trashed($post_id) {
    $summary = sprintf('Trashed post #%d', $post_id);
    adl_log_event('post_trashed', $summary, ['post_id' => $post_id]);
}

function adl_log_post_untrashed($post_id) {
    $summary = sprintf('Restored post #%d', $post_id);
    adl_log_event('post_untrashed', $summary, ['post_id' => $post_id]);
}

function adl_log_media_added($post_id) {
    $summary = sprintf('Media added #%d', $post_id);
    adl_log_event('media_added', $summary, ['attachment_id' => $post_id]);
}

function adl_log_media_edited($post_id) {
    $summary = sprintf('Media edited #%d', $post_id);
    adl_log_event('media_edited', $summary, ['attachment_id' => $post_id]);
}

function adl_log_media_deleted($post_id) {
    $summary = sprintf('Media deleted #%d', $post_id);
    adl_log_event('media_deleted', $summary, ['attachment_id' => $post_id]);
}

function adl_log_comment_added($comment_id, $comment) {
    $summary = sprintf('Comment added #%d', $comment_id);
    adl_log_event('comment_added', $summary, [
        'comment_id' => $comment_id,
        'post_id' => (int) $comment->comment_post_ID,
        'status' => $comment->comment_approved,
    ]);
}

function adl_log_comment_status($comment_id, $status) {
    $summary = sprintf('Comment status changed #%d (%s)', $comment_id, $status);
    adl_log_event('comment_status', $summary, [
        'comment_id' => $comment_id,
        'status' => $status,
    ]);
}

function adl_log_theme_switch($new_name, $new_theme, $old_theme) {
    $summary = sprintf('Theme switched to %s', $new_name);
    adl_log_event('theme_switch', $summary, [
        'new_theme' => $new_name,
        'old_theme' => is_object($old_theme) ? $old_theme->get('Name') : '',
    ]);
}

function adl_log_plugin_activated($plugin, $network_wide) {
    $summary = sprintf('Plugin activated: %s', $plugin);
    adl_log_event('plugin_activated', $summary, [
        'plugin' => $plugin,
        'network_wide' => (bool) $network_wide,
    ]);
}

function adl_log_plugin_deactivated($plugin, $network_wide) {
    $summary = sprintf('Plugin deactivated: %s', $plugin);
    adl_log_event('plugin_deactivated', $summary, [
        'plugin' => $plugin,
        'network_wide' => (bool) $network_wide,
    ]);
}

function adl_log_menu_updated($menu_id, $menu_data) {
    $summary = sprintf('Menu updated #%d', $menu_id);
    adl_log_event('menu_updated', $summary, [
        'menu_id' => $menu_id,
        'menu_name' => is_array($menu_data) && isset($menu_data['menu-name']) ? $menu_data['menu-name'] : '',
    ]);
}

function adl_log_user_created($user_id) {
    $summary = sprintf('User created #%d', $user_id);
    adl_log_event('user_created', $summary, ['user_id' => $user_id]);
}

function adl_log_user_deleted($user_id) {
    $summary = sprintf('User deleted #%d', $user_id);
    adl_log_event('user_deleted', $summary, ['user_id' => $user_id]);
}

function adl_log_user_updated($user_id, $old_user_data) {
    $summary = sprintf('User updated #%d', $user_id);
    adl_log_event('user_updated', $summary, ['user_id' => $user_id]);
}

function adl_should_skip_option($option) {
    foreach (ADL_OPTION_SKIP_PREFIXES as $prefix) {
        if (strpos($option, $prefix) === 0) {
            return true;
        }
    }
    return false;
}

function adl_log_option_updated($option, $old_value, $value) {
    if (!ADL_LOG_OPTIONS) return;
    if (adl_should_skip_option($option)) return;
    $summary = sprintf('Option updated: %s', $option);
    adl_log_event('option_updated', $summary, ['option' => $option]);
}

function adl_log_option_added($option, $value) {
    if (!ADL_LOG_OPTIONS) return;
    if (adl_should_skip_option($option)) return;
    $summary = sprintf('Option added: %s', $option);
    adl_log_event('option_added', $summary, ['option' => $option]);
}

function adl_log_option_deleted($option) {
    if (!ADL_LOG_OPTIONS) return;
    if (adl_should_skip_option($option)) return;
    $summary = sprintf('Option deleted: %s', $option);
    adl_log_event('option_deleted', $summary, ['option' => $option]);
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
    $start_date = gmdate('Y-m-d', strtotime('-' . ADL_LOG_RETENTION_DAYS . ' days'));
    $end_date = $today;
    $type = '';
    $site_id = 0;

    $events = adl_query_events($start_date, $end_date, $type, $site_id);
    adl_handle_csv_export($events, $start_date, $end_date, $type, $site_id);

    echo '<div class="wrap">';
    echo '<h1>Activity Monitor</h1>';
    echo '<p>Showing all activity within the configured retention window (' . esc_html(ADL_LOG_RETENTION_DAYS) . ' days).</p>';

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
            $item = adl_render_item($event);
            echo '<tr>';
            echo '<td>' . esc_html($event['ts'] ?? '') . '</td>';
            echo '<td>' . esc_html($event['site_id'] ?? '') . '</td>';
            echo '<td>' . esc_html($event['type'] ?? '') . '</td>';
            echo '<td>' . esc_html($event['user_login'] ?? '') . ' (' . esc_html($event['user_id'] ?? '') . ')</td>';
            echo '<td>' . esc_html($item) . '</td>';
            echo '<td>' . esc_html($event['summary'] ?? '') . '</td>';
            echo '<td><details><summary>JSON</summary><pre style="white-space: pre-wrap; max-width: 600px;">' . esc_html($json) . '</pre></details></td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    echo '</div>';
}

function adl_render_item($event) {
    $title = isset($event['title']) ? $event['title'] : '';
    $post_id = isset($event['post_id']) ? $event['post_id'] : '';
    if ($title !== '' || $post_id !== '') {
        $suffix = $post_id !== '' ? ' (#' . $post_id . ')' : '';
        return $title . $suffix;
    }
    if (isset($event['attachment_id'])) {
        return 'Attachment #' . $event['attachment_id'];
    }
    if (isset($event['plugin'])) {
        return 'Plugin: ' . $event['plugin'];
    }
    if (isset($event['option'])) {
        return 'Option: ' . $event['option'];
    }
    if (isset($event['menu_id'])) {
        return 'Menu #' . $event['menu_id'];
    }
    if (isset($event['comment_id'])) {
        return 'Comment #' . $event['comment_id'];
    }
    return '';
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
    $cutoff = strtotime('-' . ADL_LOG_RETENTION_DAYS . ' days');
    $sites = get_sites(['fields' => 'ids']);
    $site_ids = is_array($sites) ? $sites : [];
    foreach ($site_ids as $sid) {
        $dir = adl_get_log_dir($sid);
        if (!is_dir($dir)) {
            continue;
        }
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
?>
