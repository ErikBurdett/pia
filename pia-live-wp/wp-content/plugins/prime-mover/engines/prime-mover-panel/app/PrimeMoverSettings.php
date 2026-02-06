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
use Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization;
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 *
 */
class PrimeMoverSettings
{
    private $prime_mover;
    private $system_authorization;
    private $openssl_utilities;
    private $settings_markup;
    private $component_utilities;
    private $freemius_integration;
    private $config_utilities;
    private $prime_mover_sites;
    private $default_autobackup_schedules;
    private $test_autobackup_schedules;
    private $autobackup_schedules;
    
     /**
     * Constructor
     * @param PrimeMover $PrimeMover
     * @param PrimeMoverSystemAuthorization $system_authorization
     * @param array $utilities
     * @param PrimeMoverSettingsMarkups $settings_markup
     */
    public function __construct(PrimeMover $PrimeMover, PrimeMoverSystemAuthorization $system_authorization, array $utilities, PrimeMoverSettingsMarkups $settings_markup) 
    {
        $this->prime_mover = $PrimeMover;
        $this->system_authorization = $system_authorization;
        $this->openssl_utilities = $utilities['openssl_utilities'];
        $this->settings_markup = $settings_markup;
        $this->component_utilities = $utilities['component_utilities'];
        $this->freemius_integration = $utilities['freemius_integration'];
        $this->config_utilities = $utilities['config_utilities'];
        $this->prime_mover_sites = 'prime_mover_sites';
        
        $this->default_autobackup_schedules = [
            'prime_mover_autobackup_daily' => [
                'interval' => DAY_IN_SECONDS,
                'display'  => __('Once Daily', 'prime-mover'),
            ],
            'prime_mover_autobackup_biweekly' => [
                'interval' => (WEEK_IN_SECONDS / 2),
                'display'  => __('Twice weekly', 'prime-mover'),
            ],
            'prime_mover_autobackup_weekly' => [
                'interval' => WEEK_IN_SECONDS,
                'display'  => __('Once Weekly', 'prime-mover'),
            ],
            'prime_mover_autobackup_bimonthly' => [
                'interval' => (MONTH_IN_SECONDS / 2),
                'display'  => __('Twice monthly', 'prime-mover'),
            ],
            'prime_mover_autobackup_monthly' => [
                'interval' => MONTH_IN_SECONDS,
                'display'  => __('Once monthly', 'prime-mover'),
            ]
        ]; 
        
        $this->test_autobackup_schedules = [        
            'prime_mover_every_180_seconds' => [
                'interval'  => 180,
                'display'   => __('Once in 3 minutes', 'prime-mover')
            ],        
            'prime_mover_every_540_seconds' => [
                'interval'  => 540,
                'display'   => __('Once in 9 minutes', 'prime-mover')
            ],        
            'prime_mover_every_900_seconds' => [
                 'interval'  => 900,
                 'display'   => __('Once in 15 minutes', 'prime-mover')
            ],        
            'prime_mover_every_1260_seconds' => [
                 'interval'  => 1260,
                 'display'   => __('Once in 21 minutes', 'prime-mover')
            ]
         ];
        
        $this->autobackup_schedules = [];
    }
    
    /**
     * Get test auto backup schedules
     * @return array
     */
    public function getTestAutoBackupSchedules()
    {
        return $this->test_autobackup_schedules;
    }
    
    /**
     * Get default autobackup schedules
     * @return array
     */
    public function getDefaultAutoBackupSchedules()
    {
        return $this->default_autobackup_schedules;
    }
        
    /**
     * Get auto backup schedules
     * @return array
     */
    public function getAutoBackupSchedules()
    {
        $autobackup_schedules = $this->getDefaultAutoBackupSchedules();
        if (PRIME_MOVER_CRON_TEST_MODE) {
            $autobackup_schedules = array_merge($this->getTestAutoBackupSchedules(), $autobackup_schedules);
        }
        
        return $autobackup_schedules;
    }
    
    /**
     * Get Prime Mover site specific settings identifier
     * @return string
     */
    public function getPrimeMoverSites()
    {
        return $this->prime_mover_sites;
    }

    /**
     * Get config utilities
     */
    public function getConfigUtilities()
    {
        return $this->config_utilities;
    }
    
