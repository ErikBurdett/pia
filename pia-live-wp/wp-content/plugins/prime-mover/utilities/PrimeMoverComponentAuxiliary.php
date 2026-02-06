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

use Freemius;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover Component Auxiliary
 * Auxiliary component class for interacting with Prime Mover components
 *
 */
class PrimeMoverComponentAuxiliary
{        
    private $import_utilities;
    private $download_utilities;
    private $backup_utilities;
    private $corrupt_packages;
    
    /**
     * Constructor
     * @param PrimeMoverImportUtilities $import_utilities
     * @param PrimeMoverDownloadUtilities $download_utilities
     * @param PrimeMoverBackupUtilities $backup_utilities
     */
    public function __construct(PrimeMoverImportUtilities $import_utilities, PrimeMoverDownloadUtilities $download_utilities, PrimeMoverBackupUtilities $backup_utilities)
    {
        $this->import_utilities = $import_utilities;
        $this->download_utilities = $download_utilities;
        $this->backup_utilities = $backup_utilities;
        $this->corrupt_packages = [];       
    }
    
    /**
     * Get shutdown utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverShutdownUtilities
     */
    public function getShutDownUtilities()
    {
        return $this->getImporter()->getCliArchiver()->getProgressHandlers()->getShutDownUtilities();
    }
    
    /**
     * Get corrupt packages
     * @return array
     */
    public function getCorruptPackages()
    {
        return $this->corrupt_packages;
    }
    
    /**
     * Set corrupt packages
     * @param string $package_path
     */
    public function setCorruptPackages($package_path = '')
    {
        if (!in_array($package_path, $this->corrupt_packages)) {
            $this->corrupt_packages[] = $package_path;
        }        
    }
    
    /**
     * Get backup utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverBackupUtilities
     */
    public function getBackupUtilities()
    {
        return $this->backup_utilities;
    }
    
    /**
     * Get download utilities
     */
    public function getDownloadUtilities()
    {
        return $this->download_utilities;
    }
    
    /**
     * Get import utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverImportUtilities
     */
    public function getImportUtilities()
    {
        return $this->import_utilities;
    }
    
    /**
     * Get lock utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverLockUtilities
     */
    public function getLockUtilities()
    {
        return $this->getImportUtilities()->getLockUtilities();    
    }
    
    /**
     * Get exporter
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverExporter
     */
    public function getExporter()
    {
        return $this->getImportUtilities()->getExportUtilities()->getExporter();
    }
    
    /**
     * Get importer
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverImporter
     */
    public function getImporter()
    {
        return $this->getImportUtilities()->getImporter();
    }
    
    /**
     * Get system authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()
    {
        return $this->getExporter()->getSystemAuthorization();
    }
    
    /**
     * Get Freemius
     * @return Freemius
     */
    public function getFreemius()
    {
        return $this->getSystemAuthorization()->getFreemius();
    }
    
    /**
     * Get system initialization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemInitialization
     */
    public function getSystemInitialization()
    {
        return $this->getExporter()->getSystemInitialization();
    }
    
    /**
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getExporter()->getSystemFunctions();
    }
    
    /**
     * Init hook class
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itAddsInitHooks()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itChecksIfHooksAreOutdated()
     */
    public function initHooks()
    {
        add_action('prime_mover_before_db_dump_export', [$this, 'backupControlPanelSettings'], 15, 0);
        add_action('prime_mover_after_db_dump_export', [$this, 'restoreControlPanelSettings'], 60, 2); 
        add_action('prime_mover_before_db_dump_export', [$this, 'backupGearBoxPackages'], 20, 0);
        
        add_action('prime_mover_after_db_dump_export', [$this, 'restoreGearBoxPackages'], 15, 2); 
        add_action('prime_mover_shutdown_actions', [$this, 'restoreGearBoxPackages'], 15, 0);  
        
        add_action('prime_mover_shutdown_actions', [$this, 'restoreOptionRelatedSettings'], 30, 0);
        add_action('prime_mover_shutdown_actions', [$this, 'restoreControlPanelSettings'], 60, 0); 
        add_action('prime_mover_before_db_processing', [$this, 'backupGearBoxPackages'], 15, 0);
        
        add_action('prime_mover_after_db_processing', [$this, 'restoreGearBoxPackages'], 15, 0);
        add_action('prime_mover_before_db_processing', [$this, 'backupOptionRelatedSettings'], 45, 0);
        add_action('prime_mover_after_db_processing', [$this, 'restoreOptionRelatedSettings'], 45, 0);
        
        add_action('prime_mover_before_db_dump_export', [$this, 'backupOptionRelatedSettings'], 45, 0);
        add_action('prime_mover_after_db_dump_export', [$this, 'restoreOptionRelatedSettings'], 30, 2); 
        
        add_filter('prime_mover_restore_backup_parameters_menu', [$this, 'noMainSiteSupportByDefault'], 10, 4);
        add_filter('prime_mover_addnew_backupmenu', [$this, 'disableExportButtonMenuMainSite'], 10, 4);    
       
        add_action('prime_mover_before_db_dump_export', [$this, 'backupExcludedOptions'], 60, 2);
        add_action('prime_mover_after_db_dump_export', [$this, 'restoreExcludedOptions'], 45, 2); 
        
        add_action('prime_mover_before_db_processing', [$this, 'backupOptionRelatedSettingsRestore'], 60, 2);
        add_action('prime_mover_after_db_processing', [$this, 'restoreExcludedOptionsImport'], 60, 2);
        
        add_action('prime_mover_shutdown_actions', [$this, 'restoreExcludedOptions'], 45, 0);
        add_filter('prime_mover_get_all_settings', [$this, 'getAllPrimeMoverSettings'], 10, 1);       
        add_action('admin_init', [$this, 'maybeRestoreSettingBackToOptions'], 10000);
        
        add_action('admin_init', [$this, 'maybeRestoreFallBackIfLockFileDeleted'], 10001);
    }

    /**
     * Restore fallback settings when lock file is deleted
     */
    public function maybeRestoreFallBackIfLockFileDeleted()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (wp_doing_ajax() || wp_doing_cron() || is_multisite()) {
            return;
        }
        
