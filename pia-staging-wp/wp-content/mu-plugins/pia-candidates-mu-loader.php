<?php
/**
 * Plugin Name: PIA Candidates MU Loader
 * Description: Loads the PIA Candidates MU plugin from its directory.
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin_file = __DIR__ . '/pia-candidates-mu/pia-candidates-mu.php';
if (file_exists($plugin_file)) {
    require_once $plugin_file;
}
