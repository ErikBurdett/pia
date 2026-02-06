<?php

use Codexonics\PrimeMoverFramework\classes\PrimeMover;
use Codexonics\PrimeMoverFramework\app\PrimeMoverControlPanel;
use Codexonics\PrimeMoverFramework\app\PrimeMoverSettings;
use Codexonics\PrimeMoverFramework\extensions\PrimeMoverGamiPressCompat;
use Codexonics\PrimeMoverFramework\extensions\PrimeMoverEDDCompat;
use Codexonics\PrimeMoverFramework\extensions\PrimeMoverWpFusion;
use Codexonics\PrimeMoverFramework\extensions\PrimeMoverLearnDash;
use Codexonics\PrimeMoverFramework\extensions\PrimeMoverRelevanssi;
use Codexonics\PrimeMoverFramework\extensions\PrimeMoverAutoUserAdjustment;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverBackupManagement;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverDeleteUtilities;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverBackupDirectorySize;
use Codexonics\PrimeMoverFramework\advance\PrimeMoverTroubleshooting;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverTroubleshootingMarkup;
use Codexonics\PrimeMoverFramework\advance\PrimeMoverUploadSettings;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverUploadSettingMarkup;
use Codexonics\PrimeMoverFramework\app\PrimeMoverReset;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverPanelValidationUtilities;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayCustomDirSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayExcludedPluginsSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayExcludedUploadSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayMaintenanceSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplaySecuritySettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayDropBoxSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayEncryptionSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayGDriveSettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplaySettings;
use Codexonics\PrimeMoverFramework\app\PrimeMoverDisplayRunTimeSettings;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverAutoBackupSetting;
use Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsConfig;
use Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsTemplate;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsHelper;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverToolBox;
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( defined( 'PRIME_MOVER_PANEL_PLUGINPATH' ) ) {
    return;
}
define( 'PRIME_MOVER_PANEL_PLUGINPATH', plugin_dir_path( __FILE__ ) );
define( 'PRIME_MOVER_PANEL_VERSION', '2.1.1' );
define( 'PRIME_MOVER_PANEL_MAINPLUGIN_FILE', __FILE__ );
define( 'PRIME_MOVER_PANEL_PLUGINBASENAME', plugin_basename( PRIME_MOVER_PANEL_MAINPLUGIN_FILE ) );
if ( !defined( 'PRIME_MOVER_DROPBOX_UPLOAD_CHUNK' ) ) {
    define( 'PRIME_MOVER_DROPBOX_UPLOAD_CHUNK', 1048576 );
}
if ( !defined( 'PRIME_MOVER_GDRIVE_UPLOAD_CHUNK' ) ) {
    define( 'PRIME_MOVER_GDRIVE_UPLOAD_CHUNK', 1048576 );
}
if ( !defined( 'PRIME_MOVER_GDRIVE_DOWNLOAD_CHUNK' ) ) {
    define( 'PRIME_MOVER_GDRIVE_DOWNLOAD_CHUNK', 1048576 );
}
include PRIME_MOVER_PANEL_PLUGINPATH . '/PrimeMoverPanelLoader.php';
add_action(
    'prime_mover_load_module_apps',
    'loadPrimeMoverControlPanel',
    10,
    2
);
function loadPrimeMoverControlPanel(  PrimeMover $prime_mover, array $utilities  ) {
    if ( empty( $utilities['freemius_integration'] ) ) {
        return;
    }
    $freemius_integration = $utilities['freemius_integration'];
    $system_authorization = $prime_mover->getSystemAuthorization();
    $prime_mover_settings_config = new PrimeMoverSettingsConfig($prime_mover, $utilities);
    $prime_mover_settings_config->initHooks();
    $settings_markup = new PrimeMoverSettingsMarkups($prime_mover, $utilities);
    $prime_mover_settings = new PrimeMoverSettings(
        $prime_mover,
        $system_authorization,
        $utilities,
        $settings_markup
    );
    $prime_mover_settings->initHooks();
    $settings_helper = new PrimeMoverSettingsHelper(
        $prime_mover,
        $utilities,
        $prime_mover_settings,
        $prime_mover_settings_config
    );
    $prime_mover_settings_template = new PrimeMoverSettingsTemplate(
        $prime_mover,
        $utilities,
        $settings_markup,
        $prime_mover_settings,
        $prime_mover_settings_config
    );
    $delete_utilities = new PrimeMoverDeleteUtilities(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings
    );
    $backupdir_size = new PrimeMoverBackupDirectorySize(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings
    );
    $autobackup_setting = new PrimeMoverAutoBackupSetting($prime_mover_settings_template);
    $autobackup_setting->initHooks();
    $autobackup_setting->maybeResetAutoBackupInitialization();
    $toolbox = new PrimeMoverToolBox($autobackup_setting);
    $toolbox->initHooks();
    $backup_management = new PrimeMoverBackupManagement(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings,
        $delete_utilities,
        $backupdir_size,
        $settings_helper
    );
    $backup_management->initHooks();
    $prime_mover_custom_dir_settings = new PrimeMoverDisplayCustomDirSettings($prime_mover_settings);
    $prime_mover_excludedplugin_settings = new PrimeMoverDisplayExcludedPluginsSettings($prime_mover_settings_template);
    $prime_mover_excludeduploads_settings = new PrimeMoverDisplayExcludedUploadSettings($prime_mover_settings);
    $prime_mover_display_maintenance_settings = new PrimeMoverDisplayMaintenanceSettings($prime_mover_settings);
    $prime_mover_display_security_settings = new PrimeMoverDisplaySecuritySettings($prime_mover_settings);
    $prime_mover_display_dropbox_settings = new PrimeMoverDisplayDropBoxSettings($prime_mover_settings);
    $prime_mover_display_encryption_settings = new PrimeMoverDisplayEncryptionSettings($prime_mover_settings);
    $prime_mover_display_gdrive_settings = new PrimeMoverDisplayGDriveSettings($prime_mover_settings);
    $prime_mover_runtime_setings = new PrimeMoverDisplayRunTimeSettings($prime_mover_settings);
    $prime_mover_runtime_setings->initHooks();
    $prime_mover_display_settings = new PrimeMoverDisplaySettings(
        $prime_mover_custom_dir_settings,
        $prime_mover_excludedplugin_settings,
        $prime_mover_excludeduploads_settings,
        $prime_mover_display_maintenance_settings,
        $prime_mover_display_security_settings,
        $prime_mover_display_dropbox_settings,
        $prime_mover_display_encryption_settings,
        $prime_mover_display_gdrive_settings,
        $prime_mover_runtime_setings
    );
    $prime_mover_display_settings->initHooks();
    $troubleshooting_markup = new PrimeMoverTroubleshootingMarkup(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings_template
    );
    $troubleshooting = new PrimeMoverTroubleshooting(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings,
        $troubleshooting_markup
    );
    $troubleshooting->initHooks();
    $upload_setting_markup = new PrimeMoverUploadSettingMarkup(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings
    );
    $upload_settings = new PrimeMoverUploadSettings(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings,
        $upload_setting_markup
    );
    $upload_settings->initHooks();
    $reset_setting = new PrimeMoverReset(
        $prime_mover,
        $system_authorization,
        $utilities,
        $prime_mover_settings
    );
    $reset_setting->initHooks();
    $validation_utilities = new PrimeMoverPanelValidationUtilities(
        $prime_mover,
        $utilities,
        $troubleshooting,
        $autobackup_setting
    );
    $validation_utilities->initHooks();
    $prime_mover_panel = new PrimeMoverControlPanel($prime_mover, $system_authorization, $utilities);
    $prime_mover_panel->initHooks();
    $panel_resources = [
        'prime_mover'          => $prime_mover,
        'authorization'        => $system_authorization,
        'freemius_integration' => $freemius_integration,
        'utilities'            => $utilities,
        'settings'             => $prime_mover_settings,
        'backup_management'    => $backup_management,
        'settings_config'      => $prime_mover_settings_config,
        'settings_helper'      => $settings_helper,
    ];
    $gamipress_compat = new PrimeMoverGamiPressCompat($prime_mover, $utilities);
    $gamipress_compat->initHooks();
    $wp_fusion_compat = new PrimeMoverWpFusion($prime_mover, $utilities);
    $wp_fusion_compat->initHooks();
    $learndash_compat = new PrimeMoverLearnDash($prime_mover, $utilities);
    $learndash_compat->initHooks();
    $relevanssi_compat = new PrimeMoverRelevanssi($prime_mover, $utilities);
    $relevanssi_compat->initHooks();
    $edd_compat = new PrimeMoverEDDCompat($prime_mover, $utilities);
    $edd_compat->initHooks();
    $auto_user_adj = new PrimeMoverAutoUserAdjustment($prime_mover, $autobackup_setting, $utilities);
    $auto_user_adj->initHooks();
    do_action( 'prime_mover_panel_loaded', $panel_resources );
}