        if (!is_admin()) {
            return;
        }
        
        $current_db_export_user = $this->getSystemInitialization()->getPrimeMoverCurrentDbExportUser();
        if (!$current_db_export_user) {
            return;
        }
        
        $prime_mover_plugin_manager = $this->getSystemInitialization()->getPrimeMoverPluginManagerInstance();
        if (!is_object($prime_mover_plugin_manager)) {
            return;
        }
        
        if ($this->getLockUtilities()->isLockingPrimeMoverProcesses($prime_mover_plugin_manager, true)) {
            return;
        }
        
        if ($this->getLockUtilities()->checkifDBExportIsLockedToUsers()) {
            return;
        }        
        
        $this->restoreAllFallBackSettings(0, 0, false, true);        
    }
    
    /**
     * Bailout settings update if we are on locked state
     * @return boolean
     */
    public function maybeBailOutSettingsUpdateOnLockedState()
    {
        if (is_multisite()) {
            return false;
        }
         
        return apply_filters('prime_mover_db_is_still_locked', false);  
    }
    
    /**
     * Maybe restore settings back to options
     */
    public function maybeRestoreSettingBackToOptions()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
      
        if (wp_doing_ajax() || wp_doing_cron() || is_multisite()) {
            return;
        }
       
        $value = $this->getSystemFunctions()->getSiteOption($this->getSystemInitialization()->getSettingsOptionToRestore());
        if ($value !== 'yes') {
            return;    
        }         
         
        $auto_backup_user_id = $this->getBackupUtilities()->getAutoBackupUser();
        if (!$auto_backup_user_id) {
            return;
        } 
       
        $this->restoreAllFallBackSettings(0, $auto_backup_user_id, true, false);
        $this->getSystemFunctions()->deleteSiteOption($this->getSystemInitialization()->getSettingsOptionToRestore(), true);
    }
    
    /**
     * Unlocks a specified user and then restore all fallback settings back to options table
     * @param number $current_user_id
     * @param number $user_to_unlock
     * @param boolean $autobackup_mode
     * @param boolean $clear_all_locks
     */
    public function restoreAllFallBackSettings($current_user_id = 0, $user_to_unlock = 0, $autobackup_mode = false, $clear_all_locks = false)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        } 
        
        do_action('prime_mover_db_dump_lock_end', $user_to_unlock, $autobackup_mode,  $clear_all_locks);
        
        $this->restoreGearBoxPackages(1, [], $current_user_id);
        $this->restoreOptionRelatedSettings(1, [], $current_user_id);
        $this->restoreExcludedOptions(1, [], $current_user_id);        
      
        $this->restoreControlPanelSettings(0, [], $current_user_id);        
    }
    
    /**
     * Restore excluded options (API supported - import mode)
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function restoreExcludedOptionsImport($ret = [], $blogid_to_import = 0)
    {
        $this->restoreExcludedOptions($blogid_to_import, $ret);
    }
    
    /**
     * Backup excluded options (API supported - restore mode)  
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function backupOptionRelatedSettingsRestore($ret = [], $blogid_to_import = 0)
    {
        $this->backupExcludedOptions($blogid_to_import, $ret);
    }
    
    /**
     * Backup excluded options (API supported)
     * @param number $blog_id
     * @param array $ret
     */
    public function backupExcludedOptions($blog_id = 0, $ret = [])
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blog_id) {
            return;
        }
        
        $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();   
        $meta_key = $this->getSystemInitialization()->getPrimeMoverExcludedSettingsKey();
        
        $current_filter = current_filter();            
        if (is_multisite()) {
            $meta_key = $meta_key . '_' . $blog_id;             
        } else {           
            $settings_check = get_user_meta($current_user_id, $meta_key, true);
            if (is_array($settings_check)) {
                do_action('prime_mover_log_processed_events', "DB LOCK: Bail out moving settings from options to user meta because $meta_key meta key already LOCKED in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
                return;
            }
        }           
      
        delete_user_meta($current_user_id, $meta_key);      
        $this->getSystemFunctions()->switchToBlog($blog_id);       
        $delete = false;        
        if ('prime_mover_before_db_dump_export' === $current_filter) {
            $delete = true;
        }
      
        $options = apply_filters('prime_mover_excluded_options_db', [], $blog_id, $ret);        
        if ($delete && is_array($options) && !empty($options)) {
            $keys = array_keys($options);
            foreach ($keys as $key) {
                delete_option($key);
            }
        }       
             
        $this->getSystemFunctions()->restoreCurrentBlog();      
        if (!empty($options)) {            
            do_action('prime_mover_update_user_meta', $current_user_id, $meta_key, $options);
            do_action('prime_mover_log_processed_events', "DB LOCK: Moving settings from options to user meta using $meta_key meta key to LOCK this in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
        }        
    }
 
    /**
     * Restore excluded settings after DB dump or restoration.
     * @param number $blog_id
     * @param array $ret
     * @param number $current_user_id
     */
    public function restoreExcludedOptions($blog_id = 0, $ret = [], $current_user_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
      
        if (!$blog_id) {
            $blog_id = $this->getShutDownUtilities()->primeMoverGetProcessedID();
        }      
        
        $meta_key = $this->getSystemInitialization()->getPrimeMoverExcludedSettingsKey();
        if (is_multisite()) {
            $meta_key = $meta_key . '_' . $blog_id;
        }
        
        $current_filter = current_filter();        
        if ($this->maybeBailOutSettingsUpdateOnLockedState()){
            do_action('prime_mover_log_processed_events', "DB LOCK: POSTPONE moving setting $meta_key from user meta to options due to locked state in $current_filter hook", $blog_id, 'common', __FUNCTION__, $this);
            return;
        } 
        
        if (!$current_user_id) {
            $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        }
        
        if (!$current_user_id || !$blog_id) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Not being able to move setting $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id because either blog ID or user ID does not exists.", $blog_id, 'common', __FUNCTION__, $this);
            return;
        }       
        
        $options = get_user_meta($current_user_id, $meta_key, true);
        if (!is_array($options) || empty($options)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Not being able to move setting $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id because the value is not an array or empty.", $blog_id, 'common', __FUNCTION__, $this);
            return;
        }
       
        $this->getSystemFunctions()->switchToBlog($blog_id);    
        foreach ($options as $option_name => $option_value) {
            update_option($option_name, $option_value);
        }
       
        $this->getSystemFunctions()->restoreCurrentBlog(); 
                
        delete_user_meta($current_user_id, $meta_key);
        do_action('prime_mover_log_processed_events', "DB LOCK: MOVED SETTINGS $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id.", $blog_id, 'common', __FUNCTION__, $this);
    }
    
    /**
     * Backup option related settings
     */
    public function backupOptionRelatedSettings()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        }
        
        $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        $meta_key = $this->getSystemInitialization()->getEncKeySetting();
        $settings_check = get_user_meta($current_user_id, $meta_key, true);
        $current_filter = current_filter();
        
        if (is_array($settings_check)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Bail out moving settings from options to user meta because $meta_key meta key already LOCKED in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
            return;
        }
        
        delete_user_meta($current_user_id, $meta_key);
        $delete = false;       
        if ('prime_mover_before_db_dump_export' === $current_filter) {
            $delete = true;
        }
        
        $options = [];
        $options[$this->getSystemInitialization()->getImportantReadMsgSetting()] = $this->getSystemFunctions()->getSiteOption($this->getSystemInitialization()->getImportantReadMsgSetting());
        $options[$this->getSystemInitialization()->getEncConfigDoneSetting()] = $this->getSystemFunctions()->getSiteOption($this->getSystemInitialization()->getEncConfigDoneSetting());
        
        if ($delete) {
            delete_option($this->getSystemInitialization()->getImportantReadMsgSetting());
            delete_option($this->getSystemInitialization()->getEncConfigDoneSetting());
        }
                
        do_action('prime_mover_update_user_meta', $current_user_id, $meta_key, $options);
        do_action('prime_mover_log_processed_events', "DB LOCK: Moving settings from options to user meta using $meta_key meta key to LOCK this in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
    }
    
    /**
     * Restore option related settings after dB processing or dB export
     * @param number $blog_id
     * @param array $ret
     * @param number $current_user_id
     */
    public function restoreOptionRelatedSettings($blog_id = 0, $ret = [], $current_user_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        }

        if (!$blog_id) {
            $blog_id = 1;
        }
        
        $meta_key = $this->getSystemInitialization()->getEncKeySetting();
        $current_filter = current_filter();
        
        if ($this->maybeBailOutSettingsUpdateOnLockedState()){
            do_action('prime_mover_log_processed_events', "DB LOCK: POSTPONE moving setting $meta_key from user meta to options due to locked state in $current_filter hook.", 1, 'common', __FUNCTION__, $this);
            return;
        }        
        
        if (!$current_user_id) {
            $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        }
        
        $options = get_user_meta($current_user_id, $meta_key, true);
        if (!is_array($options) || empty($options)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Not being able to move setting $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id because the option is not an array or does not exists.", 1, 'common', __FUNCTION__, $this);
            return;
        }
        
        foreach ($options as $option_name => $option_value) {
            $this->getSystemFunctions()->updateSiteOption($option_name, $option_value, true, '', true, true);
        }
        
        delete_user_meta($current_user_id, $meta_key);
        do_action('prime_mover_log_processed_events', "DB LOCK: MOVED SETTINGS $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
    }
    
    /**
     * Disable export button on main site by default
     * @param array $addnewbackup_button
     * @param number $blog_id
     * @param boolean $enabled
     * @param array $original_button_markup
     * @return boolean $enabled
     */
    public function disableExportButtonMenuMainSite($addnewbackup_button = [], $blog_id = 0, $enabled = false, $original_button_markup = [])
    {
        if (!$blog_id || empty($addnewbackup_button) || empty($original_button_markup)) {
            return $addnewbackup_button;
        }
        
        if ($this->getSystemFunctions()->isMultisiteMainSite($blog_id) && $enabled) {
            list($note, $url, $class, $link_text) = $addnewbackup_button;
            $note = esc_html__('Exporting main site is a PRO feature. Please upgrade or activate license to use this feature.', 'prime-mover');
            
            $upgrade_url = apply_filters('prime_mover_filter_upgrade_pro_url', $this->getFreemius()->get_upgrade_url(), $blog_id); 
            $url = esc_url($upgrade_url);
            $link_text = apply_filters('prime_mover_filter_upgrade_pro_text', esc_html__( 'Upgrade to PRO', 'prime-mover' ), $blog_id);
            
            $class = 'page-title-action prime-mover-upgrade-button';
            $addnewbackup_button = [$note, $url, $class, $link_text];  
        }
        
        
        return $addnewbackup_button;
    }
    
    /**
     * No main site support by default
     * @param array $new_parameters
     * @param array $original_parameters
     * @param number $blog_id
     * @param boolean $link_active
     * @return array
     * @mainsitesupport_affected
     */
    public function noMainSiteSupportByDefault($new_parameters = [], $original_parameters = [], $blog_id = 0, $link_active = false)
    {
        if (!$blog_id || empty($new_parameters)) {
            return $new_parameters;
        }
        
        if ($this->getSystemFunctions()->isMultisiteMainSite($blog_id) && $link_active) {
            $upgrade_url = apply_filters('prime_mover_filter_upgrade_pro_url', $this->getFreemius()->get_upgrade_url(), $blog_id);            
            $url = esc_url($upgrade_url);            
            $link_text = apply_filters('prime_mover_filter_upgrade_pro_text', esc_html__( 'Upgrade to PRO', 'prime-mover' ), $blog_id);
            
            $note = esc_html__('Restoring main site is a PRO feature. Please upgrade or activate license to use this feature.', 'prime-mover');
            $class = 'button js-prime-mover-upgrade-button-simple prime-mover-upgrade-button-simple';
            
            $new_parameters = [$url, $class, $note, $link_text];            
        }
        
        return $new_parameters;        
    }
    
    /**
     * Restore all Prime Mover settings to options table
     * @param array $settings
     * @moved
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itRestoresAllPrimeMoverSettings()
     */
    public function restoreAllPrimeMoverSettings($settings = [])
    {
        if ( ! is_array($settings) || empty($settings) ) {
            return;
        }
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $setting_name = $this->getSystemInitialization()->getControlPanelSettingsName(); 
        $this->getSystemFunctions()->updateSiteOption($setting_name, $settings, true, '', true, true);
    }
    
    /**
     * Delete all settings in options table (or user meta table for fallback situations)
     * 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itDeletesAllPrimeMoverSettings() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itDoesNotDeletesAllPrimeMoverSettingsIfNotAuthorized() 
     *  
     * @param boolean $force_to_option
     * @param boolean $everything
     * @return boolean|void|boolean
     */
    public function deleteAllPrimeMoverSettings($force_to_option = false, $everything = false)
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        }
        
        $setting_name = $this->getSystemInitialization()->getControlPanelSettingsName();
        if ($force_to_option) {
            return $this->getSystemFunctions()->deleteSiteOption($setting_name, true, '', true, true);
        }
        
        $user_meta_setting = [];
        $db_export_user = 0;

        if (!is_multisite()) {           
            $db_export_user = $this->getSystemFunctions()->getLockedSettingsUser();
        }
        
        if ($db_export_user) {
            $user_meta_setting = get_user_meta($db_export_user, $this->getSystemInitialization()->getMigrationCurrentSettings(), true);
        }     
        
        if (is_array($user_meta_setting) && !empty($user_meta_setting)) {
            delete_user_meta($db_export_user, $this->getSystemInitialization()->getMigrationCurrentSettings());
        }      
         
        return $this->getSystemFunctions()->deleteSiteOption($setting_name, true, '', true, $everything);
    }

    /**
     * Restore control panel settings after dB processing or dB export (or even after runtime error)
     * @moved
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itRestoreControlPanelSettingsInSingleSite() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itSkipsRestorationControlPanelSettingsInMultisite() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itSkipsRestorationControlPanelSettingsNotAuthorized()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itDoesNotDeleteControlPanelSettingsIfNotExist()
     * @param number $blog_id
     * @param array $ret
     * @param number $current_user_id
     */
    public function restoreControlPanelSettings($blog_id = 0, $ret = [], $current_user_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        }       
        
        if (!$blog_id) {
            $blog_id = 1;
        }
        
        $meta_key = $this->getSystemInitialization()->getMigrationCurrentSettings();
        $current_filter = current_filter();        
        
        if ($this->maybeBailOutSettingsUpdateOnLockedState()){
            do_action('prime_mover_log_processed_events', "DB LOCK: POSTPONE moving setting $meta_key from user meta to options due to locked state in $current_filter hook.", $blog_id, 'common', __FUNCTION__, $this);
            return;
        } 
        
        if (!$current_user_id) {
            $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        }
        
        $current_settings = get_user_meta($current_user_id, $meta_key, true);
        if (empty($current_settings)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Not being able to move setting $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id because the current setting is empty.", $blog_id, 'common', __FUNCTION__, $this);
            return;
        }
      
        $this->deleteAllPrimeMoverSettings(true);  
        $this->restoreAllPrimeMoverSettings($current_settings);
        
        delete_user_meta($current_user_id, $meta_key);   
        do_action('prime_mover_log_processed_events', "DB LOCK: MOVED SETTINGS $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id.", $blog_id, 'common', __FUNCTION__, $this);
    }
    
    /**
     * Sanitize prime mover setting from the options table
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itSanitizesPrimeMoverSettings() 
     */
    public function sanitizePrimeMoverSetting()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        }
        
        $control_panel_settings = $this->getSystemInitialization()->getControlPanelSettingsName();  
        $this->getSystemFunctions()->deleteSiteOption($control_panel_settings, true, '', true, true);
    }

    /**
     * Restore gearbox packages after dB export or import processing
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itRestoreGearBoxPackages() 
     * @moved 
     * @param number $blog_id
     * @param array $ret
     * @param number $current_user_id
     */
    public function restoreGearBoxPackages($blog_id = 0, $ret = [], $current_user_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        }      
        
        if (!$blog_id) {
            $blog_id = 1;
        }
        
        $meta_key = $this->getSystemInitialization()->getCurrentGearBoxPackagesMetaKey();
        $current_filter = current_filter();
        
        if ($this->maybeBailOutSettingsUpdateOnLockedState()){
            do_action('prime_mover_log_processed_events', "DB LOCK: POSTPONE moving setting $meta_key from user meta to options due to locked state in $current_filter hook.", $blog_id, 'common', __FUNCTION__, $this);
            return;
        }
        
        if (!$current_user_id) {
            $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        }
        
        $gearbox_packages = get_user_meta($current_user_id, $meta_key, true);
        if (!is_array($gearbox_packages) || empty($gearbox_packages)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Not being able to move setting $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id because the packages is not an array or is empty.", $blog_id, 'common', __FUNCTION__, $this);
            return;
        }
        
        foreach ($gearbox_packages as $option_name => $option_value) {
            $this->getSystemFunctions()->updateSiteOption($option_name, $option_value, true, '', true, true);            
        }
        
        delete_user_meta($current_user_id, $meta_key);
        do_action('prime_mover_log_processed_events', "DB LOCK: MOVED SETTINGS $meta_key from user meta to options in $current_filter hook using user ID: $current_user_id.", $blog_id, 'common', __FUNCTION__, $this);
    }
    
    /**
     * Get all Prime Mover settings
     * This is compatible to manual and automated processes.
     * @return mixed|boolean|NULL|array
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itGetsAllPrimeMoverSettings()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalValueIfEncryptedKeyNotSet()
     * 
     * @param array $value
     * @return mixed|boolean|NULL|array
     */
    public function getAllPrimeMoverSettings($value = []) 
    {        
        $setting_name = $this->getSystemInitialization()->getControlPanelSettingsName();        
        $setting_value = $this->getSystemFunctions()->getSiteOption($setting_name, false, true, true);
        
        if (is_multisite()) {
            return $setting_value;
        }
       
        $db_export_user = $this->getSystemFunctions()->getLockedSettingsUser();
        if (!$db_export_user) {
            return $setting_value; 
        }
        
        $user_meta_setting = get_user_meta($db_export_user, $this->getSystemInitialization()->getMigrationCurrentSettings(), true);     
        if (is_array($user_meta_setting) && !empty($user_meta_setting)) {
            return $user_meta_setting;
        }
      
        return $setting_value;        
    }
        
    /**
     * Update all Prime Mover settings
     * Settings should be already processed at this time
     * As this function should only handle the saving mechanism
     * This is compatible to manual and automated processes.
     * @param array $settings
     */
    public function updateAllPrimeMoverSettings($settings = [])
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }

        if (!is_array($settings) || empty($settings)) {
            return;
        }
       
        $settings_name = $this->getSystemInitialization()->getControlPanelSettingsName();
        $options_table_update = true;       
        $db_export_user = 0;
        $user_meta_setting = [];
      
        if (!is_multisite()) {
            $db_export_user = $this->getSystemFunctions()->getLockedSettingsUser();
        } 
        
        if ($db_export_user) {
            $user_meta_setting = get_user_meta($db_export_user, $this->getSystemInitialization()->getMigrationCurrentSettings(), true); 
        }
        
        if (is_array($user_meta_setting) && !empty($user_meta_setting)) {
            $options_table_update = false;
        }
        
        if ($options_table_update) {
            $this->getSystemFunctions()->updateSiteOption($settings_name, $settings, true,  '', true, true);            
        } else {            
            do_action('prime_mover_update_user_meta', $db_export_user, $this->getSystemInitialization()->getMigrationCurrentSettings(), $settings);
        }       
    }
    
    /**
     * Mark settings for restoration back to options table
     * (From user meta table)
     */
    public function markSettingsForRestorationToOptionsTable()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $db_export_user = $this->getSystemFunctions()->getLockedSettingsUser();
        if (!$db_export_user) {
            return;
        }
        
        $for_restoration = false;
        $user_meta_setting = get_user_meta($db_export_user, $this->getSystemInitialization()->getMigrationCurrentSettings(), true);
        if (is_array($user_meta_setting) && !empty($user_meta_setting)) {
            $for_restoration = true;
        }
        
        if ($for_restoration) {
            $this->getSystemFunctions()->updateSiteOption($this->getSystemInitialization()->getSettingsOptionToRestore(), 'yes', true);
        }
    }
    
    /**
     * Backup control panel settings before dB export or dB import processing
     * @moved
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itBackupsControlPanelSettings() 
     */
    public function backupControlPanelSettings()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }

        if (is_multisite()) {
            return;
        }
        
        $current_filter = current_filter();        
        if ('prime_mover_before_db_dump_export' === $current_filter) {
            do_action('prime_mover_db_dump_lock_start');
        }
        
        $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();
        $meta_key = $this->getSystemInitialization()->getMigrationCurrentSettings();
        $settings_check = get_user_meta($current_user_id, $meta_key, true);
        if (is_array($settings_check)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Bail out moving settings from options to user meta because $meta_key meta key already LOCKED in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
            return;
        }
        
        $current_settings = $this->getAllPrimeMoverSettings();        
        do_action('prime_mover_update_user_meta', $current_user_id, $this->getSystemInitialization()->getMigrationCurrentSettings(), $current_settings);        
       
        $this->sanitizePrimeMoverSetting();        
        do_action('prime_mover_log_processed_events', "DB LOCK: Moving settings from options to user meta using $meta_key meta key to LOCK this in $current_filter hook using user ID: $current_user_id.", 1, 'common', __FUNCTION__, $this);
    }
    
    /**
     * Generate download URL args
     * @param string $hash
     * @param number $blog_id
     * @return string[]|number[]
     * @moved
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itGeneratesDownloadUrlArgs()
     */
    public function generateDownloadURLArgs($hash = '', $blog_id = 0)
    {
        return [
            'prime_mover_export_hash' => $hash,
            'prime_mover_blogid' => $blog_id
        ];
    }
    
    /**
     * Get download URL of zip for clipboard copying purposes
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itGeneratesDownloadUrlForClipboard()
     * @param string $sanitized_name
     * @param number $blog_id
     * @param boolean $ret_array
     * @param boolean $admin_mode
     * @return string|string[]|mixed[]|boolean[]|NULL[]|array[]
     */
    public function generateDownloadURLForClipBoard($sanitized_name = '', $blog_id = 0, $ret_array = false, $admin_mode = false)
    {
        $ret = '';
        if ( ! $sanitized_name || ! $blog_id) {
            return $ret;
        }
       
        $meta_key = $this->getSystemInitialization()->getCurrentGearBoxPackagesMetaKey();
        $option_name = $this->getSystemInitialization()->generateZipDownloadOptionName($sanitized_name, $blog_id);  
        $downloadurl_params = $this->getSystemFunctions()->getSiteOption($option_name, false, true, true, $meta_key, true, false);        
        if ( ! $downloadurl_params ) {
            return $ret;
        }
        if (empty($downloadurl_params['blogid']) || empty($downloadurl_params['hash'])) {
            return $ret;
        }
        $blog_id = (int)$blog_id;
        $blogid_db = (int)$downloadurl_params['blogid'];
        if ($blogid_db !== $blog_id) {
            return $ret;
        }
        $hash = $downloadurl_params['hash'];
        $download_args = $this->generateDownloadURLArgs($hash, $blog_id);
        
        if ($ret_array) {
            
            return [
                'download_url' => $this->getSystemInitialization()->getDownloadURLGivenParameters($download_args),
                'download_params' => $downloadurl_params
            ];
            
        }
        
        return $this->getSystemInitialization()->getDownloadURLGivenParameters($download_args, $admin_mode);
    }
    
    /**
     * Checks if valid format
     * @param string $filename
     * @return boolean
     */
    protected function isReallyValidFormat($filename = '')
    {
        if (!$this->getSystemFunctions()->nonCachedFileExists($filename)) {
            return false;
        }
        
        return $this->getSystemFunctions()->isReallyValidFormat($filename);
    }
    
    /**
     * Maybe report corrupted WPRIME packages in package manager
     * @param number $blog_id
     */
    public function maybeReportCorruptedPackages($blog_id = 0)
    {
        if (empty($this->getCorruptPackages()) || !$blog_id) {
            return;
        }
        
        $refresh_package_url = $this->getSystemFunctions()->getRefreshPackageUrl($blog_id);
        $count = count($this->getCorruptPackages());
        $package_string = esc_html__('package', 'prime-mover');
        $this_package_string = esc_html__('This package', 'prime-mover');
        if ($count > 1) {
            $package_string = esc_html__('packages', 'prime-mover');
            $this_package_string = esc_html__('These packages', 'prime-mover');
        }
    ?>
       <div class="notice notice-warning is-dismissible"> 
       <?php if (wp_doing_ajax()) { ?>
            <p><?php echo sprintf(esc_html__('Corrupt %s detected! Check %s', 'prime-mover'), 
                $package_string,
                '<a href="' . esc_url($refresh_package_url) . '">' . esc_html__('package manager') . '</a>'
                ); ?>.
            </p>
       
       <?php } else { ?> 
	        <h2><?php echo sprintf(esc_html__('Corrupt %s detected', 'prime-mover'), $package_string); ?>!</h2>
	        <p><?php echo sprintf(esc_html__('%s detects the following corrupted %s in your backup directory', 'prime-mover'), 
	            $this->getSystemInitialization()->getPrimeMoverPluginTitle(), 
	            $package_string); 
	        ?>:
	        </p>	
            
            <ul>
            <?php foreach ($this->getCorruptPackages() as $package) { ?>
                <li><code><?php echo esc_html($package); ?></code></li>
            <?php } ?>
            </ul>           
	
	        <p><?php echo sprintf(esc_html__('If you think %s should not be corrupted - %s. Once fixed, click %s button to update. 
If you no longer need %s - please delete this via FTP or any file manager.', 'prime-mover'), 
		        strtolower($this_package_string),
		        '<a class="prime-mover-external-link" target="_blank" href="' . CODEXONICS_CORRUPT_WPRIME_DOC . '">' . esc_html__('please check out this tutorial', 'prime-mover') . '</a>',
		        '<strong>' . esc_html__('Refresh packages') . '</strong>',
	            strtolower($this_package_string)
		        );
		    ?></p>
	     <?php } ?>
		</div>
    <?php 
    }
    /**
     * Validate backups to be listed
     * @param array $backups
     * @param number $blog_id
     * @param string $current_backup_hash
     * @return string
     */
    public function validateBackups($backups = [], $blog_id = 0, $current_backup_hash = '')
    {
        if ( ! is_array($backups) ) {
            return $backups;
        }
        
        $refresh = false;
        $option_name = $this->getSystemInitialization()->getPrimeMoverValidatedBackupsOption();
        
        $backups_array = $this->getBackupUtilities()->getValidatedBackupsArrayInDb($option_name);
        $backups_hash_db = $this->getBackupUtilities()->getBackupsHashInDb($backups_array, $blog_id);
        
        if ( $this->getBackupUtilities()->maybeRefreshBackupData($backups_hash_db, $current_backup_hash, true, $blog_id, false)) {
            $refresh = true;
        }
        
        if (defined('PRIME_MOVER_PACKAGE_FORCE_REFRESH_PACKAGE') && PRIME_MOVER_PACKAGE_FORCE_REFRESH_PACKAGE) {
            $refresh = true;
        }        
       
        if ( ! $refresh && ! empty($backups_array[$blog_id]) ) {
            return reset($backups_array[$blog_id]);
        }
        
        $files = array_keys($backups);        
        $in_progress_packages = $this->getBackupUtilities()->getInProgressPackages();
        $in_progress_tmp = $in_progress_packages;
        foreach ($files as $file) {            
            $package_meta = [];
            $include_users = false;
            if (isset($backups[$file])) {
                $package_meta = $backups[$file];
            }
            if (!empty($package_meta['filepath']) && !$this->isReallyValidFormat($package_meta['filepath'])) { 
                unset($backups[$file]);                                
                if ($this->getBackupUtilities()->packageIsInProgress($in_progress_packages, $package_meta['filepath'], $blog_id)) {
                    $in_progress_packages = $this->getBackupUtilities()->maybeCleanUpInProgressAutoBackupPackages($in_progress_packages, $package_meta, $blog_id);
                } else {
                    if ($this->getSystemFunctions()->nonCachedFileExists($package_meta['filepath'])) {
                        $this->setCorruptPackages($package_meta['filepath']);
                        add_action('prime_mover_package_manager_notices', [$this, 'maybeReportCorruptedPackages'], 10, 1);                          
                    }                                       
                }
                
                continue;                
            }
            
            if ($this->getBackupUtilities()->maybeInProgressPackageIsAutoBackup($in_progress_packages, $package_meta, $blog_id)) {
                unset($backups[$file]);
                continue;
            }            
            
            $sanitized_name = sanitize_html_class($file);
            $validated_download = $this->generateDownloadURLForClipBoard($sanitized_name, $blog_id, true);
            
            if ( ! $validated_download ) {               
                $validated_download = $this->maybeAddAsPrimeMoverPackage($file, $package_meta, $blog_id, $sanitized_name);
            }
            
            if ( ! $validated_download ) {
                unset($backups[$file]);
                continue;
            }

            if ( ! empty($validated_download['download_params']['include_users'])) {
                $include_users = $validated_download['download_params']['include_users'];                
            } else {
                $include_users = $this->getUsersFunction()->isZipPackageIncludeUsers($package_meta['filepath']);
            }
            if (is_bool($include_users)) {
                if ($include_users) {
                    $backups[$file]['include_users'] = esc_html__('Yes', 'prime-mover');
                } else {
                    $backups[$file]['include_users'] = esc_html__('No', 'prime-mover');
                }               
            } else {
                $backups[$file]['include_users'] = $include_users;
            }
            
            if ( ! empty($validated_download['download_params']['site_title'] ) ) {
                $backups[$file]['site_title'] = $validated_download['download_params']['site_title'];
            }
            
            $backups[$file]['date'] = '';
            if ( ! empty($validated_download['download_params']['creation_timestamp'] ) ) {
                $backups[$file]['date'] = $validated_download['download_params']['creation_timestamp'];
            }
        }
        
        $this->getBackupUtilities()->updateValidatedBackupsArrayInDb($backups, $current_backup_hash, $option_name, $blog_id, $backups_hash_db);  
        if ($in_progress_tmp !== $in_progress_packages) {
            $this->getBackupUtilities()->maybeRemoveWipStatusOnError('automatic', $blog_id,$in_progress_packages, true, true);
        }
        
        return $backups;
    }
  
    /**
     * Get validated backups in export directory with caching mechanism
     * @param number $blog_id
     * @param string $dir
     * @return array
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itGetsValidatedBackupsInExportDirectoryCached() 
     */
    public function getValidatedBackupsInExportDirectoryCached($blog_id = 0, $dir = '')
    {
        if ( ! $blog_id ) {
            return [];
        }
        $backups = $this->getSystemFunctions()->getFilesToRestore($blog_id, $dir);        
        $current_backup_hash = $this->getBackupUtilities()->computeBackupHash($backups, $blog_id);
        
        return $this->validateBackups($backups, $blog_id, $current_backup_hash);        
    }
    
    /**
     * Maybe we need to add package to backups table list for easy management
     * @param string $file
     * @param array $package_meta
     * @param number $blog_id
     * @param string $sanitized_name
     * @return boolean|string
     */
    protected function maybeAddAsPrimeMoverPackage($file = '', $package_meta = [], $blog_id = 0, $sanitized_name = '')
    {        
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        }
        if (! $file || ! isset($package_meta['filepath']) || ! $blog_id) {
            return false;
        }
        $is_tar = false;
        $tar_config = [];
        if ($this->getSystemFunctions()->hasTarExtension($package_meta['filepath'])) {
            $is_tar = true;
            $tar_config = apply_filters('prime_mover_get_tar_package_config_from_file', $tar_config, $package_meta['filepath']);
        }
        
        $filepath = wp_normalize_path($package_meta['filepath']);
        $results = str_replace(wp_normalize_path($this->getSystemInitialization()->getMultisiteExportFolderPath()), '', $filepath);
        $hash = $this->getSystemFunctions()->hashString($file);
        
        if ($is_tar) {
            $encrypted_package = $tar_config['encrypted'];
        } else {
            $encrypted_package = $this->getImportUtilities()->isZipPackageDbEncrypted($filepath);
        }
        if ($is_tar) {
            $package_description = $tar_config['export_options'];
        } else {
            $package_data = $this->getImportUtilities()->getZipPackageDescription($filepath, $blog_id, $encrypted_package, false, false, true);
            if ( ! is_array($package_data) ) {
                return false;
            }            
            $package_description = key($package_data);             
        }        
        $valid_export_options = $this->getImportUtilities()->getExportUtilities()->getValidExportOptions();
        if ( ! in_array($package_description, $valid_export_options)) {
            return false;
        } 
        
        $ret = [];
        $ret['target_zip_path'] = $filepath;
        $ret['multisite_export_options'] = $package_description;
        $ret['prime_mover_encrypt_db'] = $this->analyzeEncryptionStatus($encrypted_package);
        if ($is_tar) {
            $ret['site_title'] = $tar_config['site_title'];
            $ret['include_users'] = $tar_config['include_users'];
        } else {
            $ret['site_title'] = $this->getImportUtilities()->getSiteTitleFromZipPackage($filepath);
            $ret['include_users'] = $this->getUsersFunction()->isZipPackageIncludeUsers($filepath, 'txt');
        }               
        
        $this->getDownloadUtilities()->saveDownloadURLParameters($results, $hash, $blog_id, false, $ret);  
        if ($file) {
            $generatedFilename = $file;
        } else {
            $generatedFilename = $this->getSystemFunctions()->createFriendlyName($blog_id, $results, $is_tar);
        }
        
        $ret['generated_filename'] = $generatedFilename;
        $this->getSystemFunctions()->updateSiteOption($hash, $results, true, '', true, true);
        $this->getSystemFunctions()->updateSiteOption($hash . "_filename", $generatedFilename, true, '', true, true);
        
        return $this->generateDownloadURLForClipBoard($sanitized_name, $blog_id, true);        
    }
    
    /**
     * Get user functions
     * @return \Codexonics\PrimeMoverFramework\users\PrimeMoverUserFunctions
     */
    public function getUsersFunction()
    {
        return $this->getExporter()->getUsersObject()->getUserUtilities()->getUserFunctions();
    }
        
    /**
     * Analyze encryption status
     * @param string $encrypted_package
     * @return string
     */
    private function analyzeEncryptionStatus($encrypted_package = '')
    {
        if (is_bool($encrypted_package)) {
            return $encrypted_package ? 'true' : 'false';
        } else {
            return $encrypted_package;
        } 
    }
    
    /**
     * Backup gearbox packages before dB processing or dB export
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itBackupsGearBoxPackages()
     * @moved
     * @mainsitesupport_affected
     */
    public function backupGearBoxPackages()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if (is_multisite()) {
            return;
        }
                       
        $current_user_id = $this->getSystemFunctions()->getLockedSettingsUser();    
        $blog_id = 1;
        $meta_key = $this->getSystemInitialization()->getCurrentGearBoxPackagesMetaKey();
        $settings_check = get_user_meta($current_user_id, $meta_key, true);
        $current_filter = current_filter();        
        
        if (is_array($settings_check)) {
            do_action('prime_mover_log_processed_events', "DB LOCK: Bail out moving settings from options to user meta because $meta_key meta key already LOCKED in $current_filter hook.", 1, 'common', __FUNCTION__, $this);
            return;
        }        
        
        delete_user_meta($current_user_id, $meta_key);
        $gearbox_packages = [];
        
        $delete = false;
        $current_filter = current_filter();
        if ('prime_mover_before_db_dump_export' === $current_filter) {
            $delete = true;
        }
        $backups = $this->generateBackupFilesArray($blog_id, $delete);        
        $packages = [];
        
        if (!is_array($backups)) {
            return;
        }
        $files = array_keys($backups);
        $result = $this->generateGearBoxPackages($files, $packages, $blog_id, $gearbox_packages);
        
        if ($delete) {
            $packages = $result['packages'];
            foreach ($packages as $package) {
                delete_option($package);
            }            
        }

        do_action('prime_mover_update_user_meta', $current_user_id, $meta_key, $result['gearbox_packages']); 
        do_action('prime_mover_log_processed_events', "DB LOCK: Moving settings from options to user meta using $meta_key meta key to LOCK this in $current_filter hook.", 1, 'common', __FUNCTION__, $this);
    } 
    
    /**
     * Generate backup files array
     * @param number $blog_id
     * @param boolean $delete
     * @return array|string
     */
    protected function generateBackupFilesArray($blog_id = 0, $delete = false)
    {
        $override_dir = false;
        if ($delete && ! defined('PRIME_MOVER_PANEL_VERSION') ) {
            $override_dir = true;
        }
        $dir = '';
        if ($override_dir) {            
            $setting = $this->getAllPrimeMoverSettings();
            if ( ! empty($setting['basedir_backup_path']) ) {
                $dir = $setting['basedir_backup_path'];
            }
        }
  
        return $this->getValidatedBackupsInExportDirectoryCached($blog_id, $dir);
    }
    
    /**
     * Generate gearbox packages
     * @param array $files
     * @param array $packages
     * @param number $blog_id
     * @param array $gearbox_packages
     * @return mixed|boolean|NULL|array
     */
    protected function generateGearBoxPackages($files = [], $packages = [], $blog_id = 0, $gearbox_packages = [])
    {
        $result = [];        
        $meta_key = $this->getSystemInitialization()->getCurrentGearBoxPackagesMetaKey();
        foreach ($files as $file) {
            $sanitized_name = sanitize_html_class($file);
            $option_name = $this->getSystemInitialization()->generateZipDownloadOptionName($sanitized_name, $blog_id);
            
            $package_setting = $this->getSystemFunctions()->getSiteOption($option_name, false, true, true, $meta_key, true, false);
            if ($package_setting ) {
                $packages[] = $option_name;
            }            
            
            if ( ! is_array($package_setting) || empty($package_setting['hash']) ) {
                continue;
            }
            
            $hash = $package_setting['hash'];
            $hash_setting = $this->getSystemFunctions()->getSiteOption($hash, false, true, true, '', true, true);            
            if ( ! $hash_setting ) {
                continue;
            }
            $packages[] = $hash;
            $gearbox_packages[$option_name] = $package_setting;
            $gearbox_packages[$hash] = wp_normalize_path($hash_setting);
        }
        
        $result['packages'] = $packages;
        $result['gearbox_packages'] = $gearbox_packages;
        
        return $result;
    }
    
    /**
     * Get encryption status given dB option
     * @param number $blog_id
     * @param string $sanitized_name
     * @param string $db_option
     * @return boolean
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::generateEncryptionStatusTestData() 
     */
    public function getEncryptionStatusGivenOption($blog_id = 0, $sanitized_name = '', $db_option = 'encrypted_db')
    {
        $encryption_status = false;
        if ( ! $blog_id || ! $sanitized_name ) {
            return $encryption_status;
        }
        
        $option_name = $this->getSystemInitialization()->generateZipDownloadOptionName($sanitized_name, $blog_id);
        $meta_key = $this->getSystemInitialization()->getCurrentGearBoxPackagesMetaKey();
        $setting = $this->getSystemFunctions()->getSiteOption($option_name, false, true, true, $meta_key, true, false);
        if ( ! $setting || empty($setting[$db_option])) {
            return $encryption_status;
        }
        return $setting[$db_option];
    }
    
    /**
     * Enqueue clipboard js
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itEnqueuesClipboardJs()
     */
    public function enqueueClipBoardJs($backup_menu = false)
    {
        $min = '.min';
        if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) {
            $min = '';
        }
        
        $clipboard_js = "clipboard$min.js";
        $current_filter = current_filter();
        $dependencies = ['jquery', 'jquery-ui-core', 'jquery-ui-dialog'];
        if ('prime_mover_panel_after_enqueue_assets' !== $current_filter && false === $backup_menu) {
            $dependencies[] = 'prime_mover_js_network_admin';
        }
        wp_enqueue_script(
            'prime_mover_gearbox_clipboard_js',
            esc_url_raw(plugins_url('res/js/' . $clipboard_js, dirname(__FILE__))),
            $dependencies,
            PRIME_MOVER_VERSION
            );     
    }
    
    /**
     * Get sites with backups in multisite
     * @return void|array|mixed|boolean|NULL
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverComponentAuxiliary::itGetSitesWithBackupsInMultisite() 
     */
    public function getSitesWithBackupsInMultisite()
    {
        if ( ! $this->getSystemAuthorization()->isUserAuthorized() ) {
            return;
        }
        
        $option_name = $this->getSystemInitialization()->getBackupSitesOptionName();
        $site_ids = $this->getSystemFunctions()->getSiteOption($option_name, false, true, true);
        if ( ! is_array($site_ids) ) {
            $site_ids = [];
        }
              
        return apply_filters('prime_mover_filter_subsites_with_backups', $site_ids);        
    }
    
    /**
     * Can support restore URL in Free mode
     * @return boolean
     */
    public function canSupportRestoreUrlInFreeMode()
    {
        $current =basename(PRIME_MOVER_PLUGIN_PATH) . '/' . PRIME_MOVER_PLUGIN_FILE;
        return (PRIME_MOVER_DEFAULT_PRO_BASENAME === $current && apply_filters('prime_mover_get_setting', '', 'allowed_domains', true, '', true));
    }
}