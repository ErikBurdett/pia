<?php
/**
 * MU Plugin Loader: PIA Candidates (MU)
 *
 * WordPress only auto-loads MU plugins placed directly in wp-content/mu-plugins/.
 * This file loads the actual plugin from the pia-candidates-mu/ subfolder.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/pia-candidates-mu/pia-candidates-mu.php';

