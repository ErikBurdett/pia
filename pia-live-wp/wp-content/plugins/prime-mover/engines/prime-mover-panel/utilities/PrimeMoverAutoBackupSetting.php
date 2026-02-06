<?php
namespace Codexonics\PrimeMoverFramework\utilities;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsTemplate;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover display auto-backup setting
 *
 */
class PrimeMoverAutoBackupSetting
{
    private $prime_mover_settings_template;
    private $autobackup_multisite_subsite_status;
    private $autobackup_global_status;
    private $autobackup_export_options;
    private $default_autobackup_schedule;
    private $autobackup_schedule_identifier;
    private $autobackup_encryption_status_identifier;    
    private $autobackup_dropbox_identifier;
    private $autobackup_gdrive_identifier;
    private $autobackup_maintenance_identifier;
    private $autobackup_timeout_options;
    private $autobackup_log_identifier;
    private $clear_autobackup_log_identifier;
    private $autobackup_runtime_error_identifier;
    private $clear_error_log_identifier;
    private $clear_autobackup_init_identifier;
    
    /**
     * Constructor
     * @param PrimeMoverSettingsTemplate $prime_mover_settings_template
     */
    public function __construct(PrimeMoverSettingsTemplate $prime_mover_settings_template) 
    {
        $this->prime_mover_settings_template = $prime_mover_settings_template;
        $this->autobackup_multisite_subsite_status = 'autobackup_multisite_level_status';
        $this->autobackup_global_status = 'autobackup_global_status';
        
        $this->autobackup_export_options = 'autobackup_export_options';       
        $this->autobackup_schedule_identifier = 'autobackup_schedule';
        $this->autobackup_encryption_status_identifier = 'autobackup_encryption_status';
        $this->autobackup_dropbox_identifier = 'autobackup_backup_to_dropbox';
        
        $this->autobackup_gdrive_identifier = 'autobackup_backup_to_gdrive';
        $this->autobackup_maintenance_identifier = 'autobackup_db_maintenance_mode';
        $this->autobackup_timeout_options = 'autobackup_timeout_options';
        
        $this->autobackup_log_identifier = 'autobackup_download_log';
        $this->clear_autobackup_log_identifier = 'clear_autobackup_log';
        $this->autobackup_runtime_error_identifier = 'autobackup_runtime_error_log';
        
        $this->clear_error_log_identifier = 'clear_runtime_error_log';        
        $this->clear_autobackup_init_identifier = 'clear_autobackup_init_meta';
    }
    
    /**
     * Get clear identifier auto backup init
     * @return string
     */
    public function getIdentifierClearAutoBackupInit()
    {
        return $this->clear_autobackup_init_identifier;
    }
    
    /**
     * Get clear runtime error log identifier
     * @return string
     */
    public function getIdentifierClearErrorLog()
    {
        return $this->clear_error_log_identifier;
    }
    
    /**
     * Get autobackup runtime error identifier
     * @return string
     */
    public function getAutoBackupRuntimeErrorLogIdentifier()
    {
        return $this->autobackup_runtime_error_identifier;
    }
    
    /**
     * Get lock utilities
     */
    public function getLockUtilities()
    {
        $utilities = $this->getPrimeMoverSettingsTemplate()->getUtilities();
        $lock_utilities = $utilities['lock_utilities'];
        
        return $lock_utilities;
    }
    
    /**
     * Get error handlers
     */
    public function getErrorHandlers()
    {
        $utilities = $this->getPrimeMoverSettingsTemplate()->getUtilities();
        $error_handlers = $utilities['error_handlers'];
        
        return $error_handlers;
    }
    
    /**
     * Get shutdown utilities
     */
    public function getShutdownUtilities()
    {
        return $this->getErrorHandlers()->getShutDownUtilities();
    }

    /**
     * Get clear autobakup log identifier
     * @return string
     */
    public function getIdentifierClearAutoBackupLog()
    {
        return $this->clear_autobackup_log_identifier;
    }
    
    /**
     * Get automatic backup log identifier
     * @return string
     */
    public function getIdentifierAutoBackupLog()
    {
        return $this->autobackup_log_identifier;
    }
    
