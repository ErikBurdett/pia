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

use Codexonics\PrimeMoverFramework\classes\PrimeMover;
use Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization;
use Codexonics\PrimeMoverFramework\app\PrimeMoverSettings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class for managing backup directory
 *
 */
class PrimeMoverBackupManagement
{
    private $prime_mover;
    private $system_authorization;
    private $settings;
    private $delete_utilities;
    private $backupdir_size;
    private $component_aux;
    private $settings_helper;
    private $form_setting_keys;
    
    const COPYBACKUP_DIR = 'dont_copydir_when_deactivated';
    
    /**
     * Constructor
     * @param PrimeMover $PrimeMover
     * @param PrimeMoverSystemAuthorization $system_authorization
     * @param array $utilities
     * @param PrimeMoverSettings $settings
     * @param PrimeMoverDeleteUtilities $delete_utilities
     * @param PrimeMoverBackupDirectorySize $backupdir_size
     * @param PrimeMoverSettingsHelper $settings_helper
     */
    public function __construct(PrimeMover $PrimeMover, PrimeMoverSystemAuthorization $system_authorization, array $utilities, 
        PrimeMoverSettings $settings, PrimeMoverDeleteUtilities $delete_utilities, PrimeMoverBackupDirectorySize $backupdir_size, PrimeMoverSettingsHelper $settings_helper) 
    {
        $this->prime_mover = $PrimeMover;
        $this->system_authorization = $system_authorization;
        $this->settings = $settings;
        $this->delete_utilities = $delete_utilities;
        $this->backupdir_size = $backupdir_size;
        $this->component_aux = $utilities['component_utilities'];
        $this->settings_helper = $settings_helper;
        
        $this->form_setting_keys = [
            'prime_mover_save_automatic_backup_db_maintenance' => 'autobackup_db_maintenance_mode',
            'prime_mover_save_automatic_backup_timeout_options' => 'autobackup_timeout_options'
        ];
    }

    /**
     * Get checkbox setting keys
     * @return string[]
     */
    public function getFormSettingKeys()
    {
        return $this->form_setting_keys;
    }
    
    /**
     * Get settings helper
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsHelper
     */
    public function getSettingsHelper()
    {
        return $this->settings_helper;
    }

    /**
     * Get component auxiliary
     * @return array
     */
    public function getComponentAux()
    {
        return $this->component_aux;
    }
    
    /**
     * Get backup dir size instance
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverBackupDirectorySize
     */
    public function getBackupDirSize()
    {
        return $this->backupdir_size;
    }
    
    /**
     * Get Delete utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverDeleteUtilities
     */
    public function getDeleteUtilities()
    {
        return $this->delete_utilities;        
    }
    
    /**
     * Get Prime Mover settings
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettings
     */
    public function getPrimeMoverSettings() 
    {
        return $this->settings;
    }    

    /**
     * Get settings config
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsConfig
     */
    public function getSettingsConfig()
    {
        return $this->getSettingsHelper()->getSettingsConfig();
    }
    
    /**
     * Init hooks
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupManagement::itAddsInitHooks()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupManagement::itChecksIfHooksAreOutdated()
     */
    public function initHooks() 
    {
        /**
         * Ajax handler
         */
        add_action('wp_ajax_prime_mover_copydir_preference', [$this,'saveSettings']);
        add_action('wp_ajax_prime_mover_delete_all_backups_request', [$this,'deleteAllBackups']);
        add_action('wp_ajax_prime_mover_computedir_size', [$this,'computeBackupDirSize']);
        
        add_action('prime_mover_control_panel_settings', [$this, 'showBackupManagementSetting'], 40);   
        add_filter('prime_mover_filter_migratesites_column_markup', [$this, 'maybeFilterMigrateColumnMarkup'], 10, 3); 
        
        $form_setting_keys = array_values($this->getFormSettingKeys());
        foreach ($form_setting_keys as $setting_keys) {
            $settings_config = $this->getSettingsConfig()->getMasterSettingsConfig();
            if (isset($settings_config[$setting_keys]['ajax_action'])) {
                $ajax_action = $settings_config[$setting_keys]['ajax_action'];
                add_action("wp_ajax_{$ajax_action}", [$this,'saveAutoBackupSettings']);
            }
        }
    }
 