    /**
     * Get Freemius integration instance
     */
    public function getFreemiusIntegration()
    {
        return $this->freemius_integration;
    }
    
    /**
     * Get component utilities
     * @return array
     */
    public function getComponentUtilities()
    {
        return $this->component_utilities;
    }
    
    /**
     * Get settings markup
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups
     */
    public function getSettingsMarkup()
    {
        return $this->settings_markup;
    }
    
    /**
     * Get openssl utilities
     * @return array
     */
    public function getOpenSSLUtilities()
    {
        return $this->openssl_utilities;
    }
    
    /**
     * Init hooks
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itAddsInitHooks() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itDoesNotAddInitHooksWhenNotAuthorized()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itChecksIfHooksAreOutdated()
     */
    public function initHooks()
    {
        add_filter('prime_mover_get_setting', [$this, 'getSettingApi'], 10, 7);        
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        add_filter('prime_mover_filter_error_output', [$this, 'appendControlPanelSettingsToLog'], 50, 1);        
        add_action('prime_mover_before_db_processing', [$this, 'backupControlPanelSettings']);
        add_action('prime_mover_before_only_activated', [$this, 'restoreControlPanelSettings']); 
        
        add_action('prime_mover_save_setting', [$this, 'saveSettingApi'], 10, 5);
    }   
 
    /**
     * Save settings API
     * @param string $setting
     * @param mixed $value
     * @param boolean $encrypt
     * @param number $blog_id
     * @param boolean $cron_sched
     */
    public function saveSettingApi($setting = '', $value = null, $encrypt = false, $blog_id = 0, $cron_sched = false)
    {
        $this->saveSetting($setting, $value, $encrypt, $blog_id, $cron_sched); 
    }
    
    /**
     * Convert settings to text area output
     * @param string $setting
     * @param boolean $display_key
     * @param boolean $media_settings
     * @param boolean $decrypt
     * @param number $blog_id
     * @return string
     */
    public function convertSettingsToTextAreaOutput($setting = '', $display_key = false, $media_settings = false, $decrypt = false, $blog_id = 0)
    {        
        $setting = $this->getSetting($setting, $decrypt, '', true, $blog_id);
        $ret = '';
        if (!is_array($setting) || empty($setting)) {
            return $ret;
        }
        
        if ($media_settings) {
            foreach($setting as $blog_id => $resources) {
                if ( ! is_array($resources) ) {
                    continue;
                }
                foreach ($resources as $resource => $values) {
                    $identifier = $resource . '-' . $blog_id;
                    $ret .= $identifier . ':' . $values . PHP_EOL;
                }
            }
            
        } else {
            foreach($setting as $data => $value) {
                if ($display_key) {
                    $ret .= $data . ':' . $value . PHP_EOL;
                } else {
                    $ret .= $value . PHP_EOL;
                }
            }
        }
        
        return $ret;
    }
    
    /**
     * Convert media settings to text area output
     * @return string
     */
    public function convertMediaSettingsToTextAreaOutput($upload_setting = '')
    {
        return $this->convertSettingsToTextAreaOutput($upload_setting, false, true, false);
    }
    
    /**
     * Restore all fallback settings after dB processing
     */
    public function restoreControlPanelSettings()
    {                
        $this->getComponentUtilities()->restoreAllFallBackSettings(0, 0, false, true);
    }
    
    /**
     * Backup control panel settings before dB processing
     */
    public function backupControlPanelSettings()
    {
        $this->getComponentUtilities()->backupControlPanelSettings();
    }
   
    /**
     * Append control panel settings to log
     * @param array $error_output
     * @return array
     */
    public function appendControlPanelSettingsToLog($error_output = [])
    {
        if ( ! is_array($error_output) ) {
            return $error_output;
        }
        
        $error_output['prime_mover_panel_settings'] = $this->getAllPrimeMoverSettings();        
        return $error_output;
    }
    
    /**
     * Get setting API for other plugins
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itGetSettingsApiWhenSet()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalValueWhenSettingNotSet()
     * @param mixed $value
     * @param string $setting
     * @param boolean $decrypt
     * @param string $default
     * @param boolean $return_default_if_no_key
     * @param number $blog_id
     * @param boolean $cron_sched
     * @return boolean|string
     */
    public function getSettingApi($value, $setting = '', $decrypt = false, $default = '', $return_default_if_no_key = false, $blog_id = 0, $cron_sched = false) 
    {
        if (!$setting ) {
            return $value;
        }
        
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            if (!wp_doing_cron()) {
                return $value;
            }            
            
            $encrypted_settings = $this->getEncryptedSettings();
            $encrypted_settings = array_keys($encrypted_settings);  
            
            if (in_array($setting, $encrypted_settings)) {  
                return $value;
            }
        }     
        
