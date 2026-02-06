<?php
/**
 * Plugin Name: AddEvent Calendar
 * Description: The AddEvent plugin allows you to seamlessly integrate a fully customizable calendar into your WordPress site. It provides a user-friendly interface where you can display an interactive calendar block that can be easily configured with a unique calendar ID to show events, schedules, and other important dates.
 * Version: 1.4
 * Author: addeventinc
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: addevent
 * Tested up to: 6.7
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function addevent_register_block() {
    wp_register_script(
        'addevent-calendar-block',
        plugins_url('block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        '1.2',  // Static version number
        true    // Load the script in the footer
    );

    wp_register_style(
        'addevent-calendar-style',
        plugins_url('style.css', __FILE__),
        array(),
        '1.2',  // Static version number for the style
        true
    );

    register_block_type('addevent-calendar/block', array(
        'editor_script' => 'addevent-calendar-block',
        'style'         => 'addevent-calendar-style',
        'editor_style'  => 'addevent-calendar-style',
    ));
}
add_action('init', 'addevent_register_block');

function addevent_enqueue_scripts() {
    if ( ! is_admin() ) {
        wp_enqueue_script(
            'addevent-calendar-js',
            'https://cdn.addevent.com/libs/cal/js/cal.embed.t1.init.js',
            array(),
            '1.3',  // No version since it's an external script
            true   // Load in the footer
        );
    }
}
add_action('wp_enqueue_scripts', 'addevent_enqueue_scripts');
add_action('enqueue_block_editor_assets', 'addevent_enqueue_scripts');

function addevent_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'addevent_calendar_events'; // Updated prefix
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_name text NOT NULL,
        event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'addevent_create_table');
