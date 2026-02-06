<?php
namespace Codexonics\PrimeMoverFramework\app;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Codexonics\PrimeMoverFramework\classes\PrimeMover;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover master settings config
 *
 */
class PrimeMoverSettingsConfig
{
    private $master_settings_config;
    private $non_js_config;
    private $prime_mover;
    private $timeout_options;
    
    /**
     * Constructor
     */
    public function __construct(PrimeMover $prime_mover, $utilities = []) 
    {        
        $this->prime_mover = $prime_mover;
        $this->master_settings_config = [
            
            'autobackup_global_status' => 
              [
                  "button_selector" => '#js-save-prime-mover-automatic-backup',
                  "spinner_selector" => '.js-save-prime-mover-automatic-backup-spinner',
                  "data_selector" => '#js-prime_mover_automatic_backup_checkbox',
                  "ajax_action" => 'prime_mover_save_automatic_backup_settings',
                  "ajax_key" => 'automatic_backup_enabled',
                  "datatype" => "checkbox",
                  "dialog" => false,
                  "encrypted" => false,
                  "nonce" => "prime_mover_save_automatic_backup_settings_nonce",
                  "description" => __('Automatic backup', 'prime-mover'),
                  "on_status" => $this->getUserFriendEnabling(),
                  "off_status" => __('off', 'prime-mover'),
                  "purpose" => __('This is a global setting for enabling/disabling automatic backup feature.', 'prime-mover'),
                  "validation_id" => 'settings_checkbox_validation',
                  "show_as_required" => false,
                  "documentation" => 'https://codexonics.com/prime_mover/prime-mover/how-to-enable-automatic-backup-for-wordpress-multisite-and-single-site/'
              ], 
        
            'autobackup_multisite_level_status' => 
              [
                  "button_selector" => "#js-save-prime-mover-automatic-backup-status-subsite",
                  "spinner_selector" => ".js-save-prime-mover-automatic-backup-status-subsite-spinner",
                  "data_selector" => "#js-prime_mover_automatic_backup-status-subsite_checkbox",
                  "ajax_action" => "prime_mover_save_automatic_backup_subsite_status",
                  "ajax_key" => "automatic_backup_subsite_enabled",
                  "datatype" => "checkbox",
                  "dialog" => false,
                  "encrypted" => false,
                  "nonce" => "prime_mover_save_automatic_backup_subsite_status_nonce",
                  "description" => __('Automatic backup', 'prime-mover'),
                  "on_status" => __('enabled', 'prime-mover'),
                  "off_status" => __('off', 'prime-mover'),
                  "purpose" => __('This is a subsite specific setting for enabling/disabling automatic backup feature.', 'prime-mover'),
                  "validation_id" => 'settings_checkbox_validation',
                  "show_as_required" => true, 
                  "documentation" => 'https://codexonics.com/prime_mover/prime-mover/how-to-enable-automatic-backup-for-wordpress-multisite-and-single-site/'
              ],       
            
            'autobackup_export_options' =>
            [
                  "button_selector" => "#js-save-prime-mover-automatic-backup-export-options",
                  "spinner_selector" => ".js-save-prime-mover-automatic-backup-export-options-spinner",
                  "data_selector" => ".js-prime_mover_automatic-backup-export-options_radio",
                  "ajax_action" => "prime_mover_save_automatic_backup_export_options",
                  "ajax_key" => "automatic_backup_export_options",
                  "datatype" => "radio",
                  "dialog" => false,
                  "encrypted" => false,
                  "nonce" => "prime_mover_save_automatic_backup_export_options_nonce",
                  "description" => __('Automatic backup export options', 'prime-mover'),
                  "on_status" => __('saved', 'prime-mover'),
                  "off_status" => __('off', 'prime-mover'),
                  "purpose" => __('This is the automatic backup export option on a per site basis.', 'prime-mover'),
                  "validation_id" => 'settings_radio_validation',
                  'default_value' => '',
                  'return_default_if_no_key' => false,
                  "show_as_required" => true,
                  "documentation" => 'https://codexonics.com/prime_mover/prime-mover/understanding-prime-mover-backup-options/'
            ],
            
            'autobackup_schedule' =>
            [
                "button_selector" => "#js-save-prime-mover-automatic-backup-schedule",
                "spinner_selector" => ".js-save-prime-mover-automatic-backup-schedule-spinner",
                "data_selector" => "#js-prime_mover_automatic-backup-schedule-select",
                "ajax_action" => "prime_mover_save_automatic_backup_schedule",
                "ajax_key" => "automatic_backup_export_schedule",
                "datatype" => "select",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_automatic_backup_schedule_nonce",
                "description" => __('Automatic backup export schedule', 'prime-mover'),
                "on_status" => __('saved', 'prime-mover'),
                "off_status" => __('updated', 'prime-mover'),
                "purpose" => __('This is the automatic backup export schedule on a per site basis.', 'prime-mover'),
                "validation_id" => 'settings_autobackup_schedule_validation',
                "show_as_required" => true,
                "documentation" => 'https://codexonics.com/prime_mover/prime-mover/how-to-add-custom-backup-schedules-for-automatic-backup/'
            ],
            
            'autobackup_encryption_status' =>
            [
                "button_selector" => "#js-save-prime-mover-automatic-backup-enc-status",
                "spinner_selector" => ".js-save-prime-mover-automatic-backup-enc-status-spinner",
                "data_selector" => "#js-prime_mover_automatic_backup-enc-status-checkbox",
                "ajax_action" => "prime_mover_save_automatic_backup_encryption_status",
                "ajax_key" => "automatic_backup_subsite_encryption_enabled",
                "datatype" => "checkbox",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_automatic_backup_enc_status_nonce",
                "description" => __('Automatic backup encryption status', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Check this box if you want backups to be encrypted. This will encrypt the database, user data, media files, plugins, and themes.', 'prime-mover'),
                "validation_id" => 'settings_checkbox_validation',
                "show_as_required" => false,
                "documentation" => 'https://codexonics.com/prime_mover/prime-mover/how-to-enable-encryption-support-in-prime-mover/'
            ], 
            
            'autobackup_backup_to_dropbox' =>
            [
                "button_selector" => "#js-save-prime-mover-autobackup-to-dropbox",
                "spinner_selector" => ".js-save-prime-mover-autobackup-to-dropbox-spinner",
                "data_selector" => "#js-prime_mover_automatic_backup-dropbox-checkbox",
                "ajax_action" => "prime_mover_save_automatic_backup_dropbox_status",
                "ajax_key" => "automatic_backup_dropbox_upload_enabled",
                "datatype" => "checkbox",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_automatic_backup_dropbox_nonce",
                "description" => __('Automatic backup upload to Dropbox cloud', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This is automatic backup setting for Dropbox cloud storage.', 'prime-mover'),
                "validation_id" => 'settings_checkbox_validation',
                "show_as_required" => false,
                "documentation" => 'https://codexonics.com/prime_mover/prime-mover/prime-mover-dropbox-integration/'
            ],
            
            'autobackup_backup_to_gdrive' =>
            [
                "button_selector" => "#js-save-prime-mover-autobackup-to-gdrive",
                "spinner_selector" => ".js-save-prime-mover-autobackup-to-gdrive-spinner",
                "data_selector" => "#js-prime_mover_automatic_backup-gdrive-checkbox",
                "ajax_action" => "prime_mover_save_automatic_backup_gdrive_status",
                "ajax_key" => "automatic_backup_gdrive_upload_enabled",
                "datatype" => "checkbox",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_automatic_backup_gdrive_nonce",
                "description" => __('Automatic backup upload to Gdrive cloud', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This is automatic backup setting for Gdrive cloud storage.', 'prime-mover'),
                "validation_id" => 'settings_checkbox_validation',
                "show_as_required" => false,
                "documentation" => 'https://codexonics.com/prime_mover/prime-mover/prime-mover-pro-google-drive-api-integration/'
            ],
            
            'autobackup_db_maintenance_mode' =>
            [
                "button_selector" => "#js-save-prime-mover-automatic-backup-db-maintenance",
                "spinner_selector" => ".js-save-prime-mover-automatic-backup-db-maintenance-spinner",
                "data_selector" => "#js-prime_mover_automatic_backup-db-maintenance_checkbox",
                "ajax_action" => "prime_mover_save_automatic_backup_db_maintenance",
                "ajax_key" => "automatic_backup_db_maintenance_enabled",
                "datatype" => "checkbox",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_automatic_backup_db_maintenance_nonce",
                "description" => __('Maintenance mode', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('If this is checked - it will enable maintenance mode when exporting or dumping the database.', 'prime-mover'),
                "validation_id" => 'settings_checkbox_validation',
                "show_as_required" => false,
                "documentation" => ''
            ], 
            
            'autobackup_timeout_options' =>
            [
                "button_selector" => "#js-save-prime-mover-automatic-backup-timeout-options",
                "spinner_selector" => ".js-save-prime-mover-automatic-backup-timeout-spinner",
                "data_selector" => ".js-prime_mover_automatic-backup-timeout_radio",
                "ajax_action" => "prime_mover_save_automatic_backup_timeout_options",
                "ajax_key" => "automatic_backup_timeout_options",
                "datatype" => "radio",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_automatic_backup_timeout_options_nonce",
                "description" => __('Automatic backup timeout options', 'prime-mover'),
                "on_status" => __('saved', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This is the automatic backup timeout option on a per site basis.', 'prime-mover'),
                "validation_id" => 'settings_radio_validation',
                'default_value' => 50,
                'return_default_if_no_key' => true,
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'autobackup_download_log' =>
            [
                "button_selector" => "#js-save-prime-mover-automatic-backup-download-log",
                "spinner_selector" => ".js-save-prime-mover-automatic-backup-download-log-spinner",
                "data_selector" => "#js-prime_mover_automatic_backup-download_button",
                "ajax_action" => "prime_mover_automatic_backup_download_log",
                "ajax_key" => "automatic_backup_download_log",
                "datatype" => "buttonform",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_automatic_backup_download_log_nonce",
                "description" => __('Download log file', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Download auto backup log', 'prime-mover'),
                "validation_id" => 'settings_buttonform_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'clear_autobackup_log' =>
            [                
                "button_selector" => "#js-clear-prime-mover-autobackup-log",
                "spinner_selector" => ".js-save-prime-mover-clear-log-spinner",
                "data_selector" => "#js-prime_mover_clear_automatic_backup_log_button",
                "ajax_action" => "prime_mover_clear_automatic_backup_log",
                "ajax_key" => "clear_automatic_backup_log",
                "datatype" => "buttonform",
                "dialog" => true,
                "dialog_selector" => '#js-prime-mover-clear-autobackup-log',
                "encrypted" => false,                
                "nonce" => "prime_mover_clear_automatic_backup_log_nonce",
                "description" => __('Clear auto backup log file', 'prime-mover'),
                "dialog_button_text" => __('Yes', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Clear auto backup log', 'prime-mover'),
                "validation_id" => 'settings_buttonform_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'autobackup_runtime_error_log' =>
            [
                "button_selector" => "#js-autobackup-runtime-error-log",
                "spinner_selector" => ".js-autobackup-runtime_error-log-spinner",
                "data_selector" => "#js-autobackup-runtime-error-log-button",
                "ajax_action" => "prime_mover_automatic_runtime_error_log",
                "ajax_key" => "automatic_backup_runtime_error_log",
                "datatype" => "buttonform",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_automatic_backup_runtime_error_nonce",
                "description" => __('Download error log', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Download error log', 'prime-mover'),
                "validation_id" => 'settings_buttonform_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'clear_runtime_error_log' =>
            [                
                "button_selector" => "#js-clear-runtime-error-log",
                "spinner_selector" => ".js-clear-runtime-error-log-spinner",
                "data_selector" => "#js-clear_runtime_error_log_button",
                "ajax_action" => "prime_mover_clear_runtime_error_log",
                "ajax_key" => "clear_runtime_error_log_key",
                "datatype" => "buttonform",
                "dialog" => true,
                "dialog_selector" => '#js-prime-mover-clear-runtime_error-log',
                "encrypted" => false,
                "nonce" => "prime_mover_clear_runtime_error_log_nonce",
                "description" => __('Clear auto backup error log', 'prime-mover'),
                "dialog_button_text" => __('Yes', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Clear auto backup error log', 'prime-mover'),
                "validation_id" => 'settings_buttonform_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'clear_autobackup_init_meta' =>
            [                
                "button_selector" => "#js-clear-autobackup_init_meta",
                "spinner_selector" => ".js-clear-autobackup_init_meta-spinner",
                "data_selector" => "#js-clear_autobackup_init_meta_button",
                "ajax_action" => "prime_mover_clear_autobackup_init_meta",
                "ajax_key" => "clear_autobackup_init_meta_key",
                "datatype" => "buttonform",
                "dialog" => true,
                "dialog_selector" => '#js-prime-mover-clear-autobackup-init-meta',
                "encrypted" => false,
                "nonce" => "prime_mover_clear_autobackup_init_meta_nonce",
                "description" => __('Clear auto backup initialization', 'prime-mover'),
                "dialog_button_text" => __('Yes', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Clear auto backup initialization', 'prime-mover'),
                "validation_id" => 'settings_buttonform_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'clear_locked_settings' =>
            [                
                "button_selector" => "#js-clear-prime-mover-locked-settings",
                "spinner_selector" => ".js-save-prime-mover-locked-settings-spinner",
                "data_selector" => "#js-prime_mover_clear_locked_settings_button",
                "ajax_action" => "prime_mover_clear_locked_settings",
                "ajax_key" => "clear_locked_settings",
                "datatype" => "buttonform",
                "dialog" => true,
                "dialog_selector" => '#js-prime-mover-clear-locked-settings',
                "encrypted" => false,
                "nonce" => "prime_mover_clear_locked_settings_nonce",
                "description" => __('Clear locked settings', 'prime-mover'),
                "dialog_button_text" => __('Yes', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('Clear locked settings', 'prime-mover'),
                "validation_id" => 'settings_buttonform_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'non_user_id_adjustment' =>
            [
                "button_selector" => "#js-save-prime-mover-non-user-id-adjustment",
                "spinner_selector" => ".js-save-prime-mover-non-user-id-adjustment-spinner",
                "data_selector" => "#js-prime_mover_non_user_adjustment_textarea",
                "ajax_action" => "prime_mover_save_non_user_adjustment",
                "ajax_key" => "non_user_column_id_adjustment",
                "datatype" => "text_area_data",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_non_user_column_adjustment_nonce",
                "description" => __('Non-user_id column auto-adjustment', 'prime-mover'),
                "on_status" => __('saved', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This is the non user_id column adjustment on a per site basis.', 'prime-mover'),
                "validation_id" => 'settings_textarea_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'disable_user_diff' =>
            [
                "button_selector" => '#js-save-prime-mover-disable-user-diff',
                "spinner_selector" => '.js-save-prime-mover-disable-user-diff-spinner',
                "data_selector" => '#js-prime_mover_disable_user_diff_checkbox',
                "ajax_action" => 'prime_mover_save_disable_user_diff_settings',
                "ajax_key" => 'disable_user_diff',
                "datatype" => "checkbox",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_save_disable_user_diff_settings_nonce",
                "description" => __('User diff', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This is a global setting for enabling/disabling user diff feature.', 'prime-mover'),
                "validation_id" => 'settings_checkbox_validation',
                "show_as_required" => false,
                "documentation" => ''
            ],
            
            'excluded-plugins' =>
            [
                "button_selector" => '#js-save-prime-mover-excluded-plugins',
                "spinner_selector" => '.js-save-prime-mover-excluded-plugins-spinner',
                "data_selector" => '#js-prime-mover-excluded-plugins',
                "ajax_action" => 'prime_mover_excluded_plugins',
                "ajax_key" => 'text_area_data',
                "datatype" => "checkboxes",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_excluded_plugins_nonce",
                "description" => __('Excluded plugins', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This is a global setting for excluding plugins during the export process.', 'prime-mover'),
                "validation_id" => '',
                "show_as_required" => false,
                "documentation" => 'https://codexonics.com/prime_mover/prime-mover/how-to-exclude-plugins-in-prime-mover-pro/'
            ],
            
            'excluded-tables' =>
            [
                "button_selector" => '#js-save-prime-mover-excluded-tables',
                "spinner_selector" => '.js-save-prime-mover-excluded-tables-spinner',
                "data_selector" => '#js-prime-mover-excluded-tables',
                "ajax_action" => 'prime_mover_excluded_tables',
                "ajax_key" => 'text_area_data',
                "datatype" => "checkboxes",
                "dialog" => false,
                "encrypted" => false,
                "nonce" => "prime_mover_excluded_tables_nonce",
                "description" => __('Excluded tables', 'prime-mover'),
                "on_status" => __('enabled', 'prime-mover'),
                "off_status" => __('off', 'prime-mover'),
                "purpose" => __('This setting excludes database tables for a specific site during the export process.', 'prime-mover'),
                "validation_id" => '',
                "show_as_required" => false,
                "documentation" => 'https://codexonics.com/prime_mover/prime-mover/how-do-we-exclude-database-tables-in-prime-mover-export/'
            ],
            
        ];
        
        $this->non_js_config = ["encrypted", "nonce", "description", "on_status", "off_status", "purpose", "validation_id", "show_as_required", "documentation", "default_value", "return_default_if_no_key"];
        
        $this->timeout_options = [
            
            20  => [
                'timeout_desc' => __('20 seconds - recommended for hosting with server timeout below 60 seconds.', 'prime-mover'),
                'progress_interval' => 'prime_mover_every_sixty_seconds',
                'progress_interval_val' => 60,
                'progress_interval_desc' => esc_html__('Every sixty seconds', 'prime-mover')
            ],
           
            50  => [
                'timeout_desc' => __('50 seconds - optimal for hosting with server timeout below 120 seconds.', 'prime-mover'),
                'progress_interval' => 'prime_mover_every_ninety_seconds',
                'progress_interval_val' => 90,
                'progress_interval_desc' => esc_html__('Every ninety seconds', 'prime-mover')
            ],
           
            110  =>  [
                'timeout_desc' => __('110 seconds - suggested for hosting with server timeout below 180 seconds.', 'prime-mover'),
                'progress_interval' => 'prime_mover_every_one_hundred_fifty_seconds',
                'progress_interval_val' => 150,
                'progress_interval_desc' => __('Every one hundred fifty seconds', 'prime-mover')
            ],
            
            170 => [
                'timeout_desc' => __('170 seconds - best for hosting with server timeout below 240 seconds or no server timeout.', 'prime-mover'),
                'progress_interval' => 'prime_mover_every_two_hundred_ten_seconds',
                'progress_interval_val' => 210,
                'progress_interval_desc' => __('Every two hundred ten seconds', 'prime-mover')
            ]
        ];       
    }

    /**
     * Get progress interval schedules
     * @return array
     */
    public function getProgressIntervalSchedules()
    {
        $array = $this->getTimeOutOptions();
        $prog_interval = wp_list_pluck($array, 'progress_interval_val', 'progress_interval');
        $prog_interval_desc = wp_list_pluck($array, 'progress_interval_desc', 'progress_interval');
        $sched = [];
        
        foreach ($prog_interval as $k => $v) {
            $sched[$k]['interval'] = $v;
            $sched[$k]['display'] = $prog_interval_desc[$k];
        }
        
        return $sched;
    }
    
    /**
     * Get timeout options
     * @return number[]
     */
    public function getTimeOutOptions()
    {
        return $this->timeout_options;
    }
    
    /**
     * Get autobackup user friendly enabling message
     * @return string
     */
    protected function getUserFriendEnabling()
    {
        $settings_page_url = '<a href="' . $this->getPrimeMover()->getSystemFunctions()->getScheduledBackupSettingsUrl() . '">' . esc_html__('Scheduled backup', 'prime-mover') . '</a>';
        $sprintf = sprintf(esc_html__('%s - Please configure site-specific backup settings via %s', 'prime-mover'), '<strong>' . esc_html__('enabled', 'prime-mover') . '</strong>', $settings_page_url);
        
        return $sprintf;
    }
    
    /**
     * Get Prime Mover instance
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
    
    /**
     * Initialize hooks
     */
    public function initHooks()
    {
        add_filter('prime_mover_settings_js_api_extension', [$this, 'renderJsApiExtensionData'], 10, 1);
        add_filter('prime_mover_register_setting', [$this, 'registerSetting'], 10, 1);
    }
    
    /**
     * Register setting
     * @param array $settings
     * @return boolean[]|string[][]|boolean[][]
     */
    public function registerSetting($settings = [])
    {
        foreach ($this->getMasterSettingsConfig() as $master_config) {
            $key = '';
            if (isset($master_config['ajax_key'])) {
                $key = $master_config['ajax_key'];
            }
            $encrypted = false;
            if (isset($master_config['encrypted'])) {
                $encrypted = $master_config['encrypted'];
            }
            
            if ($key && !in_array($key, $settings)) {
                $settings[$key] = ['encrypted' => $encrypted];
            }
        }
        
        return $settings;
    }
    
    /**
     * Render JS api extension
     * @param array $js_api
     * @return array|string[][][]|boolean[][][]
     */
    public function renderJsApiExtensionData($js_api = [])
    {
        foreach ($this->getMasterSettingsConfig() as $master_config) {
            foreach ($this->getNonJsConfig() as $non_js) {
                if (isset($master_config[$non_js])) {
                    unset($master_config[$non_js]);
                }
            }
            
            $js_api[] = $master_config;
        }
        
        return $js_api;
    }
    
    /**
     * Get non JS config
     * @return string[]
     */
    public function getNonJsConfig()
    {
        return $this->non_js_config;
    }
    
    /**
     * Get master settings config
     * @return array
     */
    public function getMasterSettingsConfig()
    {
        return $this->master_settings_config;
    }    
}