        return $this->getSetting($setting, $decrypt, $default, $return_default_if_no_key, $blog_id, $cron_sched);
    }
    
    /**
     * Get control panel settings name
     * @return string
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesSettingWhenAllIsSet
     */
    private function getControlPanelSettingsName()
    {        
        return $this->getPrimeMover()->getSystemInitialization()->getControlPanelSettingsName();
    }
    
    /**
     * Check if we load gearbox related settings
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsFalseIfGearBoxPluginIsDeactivated() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsTrueIfGearBoxPluginIsActivated()
     * @return boolean
     */
    public function maybeLoadGearBoxRelatedSettings()
    {
        $ret = false;
        if (defined('PRIME_MOVER_GEARBOX_VERSION')) {
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Handle setting error
     * @param array $settings_post
     * @param array $response
     * @param string $setting
     */
    private function handleSettingsError($settings_post = [], $response = [], $setting = '')
    {
        $error_msg = '<ul>';
        foreach ($settings_post['validation_errors'] as $error) {
            $error_msg .= '<li>' . $error . '</li>';
        }
        $error_msg .= '</ul>';
        
        $response['message'] = $error_msg;
        return $response;
    }
   
    /**
     * Prepare settings posted from AJAX
     * @tested TestPrimeMoverDeleteUtilities::itDeleteAllBackups()
     * @param array $response
     * @param string $setting_string
     * @param string $nonce_name
     * @param boolean $settings_exist_check
     * @param string $setting_filter
     * @param string $validation_id
     * @return string[]|string
     */
    public function prepareSettings(array $response, $setting_string = '', $nonce_name = '', $settings_exist_check = true, 
        $setting_filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $validation_id = '')
    {
        $response['save_status'] = false;
        
        if (! $this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            $response['message'] = esc_html__('Error ! Unauthorized', 'prime-mover');
            wp_send_json($response);
        }
        
        if ( ! $nonce_name || ! $setting_string ) {
            $response['message'] = esc_html__('Error ! Undefined settings.', 'prime-mover');
            wp_send_json($response);
        }
        
        $args = [
            $setting_string => $setting_filter,
            'savenonce' => $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
            'action' => $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
            'prime_mover_panel_js_blogid' => FILTER_SANITIZE_NUMBER_INT
        ];
        
        $settings_post = $this->getPrimeMover()->getSystemInitialization()->getUserInput('post', $args, $validation_id, '', 0, true, true);
        if ( ! empty($settings_post['validation_errors']) && is_array($settings_post['validation_errors'])) {
            $response = $this->handleSettingsError($settings_post, $response, $setting_string);
            wp_send_json($response);
        }
        if ( ! isset($settings_post['savenonce'] ) ) {
            $response['message'] = esc_html__('Error ! Unauthorized', 'prime-mover');
            wp_send_json($response);
        }
        
        if ( ! $this->getPrimeMover()->getSystemFunctions()->primeMoverVerifyNonce($settings_post['savenonce'], $nonce_name) ) {
            $response['message'] = esc_html__('Error ! Unauthorized', 'prime-mover');
            wp_send_json($response);
        } 
        
        if ($settings_exist_check && empty($settings_post[$setting_string])) {
            $response['message'] = esc_html__('Error ! Invalid setting being saved. Please check again.', 'prime-mover');
            wp_send_json($response);
        }
        if (FILTER_VALIDATE_INT === $setting_filter && $settings_post[$setting_string] < 0) {
            $response['message'] = esc_html__('Error ! This value cannot be negative. Please check again.', 'prime-mover');
            wp_send_json($response);
        }
 
        $blog_id = 0;
        if (isset($settings_post['prime_mover_panel_js_blogid'])) {
            $blog_id = $settings_post['prime_mover_panel_js_blogid'];
        }
        
        $blog_id = (int)$blog_id;        
        if ($blog_id) {   
            return [$blog_id => trim($settings_post[$setting_string])];   
            
        } else {
            return trim($settings_post[$setting_string]);  
        }             
    }
    
    /**
     * Return settings processing to AJAX response
     * @param array $response
     * @param array $result
     * @tested TestPrimeMoverDeleteUtilities::itDeleteAllBackups()
     */
    public function returnToAjaxResponse(array $response, array $result)
    {
        $response['save_status'] = false;
        $response['message'] = esc_html__('Error!', 'prime-mover');
        
        if (isset($result['status'])) {
            $response['save_status'] = $result['status'];
        }
        if (isset($result['message'])) {
            $response['message'] = $result['message'];
        }       
        if (isset($result['saved_settings'])) {
            $response['saved_settings'] = $result['saved_settings'];
        }
        if (isset($result['reload'])) {
            $response['reload'] = $result['reload'];
        }
        wp_send_json($response);        
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
    
    /**
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsFalseIfSettingDoesNotExists()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsSettingIfItExists()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itGetsEncryptedSetting()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalValueIfEncryptedKeyNotSet() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalValueIfValueIsNotEncoded()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalArraySettingIfNotEncoded() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itGetsEncryptedArraySetting() 
     * @param string $setting
     * @param boolean $decrypt
     * @param string $default
     * @param boolean $return_default_if_no_key
     * @param number $blog_id
     * @param boolean $cron_sched
     * @return boolean|string
     */
    public function getSetting($setting = '', $decrypt = false, $default = '', $return_default_if_no_key = false, $blog_id = 0, $cron_sched = false) 
    {
        if (!$setting ) {
            return false;
        }
        if ($decrypt && !$this->getPrimeMover()->getSystemInitialization()->getDbEncryptionKey() && $return_default_if_no_key) {
            return $default;
        }
        if ($decrypt && $this->getPrimeMover()->getSystemInitialization()->getDbEncryptionKey() && $this->isKeySignatureChanged() && $return_default_if_no_key) {
            return $default;
        }        
              
        $settings = $this->getAllPrimeMoverSettings();        
        if ($blog_id && $cron_sched && isset($settings[$setting][$blog_id])) {
            
            return $this->maybeDecryptSetting($settings[$setting][$blog_id], $decrypt);
            
        } elseif ($blog_id && isset($settings[$this->getPrimeMoverSites()][$blog_id][$setting])) {  
            
            return $this->maybeDecryptSetting($settings[$this->getPrimeMoverSites()][$blog_id][$setting], $decrypt);  
            
        } elseif (isset($settings[$setting]) && false === $cron_sched) {
            return $this->maybeDecryptSetting($settings[$setting], $decrypt);
            
        }
        
        if ($default) {
            return $default;
        }
        
        return false;
    }
    
    /**
     * Get backup schedules
     * @return array
     */
    public function getBackupSchedules()
    {       
        $settings = $this->getAllPrimeMoverSettings();
        
        if (!isset($settings['automatic_backup_custom_schedule'])) {
            return $this->returnAutoBackupImplementation($settings);
        }
        
        $custom_backup_schedule_settings = $settings['automatic_backup_custom_schedule'];
        if (!is_array($custom_backup_schedule_settings) || empty($custom_backup_schedule_settings)) {
            return $this->returnAutoBackupImplementation($settings);
        }
        
        $implemented_custom_backup_schedule = array_keys($this->parseCustomScheduleFromConfig());
        $settings = $this->cleanAutoBackupImplementation($settings, $custom_backup_schedule_settings, $implemented_custom_backup_schedule);        
        
        return $this->returnAutoBackupImplementation($settings);   
    }
    
    /**
     * Clean up auto backup implementation with outdated custom backup schedules
     * @param array $settings
     * @param array $custom_backup_schedule_settings
     * @param array $implemented_custom_backup_schedule
     * @return string
     */
    protected function cleanAutoBackupImplementation($settings = [], $custom_backup_schedule_settings = [], $implemented_custom_backup_schedule = [])
    {
        $outdated = [];
        $update = false;
        foreach ($custom_backup_schedule_settings as $custom_backup_schedule_setting) {
            if (!in_array($custom_backup_schedule_setting, $implemented_custom_backup_schedule)) {
                $outdated[] = $custom_backup_schedule_setting;
            }
        }
       
        $automatic_backup_export_schedule = [];
        if (isset($settings['automatic_backup_export_schedule'])) {
            $automatic_backup_export_schedule = $settings['automatic_backup_export_schedule'];
        }
       
        foreach ($outdated as $oudated_schedule) {            
            $blog_ids = array_keys($automatic_backup_export_schedule, $oudated_schedule);
            foreach ($blog_ids as $blog_id) {
                $blog_id = (int)$blog_id;
              
                if (isset($settings['automatic_backup_export_schedule'][$blog_id])) {
                    $update = true;
                    unset($settings['automatic_backup_export_schedule'][$blog_id]);
                }
              
                if (isset($settings['prime_mover_sites'][$blog_id]['automatic_backup_subsite_enabled'])) {
                    $update = true;
                    $settings['prime_mover_sites'][$blog_id]['automatic_backup_subsite_enabled'] = 'false';
                }
               
                if (isset($settings['automatic_backup_implementation'][$blog_id])) {
                    $update = true;
                    unset($settings['automatic_backup_implementation'][$blog_id]);
                }
               
                if (1 === $blog_id && !is_multisite() && isset($settings['automatic_backup_enabled'])) {
                    $update = true;
                    $settings['automatic_backup_enabled'] = 'false';                    
                }
               
                if (isset($settings['automatic_backup_initialized'][$blog_id])) {
                    $update = true;
                    unset($settings['automatic_backup_initialized'][$blog_id]);
                }                
            }
           
            foreach ($settings['automatic_backup_custom_schedule'] as $k =>$v) {
                if ($v === $oudated_schedule) {
                    $update = true;
                    unset($settings['automatic_backup_custom_schedule'][$k]);
                }
            }            
        }
        
        if ($update) {
            $this->getComponentUtilities()->updateAllPrimeMoverSettings($settings);
        }        
        
        return $settings;
    }
    
    /**
     * Parse custom schedule from config
     * Returns ONLY validated values
     * @return boolean|NULL|mixed
     */
    public function parseCustomScheduleFromConfig()
    {
        $custom_schedules = null;
        $valid_schedule = [];
        
        if (defined('PRIME_MOVER_AUTOBACKUP_CUSTOM_SCHEDULES') && PRIME_MOVER_AUTOBACKUP_CUSTOM_SCHEDULES) {
            $custom_schedules = json_decode(PRIME_MOVER_AUTOBACKUP_CUSTOM_SCHEDULES, true);
        }
        
        if (!is_array($custom_schedules) || empty($custom_schedules)) {
            return [];
        }        
        
        foreach ($custom_schedules as $identifier => $custom_schedule) {
            $identifier = sanitize_text_field($identifier);
            if (!$identifier) {
                continue;
            }
            
            if (!is_array($custom_schedule) || !isset($custom_schedule['interval']) || !isset($custom_schedule['display'])) {
                continue;
            }
            
            $interval = $custom_schedule['interval'];
            $interval = (int)$interval;
            if (!$interval || $interval < 0) {
                continue;
            }
            
            $display = $custom_schedule['display'];
            $display = sanitize_text_field($display);
            
            if (!$display) {
                continue;
            }

            $valid_schedule[$identifier] = [
                'interval' => $interval,
                'display' => $display                
            ];
        }
        
        return $valid_schedule;
    }
    
    /**
     * Get auto backup implementation
     * @param array $settings
     * @return array
     */
    protected function returnAutoBackupImplementation($settings = [])
    {
        if (isset($settings['automatic_backup_implementation'])) {
            return $settings['automatic_backup_implementation'];
        }
        return [];   
    }
    
    /**
     * Get all Prime Mover settings
     * @return mixed|boolean|NULL|array
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itGetsAllPrimeMoverSettings()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalValueIfEncryptedKeyNotSet()
     */
    public function getAllPrimeMoverSettings() 
    {
        return $this->getComponentUtilities()->getAllPrimeMoverSettings();
    }

    /**
     * Delete all settings
     * @param boolean $force_to_option
     * @param boolean $everything
     * @return boolean
     */
    public function deleteAllPrimeMoverSettings($force_to_option = false, $everything = false)
    {        
        return $this->getComponentUtilities()->deleteAllPrimeMoverSettings($force_to_option, $everything);
    }
    
    /**
     * Restore all Prime Mover settings
     * @param array $settings
     */
    public function restoreAllPrimeMoverSettings($settings = [])
    {        
        $this->getComponentUtilities()->restoreAllPrimeMoverSettings($settings);
    }
    
    /**
     * Decrypt array setting if requested
     * @param array $value
     * @param string $encryption_key
     * @return string[]
     */
    private function decryptArraySetting($value = [], $encryption_key = '')
    {
        return $this->getOpenSSLUtilities()->decryptArraySetting($value, $encryption_key);
    }
    
    /**
     * Maybe decrypt setting
     * @param $value
     * @param boolean $decrypt
     * @return string
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itGetsEncryptedSetting()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnsOriginalValueIfEncryptedKeyNotSet()
     */
    protected function maybeDecryptSetting($value, $decrypt = false)
    {
        return $this->getOpenSSLUtilities()->maybeDecryptSetting($value, $decrypt);
    }
        
    /**
     * Encrypt array setting if requested
     * @param array $value
     * @param string $encryption_key
     * @return string[]
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesEncryptedArraySettings() 
     */
    private function encryptArraySetting($value = [], $encryption_key = '')
    {
        return $this->getOpenSSLUtilities()->encryptArraySetting($value, $encryption_key);
    }
    
    /**
     * Encrypt setting
     * @param $value
     * @param boolean $encrypt
     * @return string
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesEncryptedSettings() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itDoesNotSaveEncryptedSettingWhenKeyIsNotSet() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesEncryptedArraySettings() 
     */
    protected function maybeEncryptSetting($value, $encrypt = false) 
    {        
        return $this->getOpenSSLUtilities()->maybeEncryptSetting($value, $encrypt);
    }
    
    /**
     * Save setting
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesSettingWhenAllIsSet()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itReturnFalseIfSettingDoesNotExist()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesNewSettings()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesEncryptedSettings() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itDoesNotSaveEncryptedSettingWhenKeyIsNotSet() 
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverSettings::itSavesEncryptedArraySettings() 
     * @param string $setting
     * @param $value
     * @param boolean $encrypt
     * @param number $blog_id
     * @param boolean $cron_sched
     * @return boolean
     */
    public function saveSetting($setting = '', $value = null, $encrypt = false, $blog_id = 0, $cron_sched = false) 
    {
        if (!$setting ) {
            return false;
        }
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        }
        $settings_array = $this->getAllPrimeMoverSettings();
        if (!is_array($settings_array) ) {
            $settings_array = [];
        }
        $value = $this->maybeEncryptSetting($value, $encrypt);
        $old_value = $settings_array;
        if ($blog_id) {
            if ($cron_sched) {
                $settings_array[$setting][$blog_id] = $value;
            } else {
                $settings_array[$this->getPrimeMoverSites()][$blog_id][$setting] = $value;
            }            
        } else {
            $settings_array[$setting] = $value;
        }        
        
        $settings_array = apply_filters('prime_mover_before_saving_settings', $settings_array, $blog_id, $cron_sched, $encrypt, $setting, $old_value);
        $this->getComponentUtilities()->updateAllPrimeMoverSettings($settings_array);        
    }
    
    /**
     * Save settings helper
     * @param string $data_indicator
     * @param string $nonce_name
     * @param boolean $settings_check
     * @param string $sanitizing_filter
     * @param string $setting_name
     * @param boolean $encrypt
     * @param string | array $success_message
     * @param string $error_message
     */
    public function saveHelper($data_indicator = '', $nonce_name = '', $settings_check = false,
        $sanitizing_filter = FILTER_SANITIZE_NUMBER_INT, $setting_name = '', $encrypt = false, $success_message = null, 
        $error_message = '', $datatype = 'text', $validation_id = '' )
    {
        $response = [];
        $setting_prepared = $this->prepareSettings($response, $data_indicator, $nonce_name, $settings_check, $sanitizing_filter, $validation_id);
        
        $this->saveSetting($setting_name, $setting_prepared, $encrypt);
        $message = $error_message;
        $status = false;
        
        if ($setting_prepared) {
            $status = true;
            if ('text' === $datatype) {
                $message = $success_message;
            }
            if ('checkbox' === $datatype && isset($success_message[$setting_prepared])) {
                $message = $success_message[$setting_prepared];
            }
        }
        
        $result = ['status' => $status, 'message' => $message];
        $this->returnToAjaxResponse($response, $result);
    }
    
    /**
     * Maybe update all encrypted settings when a key is changed
     * This should support even if the original key is empty (not yet encrypted before)
     * @param string $original_key
     * @param string $new_key
     */
    public function maybeUpdateAllEncryptedSettings($original_key = '', $new_key = '')
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        if (!$new_key) {
            return;
        }
        if ($original_key === $new_key) {
            return;   
        }
        
        $original_settings = $this->getAllPrimeMoverSettings();
        if (!$original_settings) {
            return;
        }
        $new_settings = $original_settings;     
        $encrypted_settings = $this->getEncryptedSettings();
        if (empty($encrypted_settings)) {
            return;
        }
        $encrypted_settings = array_keys($encrypted_settings);
        foreach ($original_settings as $setting => $original_value) {
            if (!in_array($setting, $encrypted_settings)) {
                continue;
            }
            
            $decrypted_array = [];            
            $decrypted_string = '';            
            
            if (is_array($original_value)) {
                $decrypted_array = $this->decryptArraySetting($original_value, $original_key);
                $new_settings[$setting] = $this->encryptArraySetting($decrypted_array, $new_key);
                
            } else {
               
                $decrypted_string = $this->getOpenSSLUtilities()->openSSLDecrypt($original_value, $original_key);
                $new_settings[$setting] = $this->getOpenSSLUtilities()->openSSLEncrypt($decrypted_string, $new_key); 
            }            
        }
       
        $this->getComponentUtilities()->updateAllPrimeMoverSettings($new_settings);        
        do_action('prime_mover_maybe_update_other_encrypted_settings', $original_key, $new_key);
    }
    
    /**
     * Get encrypted setting
     * @return array
     */
    protected function getEncryptedSettings()
    {
        $registered_settings = apply_filters('prime_mover_register_setting', []);
        if (is_array($registered_settings) && !empty($registered_settings)) {
            return wp_filter_object_list($registered_settings, ['encrypted' => true]);
        }
        return [];
    }
    
    /**
     * Get encryption from WordPress configuration file
     * @return string
     */
    public function getEncryptionKeyFromConfig()
    {
        $key = '';
        if (defined('PRIME_MOVER_DB_ENCRYPTION_KEY') && PRIME_MOVER_DB_ENCRYPTION_KEY) {
            $key = PRIME_MOVER_DB_ENCRYPTION_KEY;
        }
        return $key;
    }
    
    /**
     * Generate key
     * @param boolean $http_api
     * @return string
     */
    public function generateKey($http_api = false)
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return wp_generate_password(64, false, false);
        }
        $use_native = false;
        $freemius_object = $this->getFreemiusIntegration()->getFreemius();
        if (!is_object($freemius_object)) {
            $use_native = true;
        }
        
        $license = $this->getFreemiusIntegration()->getLicense(false);
        if (!is_object($license)) {
            $use_native = true;
        }
        if (false === $use_native && is_object($license) && !property_exists($license, 'secret_key')) {
            $use_native = true;
        }
        if ($use_native) {
            return wp_generate_password(64, false, false);
        } else {
            $key = $license->secret_key;
            return $this->getPrimeMover()->getSystemFunctions()->hashString($key, $http_api);
        }
    }
  
    /**
     * Get lock files folder path
     * As substitute for ABSPATH
     * @return string
     */
    public function getLockFilesFolderPath()
    {
        return trailingslashit(wp_normalize_path($this->getPrimeMover()->getSystemInitialization()->getLockFilesFolder()));
    }
    
    /**
     * Is key signature changed
     * @return boolean
     */
    public function isKeySignatureChanged()
    {
        $auth = $this->getPrimeMover()->getSystemInitialization()->getAuthKey();
        if (!$auth) {
            return true;
        }
        
        $enc_key_cons = $this->getPrimeMover()->getSystemInitialization()->getDbEncryptionKey();
        $auth = sha1($auth);
        $hash = sha1($enc_key_cons . $auth);
        
        $ext = '.primemoversignature_file';
        $file = $hash . $ext;
        $path = $this->getLockFilesFolderPath() . $file;
        
        $changed = true;
        if ($this->getPrimeMover()->getSystemFunctions()->nonCachedFileExists($path)) {
            $changed = false;
        }
        return $changed;
    }
}