    /**
     * Get automatic backup timeout options
     * @return string
     */
    public function getIdentifierAutoBackupTimeoutOptions()
    {
        return $this->autobackup_timeout_options;
    }
    
    /**
     * Get autobackup maintenance identifier
     * @return string
     */
    public function getAutoBackupMaintenanceIdentifier()
    {
        return $this->autobackup_maintenance_identifier;
    }
    
    /**
     * Get autobackup Gdrive identifier
     * @return string
     */
    public function getAutoBackupGdriveIdentifier()
    {
        return $this->autobackup_gdrive_identifier;
    }
    
    /**
     * Get auto backup DropBox identifier
     * @return string
     */
    public function getAutoBackupDropboxIdentifier()
    {
        return $this->autobackup_dropbox_identifier;
    }
    
    /**
     * Get auto backup encryption identifier
     * @return string
     */
    public function getAutoBackupEncryptionStatusIdentifier()
    {
        return $this->autobackup_encryption_status_identifier;
    }
    
    /**
     * Get autobackup schedule identifier
     * @return string
     */
    public function getAutoBackupScheduleIdentifier()
    {
        return $this->autobackup_schedule_identifier;
    }
    
    /**
     * Get autobackup schedules
     * @param array $schedules
     * @return array
     */
    public function getAutoBackupSchedules($schedules = [])
    {       
        $schedules = $this->maybeAddCustomSchedules($schedules);        
        return array_merge($schedules, $this->getPrimeMoverSettings()->getAutoBackupSchedules());
    }
    
    /**
     * Parse custom schedule from config
     * @return boolean|NULL|mixed
     */
    public function parseCustomScheduleFromConfig()
    {
        return $this->getPrimeMoverSettings()->parseCustomScheduleFromConfig();
    }
    
    /**
     * Maybe add custom schedules
     * @param array $schedules
     * @return array
     */
    protected function maybeAddCustomSchedules($schedules = [])
    {
        $custom_schedules = $this->parseCustomScheduleFromConfig();
        if (empty($custom_schedules)) {
            return $schedules;
        }
        
        foreach ($custom_schedules as $identifier => $custom_schedule) {
            if (isset($schedules[$identifier])) {
                continue;
            }
            
            $schedules[$identifier] = $custom_schedule;
        }

        return $schedules;
    }
    
    /**
     * Get schedule values
     * @return array
     */
    public function getScheduleValues()
    {
        $schedules = $this->getAutoBackupSchedules();
        return wp_list_pluck($schedules, 'display');
    }
    
    /**
     * Get automatic backup export options
     * @return string
     */
    public function getIdentifierAutoBackupExportOptions()
    {
        return $this->autobackup_export_options;
    }
    
    /**
     * Get export utilities
     */
    public function getExportUtilities()
    {
        $utilities = $this->getPrimeMoverSettingsTemplate()->getUtilities();        
        $import_utilities = $utilities['import_utilities'];
        
        return $import_utilities->getExportUtilities();
    }
    
    /**
     * Get component utilities
     */
    public function getComponentUtilities()
    {
        $utilities = $this->getPrimeMoverSettingsTemplate()->getUtilities();
        $component_utilities = $utilities['component_utilities'];
        
        return $component_utilities;
    }
    
    /**
     * Get settings identifier for autobackup global
     * @return string
     */
    public function getIdentifierGlobalAutoBackupSetting()
    {
        return $this->autobackup_global_status;
    }
  
    /**
     * Get settings identifier for autobackup multisite subsite status
     * @return string
     */
    public function getIdentifierSubsiteAutoBackupStatusSetting()
    {
        return $this->autobackup_multisite_subsite_status;
    }
    
    /**
     * Get settings template
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsTemplate
     */
    public function getPrimeMoverSettingsTemplate()
    {
        return $this->prime_mover_settings_template;        
    }
 
    /**
     * Get multisite migration settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettings
     */
    public function getPrimeMoverSettings()
    {
        return $this->getPrimeMoverSettingsTemplate()->getPrimeMoverSettings();
    }
    
    /**
     * Get Prime Mover
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->getPrimeMoverSettingsTemplate()->getPrimeMover();
    }
    
    /**
     * Get system initialization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemInitialization
     */
    public function getSystemInitialization()
    {
        return $this->getPrimeMover()->getSystemInitialization();
    }
    