    /**
     * Save automatic backup settings used in free version.
     */
    public function saveAutoBackupSettings()
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Unauthorized, please login to WordPress and try again.', 'prime-mover')]);
        }
        
        $prefix = 'wp_ajax_';
        $str = current_filter();
        if (substr($str, 0, strlen($prefix)) == $prefix) {
            $str = substr($str, strlen($prefix));
        }
        
        $form_keys = $this->getFormSettingKeys();
        if (!isset($form_keys[$str])) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Settings not defined.', 'prime-mover')]);
        }
        
        $settings_identifier = $form_keys[$str];
        $settings_config = $this->getSettingsConfig()->getMasterSettingsConfig();
        if (!isset($settings_config[$settings_identifier]['validation_id'])) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Settings validation ID is not defined', 'prime-mover')]);
        }
        
        $validation_id = $settings_config[$settings_identifier]['validation_id'];
        $cron_sched = false;
        if ('autobackup_schedule' === $settings_identifier) {
            $cron_sched = true;
        }
        $this->getSettingsHelper()->saveSettings($settings_identifier, true, '', $validation_id, $cron_sched);
    }
    
    /**
     * Maybe filter migrate column markup
     * @param array $markup
     * @param array $item
     * @param number $blog_id
     * @return array
     */
    public function maybeFilterMigrateColumnMarkup($markup = [], $item = [], $blog_id = 0)
    {
        if (!$blog_id) {
            return $markup;
        }
        
        if (true === $this->getComponentAux()->canSupportRestoreUrlInFreeMode()) {
            $markup = [
                sprintf(esc_attr__('Copy restore URL to clipboard. This requires PRO version at target %s to migrate this package.', 'prime-mover'), $item['package_type']),
                $item['download_url'],
                "button prime-mover-menu-button js-prime-mover-clipboard-button-responsive js-prime-mover-copy-clipboard-menu prime-mover-copy-clipboard-menu-button",
                esc_html__('Copy restore URL', 'prime-mover'),
                '<span id="' . esc_attr($item['sanitized_package_name']) . '" class="prime-mover-clipboard-key-confirmation-menu js-prime-mover-copy-clipboard-menu">' . esc_html__('Copied', 'prime-mover') . ' !' . '</span>',
                esc_attr($item['sanitized_package_name'])
            ];
        }
        
        return $markup;
    }
    
    /**
     * Compute backup dir size ajax
     */
    public function computeBackupDirSize()
    {
        $this->getBackupDirSize()->computeBackupDirSize();
    }
    
    /**
     * Save settings ajax
     */
    public function deleteAllBackups()
    {
        $this->getDeleteUtilities()->deleteAllBackups();
    }
    
    /**
     * Save settings ajax
     */
    public function saveSettings()
    {
        $response = [];       
        $copydir_preference = $this->getPrimeMoverSettings()->prepareSettings($response, 'copydir_preference', 'prime_mover_copydir_preference_nonce', 
            true, $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter());        
        
        $result = $this->saveSetting($copydir_preference);
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, $result);
    }
    
    /**
     * Save setting handler
     * @param string $copydir_preference
     * @return boolean[]|string[]
     */
    private function saveSetting($copydir_preference = '')
    {
        $this->getPrimeMoverSettings()->saveSetting(self::COPYBACKUP_DIR, $copydir_preference);
        $status = true;
        $message =  sprintf( esc_html__('Success! Custom backup directory will be %s back to default uploads backup directory when Control Panel plugin is %s.', 'prime-mover'), 
            '<strong>' . esc_html__('COPIED', 'prime-mover') . '</strong>', '<strong>' . esc_html__('DEACTIVATED', 'prime-mover') . '</strong>' );
        if ( 'true' === $copydir_preference) {
            $message =  sprintf( esc_html__('Success! Custom backup directory will %s back to default uploads backup directory when Control Panel plugin is %s.', 'prime-mover'), 
                '<strong>' . esc_html__('NOT BE COPIED', 'prime-mover') . '</strong>', '<strong>' . esc_html__('DEACTIVATED', 'prime-mover') . '</strong>' );
        }        
        return ['status' => $status, 'message' => $message];
    }
    
    /**
     * Check if user wants to copy custom backup dir to default when this plugin is deactivated
     * If setting is not set, returns TRUE by default.
     * @return boolean
     */
    public function maybeCopyCustomBackupDirToDefaultDir() 
    {     
        $current_setting = $this->getPrimeMoverSettings()->getSetting(self::COPYBACKUP_DIR);
        if ( ! $current_setting) {
            return true;
        }
        if ('true' === $current_setting) {
            return false;
        }
        return true;
    }
    
    /**
     * Show backup management setting
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupManagement::itShowsBackupManagementSetting();
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupManagement::itDoesNotShowBackupManagementWhenGearBoxDeactivated()
     */
    public function showBackupManagementSetting()
    {  
    ?>
       <h2><?php esc_html_e('Backup management', 'prime-mover')?></h2>
    <?php 
       do_action('prime_mover_show_other_backup_management');
    
       $this->getBackupDirSize()->outputBackupsDirSizeMarkup(); 
       $this->getDeleteUtilities()->outputDeleteAllBackupsMarkup();            
    }
    
    /**
     * Get Prime Mover
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     * @compatible 5.6
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
}