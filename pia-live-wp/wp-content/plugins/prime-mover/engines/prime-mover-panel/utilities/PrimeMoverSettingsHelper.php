<?php
namespace Codexonics\PrimeMoverFramework\utilities;

use Codexonics\PrimeMoverFramework\classes\PrimeMover;
use Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsConfig;
use Codexonics\PrimeMoverFramework\app\PrimeMoverSettings;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Helper class for saving Prime Mover settings
 *
 */
class PrimeMoverSettingsHelper
{
    private $prime_mover;
    private $utilities;
    private $prime_mover_settings;
    private $prime_mover_settings_config;
    private $settings;
    
    /**
     * Constructor
     * @param PrimeMover $prime_mover
     * @param array $utilities
     * @param PrimeMoverSettings $prime_mover_settings
     * @param PrimeMoverSettingsConfig $prime_mover_settings_config
     */
    public function __construct(PrimeMover $prime_mover, array $utilities, PrimeMoverSettings $prime_mover_settings, PrimeMoverSettingsConfig $prime_mover_settings_config) 
    { 
        $this->prime_mover = $prime_mover;
        $this->utilities = $utilities;
        $this->settings = $prime_mover_settings;        
        $this->prime_mover_settings_config = $prime_mover_settings_config;       
    }
    
    /**
     * Get Prime Mover
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
    
    /**
     * Get utilities
     * @return array
     */
    public function getUtilities()
    {
        return $this->utilities;
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
        return $this->prime_mover_settings_config;
    }
    
    /**
     * Save settings ajax
     * @param string $settings_identifier
     * @param boolean $settings_exist_check
     * @param string $filter
     * @param string $validation_id
     * @param boolean $cron_sched
     */
    public function saveSettings($settings_identifier = '', $settings_exist_check = true, $filter = '', $validation_id = 'settings_checkbox_validation', $cron_sched = false)
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Unauthorized, please login to WordPress and try again.', 'prime-mover')]);
        }
       
        if (!$settings_identifier) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Settings identifier not set.', 'prime-mover')]);
        }
       
        $response = [];        
        $master_config = $this->getSettingsConfig()->getMasterSettingsConfig();
        if (!isset($master_config[$settings_identifier])) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Settings configuration is not found.', 'prime-mover')]);
        }
       
        $config = $master_config[$settings_identifier];
        if (!isset($config['ajax_key']) || !isset($config['nonce']) || !isset($config['description'])) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Missing dependencies, unable to save.', 'prime-mover')]);
        }
        
        if (!isset($config['on_status']) || !isset($config['off_status'])) {
            return $this->getPrimeMoverSettings()->returnToAjaxResponse([], ['status' => false, 'message' => esc_html__('Missing dependencies, unable to save.', 'prime-mover')]);
        }
       
        if (!$filter) {
            $filter = $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter();
        }
      
        if (!$validation_id) {
            $validation_id = 'settings_checkbox_validation';
        }
        
        $settings_key = $config['ajax_key'];        
        $setting_params = $this->getPrimeMoverSettings()->prepareSettings(
            $response, 
            $settings_key, 
            $config['nonce'], 
            $settings_exist_check, 
            $filter, 
            $validation_id
        );        
        
        $on_status = $config['on_status'];
        $off_status = $config['off_status'];
        $description = $config['description'];
        $encrypt = $config['encrypted'];
        
        $blog_id = 0;
        if (is_array($setting_params)) { 
            $blog_id = key($setting_params);
            $param_value = reset($setting_params);            
        } else {
            $param_value = $setting_params;
        }
        
        $result = $this->saveSetting($param_value, $settings_key, $description, $on_status, $off_status, $encrypt, $blog_id, $cron_sched);        
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, $result);
    }
    
    /**
     * Save setting handler
     * @param string $setting_params
     * @param string $settings_key
     * @param string $description
     * @param string $on_status
     * @param string $off_status
     * @param boolean $encrypt
     * @param number $blog_id
     * @param boolean $cron_sched
     * @return boolean[]|string[]
     */
    private function saveSetting($setting_params = '', $settings_key = '', $description = '', $on_status = '', $off_status = '', $encrypt = false, $blog_id = 0, $cron_sched = false)
    {
        $this->getPrimeMoverSettings()->saveSetting($settings_key, $setting_params, $encrypt, $blog_id, $cron_sched);
        $status = true;
        $html_on_message = false;
        $esc_on_message = esc_html($on_status);
        
        if($on_status !== $esc_on_message) {
            $html_on_message = true;
        }
        
        if ($html_on_message) {
            $message =  sprintf(esc_html__('Success! %s is %s.', 'prime-mover'), esc_html($description), $on_status);
        } else {
            $message =  sprintf(esc_html__('Success! %s is %s.', 'prime-mover'), esc_html($description), '<strong>' . esc_html($on_status) . '</strong>');
        }
        
        
        if ('false' === $setting_params) {
            $message =  sprintf( esc_html__('Success! %s is %s.', 'prime-mover'), esc_html($description), '<strong>' . esc_html($off_status) . '</strong>');
        }
        
        return ['status' => $status, 'message' => $message];
    }
}