    /**
     * Get settings config
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsConfig
     */
    public function getSettingsConfig()
    {
        return $this->getPrimeMoverSettingsTemplate()->getSettingsConfig();
    }   
    
    /**
     * Get Freemius integration
     */
    public function getFreemiusIntegration()
    {
        return $this->getPrimeMoverSettingsTemplate()->getFreemiusIntegration();
    }
    
    /**
     * Init hooks
     */
    public function initHooks()
    {
        add_action('prime_mover_show_other_backup_management', [$this, 'outputAutomaticBackupMarkup'], 0);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'maybeOutputEnableAutoBackupMultisite'], 10, 1); 
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputExportOptions'], 15, 1);
        
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputBackupSchedules'], 20, 1);
        add_filter('prime_mover_get_autobackup_schedules', [$this, 'getAutoBackupSchedules'], 10, 1);
        add_filter('prime_mover_get_autobackup_sites_implementation', [$this, 'getAutoBackupSitesImplementation']);
        
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputBackupEncryption'], 25, 1);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputTimeoutOptions'], 30, 1); 
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputMaintenanceMode'], 35, 1); 
        
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputBackupToDropbox'], 40, 1);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputBackupToGdrive'], 45, 1);               
        
        add_filter('prime_mover_get_autobackup_schedules', [$this, 'getAutoBackupProgressIntervals'], 9);
        add_filter('prime_mover_filter_user_metas', [$this, 'removeAutoBackupInitUserMetaOnExport'], 1000, 3);
        add_filter('prime_mover_after_creating_tar_archive', [$this, 'injectAutoBackupInitUserMetas'], 100, 2);
        
        add_action('delete_user', [$this, 'maybeMoveLockSettingsToOptionsOnUserDelete'], 0, 1);
        add_action('wp_update_user', [$this, 'maybeMoveLockSettingsToOptionsOnUserUpdate'], 0, 1);
    }
 
    /**
     * When administrator holding locked setting has role downgraded
     * Restore all fallback settings back to options
     * @param number $user_id
     */
    public function maybeMoveLockSettingsToOptionsOnUserUpdate($user_id = 0)
    {
        $multisite = false;
        $user_id = (int)$user_id;
        
        if (is_multisite()) {
            $multisite = true;
        }
        
        if (!$user_id) {
            return;
        }
        
        if ($multisite) {
            return;
        }
        
        if (!$this->getPrimeMover()->getSystemAuthorization()->canManageSite($user_id, $multisite)) {
            $this->maybeMoveLockSettingsToOptionsOnUserDelete($user_id, true);
        } 
    }
    
    /**
     * Maybe move lock settings to options if lock user is deleted
     * @param number $user_id
     * @param boolean $update
     */
    public function maybeMoveLockSettingsToOptionsOnUserDelete($user_id = 0, $update = false)
    {
        $user_id = (int)$user_id;
        if (!$user_id || !$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized() || is_multisite()) {
            return;
        }
        
        $currentdBexportuser = $this->getSystemInitialization()->getPrimeMoverCurrentDbExportUser();
        $currentdBexportuser = (int)$currentdBexportuser;
        
        if (!$currentdBexportuser) {
            return;
        }
        
        $restore = false;
        if ($currentdBexportuser === $user_id) {
            $restore = true;
        }              
        
        $prime_mover_plugin_manager = $this->getSystemInitialization()->getPrimeMoverPluginManagerInstance();       
        if ($update && $restore && is_object($prime_mover_plugin_manager) && $this->getLockUtilities()->isLockingPrimeMoverProcesses($prime_mover_plugin_manager, true)) {
            $restore = false;
        }
        
        if ($restore) {
            $this->getComponentUtilities()->restoreAllFallBackSettings(0, 0, false, true);
        }
    }
    
    /**
     * Inject autobackup initialization user metas for export removal
     * @param array $ret
     * @param number $blogid_to_export
     * @return array
     */
    public function injectAutoBackupInitUserMetas($ret = [], $blogid_to_export = 0)
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized() || !$blogid_to_export || !is_array($ret)) {
            return $ret;
        }
               
        $autobackup_user = $this->getPrimeMover()->getSystemFunctions()->getAutoBackupUser(); 
        $current_user_id = $this->getSystemInitialization()->getCurrentUserId();
        
        $autobackup_user = (int)$autobackup_user;
        $current_user_id = (int)$current_user_id;
       
        if (!$autobackup_user) {
            return $ret;
        }
        
        $init_user_meta = $this->getSystemInitialization()->getAutoBackupInitMeta($blogid_to_export, $autobackup_user);
        $retry_user_meta = $this->getSystemInitialization()->getAutoBackupRetryMeta($blogid_to_export, $autobackup_user);
        $ret = $this->appendInitUseMetas($ret, $init_user_meta, $retry_user_meta);
        
        if (!$current_user_id) {
            return $ret;
        }
        
        if ($autobackup_user === $current_user_id) {
            return $ret;
        }
 
        $init_user_meta = $this->getSystemInitialization()->getAutoBackupInitMeta($blogid_to_export, $current_user_id);
        $retry_user_meta = $this->getSystemInitialization()->getAutoBackupRetryMeta($blogid_to_export, $current_user_id);        
        $ret = $this->appendInitUseMetas($ret, $init_user_meta, $retry_user_meta);
        
        return $ret;
    }
    
    /**
     * Append init user metas
     * @param array $ret
     * @param string $init_user_meta
     * @param string $retry_user_meta
     * @return $ret
     */
    protected function appendInitUseMetas($ret = [], $init_user_meta = '', $retry_user_meta = '')
    {
        if (!$init_user_meta || !$retry_user_meta || !is_array($ret)) {
            return $ret;
        }
        
        $ret['autobackup_init_user_metas'][] = $init_user_meta;
        $ret['autobackup_init_user_metas'][] = $retry_user_meta;
        
        return $ret;
    }
    
    /**
     * Remove autobackup initialization user meta key from export
     * @param array $user_meta
     * @param array $ret
     * @param number $blog_id
     */
    public function removeAutoBackupInitUserMetaOnExport($user_meta = [], $ret = [], $blog_id = 0)
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return $user_meta;
        }
        
        if (!isset($ret['autobackup_init_user_metas'])) {
            return $user_meta;
        }
      
        $init_user_metas = $ret['autobackup_init_user_metas'];
        if (!is_array($init_user_metas)) {
            return $user_meta;
        }
        
        foreach($init_user_metas as $user_meta_key) {
            if (isset($user_meta[$user_meta_key])) {
                unset($user_meta[$user_meta_key]);
            }
        }        
        return $user_meta;
    }
    
    /**
     * Reset auto backup initialization
     */
    public function maybeResetAutoBackupInitialization()
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
       
        if (is_multisite() && !is_main_site()) {
            return;
        }

        if (is_multisite() && function_exists('fs_is_network_admin') && !fs_is_network_admin()) {
            return;
        }
        
        $prime_mover_plugin_manager = $this->getSystemInitialization()->getPrimeMoverPluginManagerInstance();
        if (!is_object($prime_mover_plugin_manager)) {
            return;
        }
        
        if ($this->getLockUtilities()->isLockingPrimeMoverProcesses($prime_mover_plugin_manager, true)) {
            return;
        }
       
        if ($this->getFreemiusIntegration()->maybeLoggedInUserIsCustomer()) {
            return;
        }
        
        $update = false;
        $setting_value = $this->getComponentUtilities()->getAllPrimeMoverSettings();
        if (!is_array($setting_value)) {
            return;
        }
                
        if (!empty($setting_value['automatic_backup_initialized'])) {
            $update = true;
            $setting_value['automatic_backup_initialized'] = [];
        }
        
        if ($update) {
            $this->getComponentUtilities()->updateAllPrimeMoverSettings($setting_value);
        }        
        
        if (is_multisite() || !$update) {
            return;
        }

        $this->getComponentUtilities()->markSettingsForRestorationToOptionsTable();        
    }
    
    /**
     * Get autobackup schedules
     * @return string[][]|number[][]
     */
    public function getAutoBackupProgressIntervals()
    {
        return $this->getSettingsConfig()->getProgressIntervalSchedules();
    }
    
    /**
     * Output timeout options
     * @param number $blog_id
     */
    public function outputTimeoutOptions($blog_id = 0)
    {
        $master_options = $this->getSettingsConfig()->getTimeOutOptions();
        $timeout_options = wp_list_pluck($master_options, 'timeout_desc');
        
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getIdentifierAutoBackupTimeoutOptions();
        
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $backup_options_label = __('Retry timeout options', 'prime-mover');
        $choose_opt_lbl = esc_html__('If your hosting supports higher timeout - please choose higher values. This will speed up the backup process. The default value is 50 seconds which should work in most cases. Please consult your hosting provider to get info regarding their timeout configuration.', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderRadioFormTemplate($backup_options_label, $identifier, $config, '', $timeout_options, $choose_opt_lbl, $blog_id, false, $button_specs);
    }
    
    /**
     * Output maintenance mode setting for backups
     * @param number $blog_id
     */
    public function outputMaintenanceMode($blog_id = 0)
    {
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupMaintenanceIdentifier();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $checkbox_specs = [
            'default' => 'false',
            'return_default_if_no_key' => true
        ];
        
        $config = $settings_api[$identifier];
        $auto_backup_lbl = __('Maintenance mode', 'prime-mover');
        $enable_autobackup_lbl = __('Enable database dump maintenance mode', 'prime-mover');        
        $sprintf = esc_html__('By default, maintenance mode is disabled when doing the automatic backup. Enabling this will result in maintenance mode when exporting the database.', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate($auto_backup_lbl, $identifier, $config, 'true', $enable_autobackup_lbl, 
            $sprintf, $blog_id, false, $button_specs, $checkbox_specs);
    }
    
    /**
     * Output backup to Gdrive
     * @param number $blog_id
     */
    public function outputBackupToGdrive($blog_id = 0)
    {
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupGdriveIdentifier();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $important_msg = '<br /><strong>' . __('Important:', 'prime-mover') . '</strong>';
        $settings_page_url =  '<a href="' . esc_url($this->getFreemiusIntegration()->getSettingsPageUrl()) . '#prime-mover-gdrive-settings-label">' . __('Google Drive OAuth 2.0 credentials', 'prime-mover') . '</a>';
        $gdrive_connect_url = '<a class="prime-mover-external-link" href="' . esc_url(CODEXONICS_GDRIVE_CONNECT_DOC) . '">' . __('connected to API', 'prime-mover') . '</a>';
        
        $sprintf = sprintf(esc_html__('Check this setting to create a backup copy of this package to Google Drive. %s This requires %s to work and should be %s.', 'prime-mover'),
            $important_msg, $settings_page_url, $gdrive_connect_url);
        
        $config = $settings_api[$identifier];
        $gdrive_storage_label = __('Google Drive storage', 'prime-mover');
        $backup_label = __('Backup to Google Drive', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate($gdrive_storage_label, $identifier, $config, 'true', $backup_label, $sprintf, $blog_id, true, $button_specs);
    }
    
    /**
     * Output backup to Dropbox
     * @param number $blog_id
     */
    public function outputBackupToDropbox($blog_id = 0)
    {
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupDropboxIdentifier();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $dropbox_storage_label = __('Dropbox storage', 'prime-mover');
        $backup_to_dropbox_label = __('Backup to Dropbox', 'prime-mover');
        $important_msg = '<br /><strong>' . __('Important:', 'prime-mover') . '</strong>';
        $settings_page_url = '<a href="' . esc_url($this->getFreemiusIntegration()->getSettingsPageUrl()) . '#prime-mover-dropbox-settings-label">' . __('Dropbox access token', 'prime-mover') . '</a>';
        
        $sprintf = sprintf(esc_html__('Check this setting if you want to save a backup copy to Dropbox. %s This requires %s to work.', 'prime-mover'), $important_msg, $settings_page_url);        
        $config = $settings_api[$identifier];
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate( $dropbox_storage_label, $identifier, $config, 'true', $backup_to_dropbox_label, $sprintf, $blog_id, true, $button_specs);
    }
    
    /**
     * Output backup encryption
     * @param number $blog_id
     */
    public function outputBackupEncryption($blog_id = 0)
    {
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupEncryptionStatusIdentifier();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $encryption_label = __('Backup encryption', 'prime-mover');
        $config = $settings_api[$identifier];
        $enable_encryption_label = __('Enable backup encryption', 'prime-mover');
        $default_label = esc_html__('By default, backup encryption is disabled. Check this box if you want backups to be encrypted.', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate($encryption_label, $identifier, $config, 'true', $enable_encryption_label, $default_label, $blog_id, true, $button_specs);
    }
    
    /**
     * Get autobackup sites implementation
     * @return array
     */
    public function getAutoBackupSitesImplementation()
    {        
        return $this->getPrimeMoverSettings()->getBackupSchedules();
    }
    
    /**
     * Output backup schedules
     * @param number $blog_id
     */
    public function outputBackupSchedules($blog_id = 0)
    { 
        $schedule_values = $this->getScheduleValues();        
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupScheduleIdentifier();
        
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];        
        $schedule_label = __('Backup schedule', 'prime-mover');
        $choose_label = sprintf(esc_html__('Choose backup schedule. For best performance, it is best to choose weekly for complete backup package or twice a week for database backups only. %s. ', 'prime-mover'), 
            esc_html__('You can ', 'prime-mover') . '<a target="_blank" class="prime-mover-external-link" href="https://codexonics.com/prime_mover/prime-mover/how-to-add-custom-backup-schedules-for-automatic-backup/">' . esc_html__('add custom schedules via wp-config.php', 'prime-mover') . '</a>');
        $backup_select_label = __('Choose backup schedule', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderSelectFormTemplate($schedule_label, $identifier, $config, '', $schedule_values, $choose_label, $blog_id, true, $button_specs, $backup_select_label, true);    
    }
    
    /**
     * Output export options
     * @param number $blog_id
     */
    public function outputExportOptions($blog_id = 0)
    {
        $export_options = $this->getExportUtilities()->getExportModes();
        if (isset($export_options['development_package'])) {
            unset($export_options['development_package']);
        }
        
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getIdentifierAutoBackupExportOptions();
        
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $backup_options_label = __('Backup options', 'prime-mover');
        $choose_opt_lbl = esc_html__('Choose from the above export options that will be used for scheduled backup.', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderRadioFormTemplate($backup_options_label, $identifier, $config, '', $export_options, $choose_opt_lbl, $blog_id, true, $button_specs);        
    }
    
    /**
     * Maybe output enable auto backup in multisite
     * @param number $blog_id
     */
    public function maybeOutputEnableAutoBackupMultisite($blog_id = 0)
    {
        if (!is_multisite()) {
            return;
        }
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getIdentifierSubsiteAutoBackupStatusSetting();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $auto_backup_lbl = __('Automatic backup', 'prime-mover');
        $enable_autobackup_lbl = __('Enable automatic backup', 'prime-mover');
        $settings_page_url = '<a href="' . esc_url($this->getFreemiusIntegration()->getSettingsPageUrl()) . '#prime-mover-autobackup_global_status-label">' . __('global setting', 'prime-mover') . '</a>';
        
        $sprintf = sprintf(esc_html__('By default, automatic backup is disabled on this site. This setting only affects this site. To disable automatic backups for all sites at once, use the %s.', 'prime-mover'), 
            $settings_page_url);
        
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate($auto_backup_lbl, $identifier, $config, 'true', $enable_autobackup_lbl, $sprintf, $blog_id, true, $button_specs);
    }
    
    /**
     * Output automatic backup markup
     */
    public function outputAutomaticBackupMarkup()
    {
        $settings_api = $this->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getIdentifierGlobalAutoBackupSetting();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'div',
            'button_classes' => 'button-primary',
            'button_text' => '',
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];       
        $autobackup_label = __('Automatic backup', 'prime-mover');
        $enable_label = __('Enable automatic backup', 'prime-mover');
        $settings_page_url = '<a href="' . $this->getPrimeMover()->getSystemFunctions()->getScheduledBackupSettingsUrl() . '">' . __('Scheduled backup', 'prime-mover') . '</a>';
        
        $sprintf = sprintf(esc_html__('By default, automatic backup is disabled. This is a global setting that affects all sites (if using multisite).
Once enabled - you can set individual backup site settings via %s.', 'prime-mover'), $settings_page_url);
        
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxFormTemplate($autobackup_label, $identifier, $config, 'true', $enable_label, $sprintf, 0, true, $button_specs);
    }   
}