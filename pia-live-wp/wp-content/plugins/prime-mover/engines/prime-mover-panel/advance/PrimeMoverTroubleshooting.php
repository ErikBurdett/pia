<?php
namespace Codexonics\PrimeMoverFramework\advance;

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
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverTroubleshootingMarkup;
use WP_Debug_Data;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling troubleshooting options
 *
 */
class PrimeMoverTroubleshooting
{
    private $prime_mover;
    private $system_authorization;
    private $settings;
    private $troubleshooting_markup;
    private $maybe_log;
    private $utilities;
    
    const TROUBLESHOOTING_KEY = 'enable_troubleshooting';
    const PERSIST_TROUBLESHOOTING_KEY = 'persist_troubleshooting';
    const ENABLE_JS_LOG = 'enable_js_troubleshooting';
    const ENABLE_UPLOAD_JS_LOG = 'enable_js_upload_troubleshooting';
    const ENABLE_TURBO_MODE = 'enable_turbo_mode';
    const DISABLE_USER_DIFF_CHECK = 'disable_user_diff';
    
    /**
     * Constructor
     * @param PrimeMover $PrimeMover
     * @param PrimeMoverSystemAuthorization $system_authorization
     * @param array $utilities
     * @param PrimeMoverSettings $settings
     */
    public function __construct(PrimeMover $PrimeMover, PrimeMoverSystemAuthorization $system_authorization, 
        array $utilities, PrimeMoverSettings $settings, PrimeMoverTroubleshootingMarkup $troubleshooting_markup) 
    {
        $this->prime_mover = $PrimeMover;
        $this->system_authorization = $system_authorization;
        $this->settings = $settings;
        $this->troubleshooting_markup = $troubleshooting_markup;
        $this->maybe_log = false;
        $this->utilities = $utilities;
    }
    
    /**
     * Get component auxiliary
     */
    public function getComponentAuxiliary()
    {
        $utilities = $this->getUtilities();
        return $utilities['component_utilities'];
    }
    
    /**
     * Get config utilities
     * Returns config utilities object
     */
    public function getConfigUtilities()
    {
        $utilities = $this->getUtilities();
        return $utilities['config_utilities'];
    }
    
    /**
     * Get utilities
     * @return string|array
     */
    public function getUtilities()
    {
        return $this->utilities;
    }
    
    /**
     * Get Freemius integration
     */
    public function getFreemiusIntegration()
    {
        $utilities = $this->getUtilities();
        return $utilities['freemius_integration'];
    }
    
    /**
     * Get shutdown utilities
     */
    public function getShutdownUtilities()
    {
        return $this->getFreemiusIntegration()->getShutdownUtilities();
    }
    
    /**
     * Get troubleshooting markup
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverTroubleshootingMarkup
     */
    public function getTroubleShootingMarkup()
    {
        return $this->troubleshooting_markup;
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
     * Init hooks
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverTroubleshooting::itChecksIfHooksAreOutdated()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverTroubleshooting::itAddsInitHooks()
     */
    public function initHooks() 
    {
        add_action('wp_ajax_prime_mover_save_troubleshooting_settings', [$this,'saveEnableTroubleShootingSetting']);
        add_action('wp_ajax_prime_mover_clear_troubleshooting_log', [$this,'clearTroublesShootingLog']);
        add_action('wp_ajax_prime_mover_save_persist_troubleshooting_settings', [$this,'saveEnablePersistTroubleShootingSetting']);
        
        add_action('wp_ajax_prime_mover_save_js_troubleshooting_settings', [$this,'saveEnableJsTroubleShootingSetting']);
        add_action('wp_ajax_prime_mover_save_uploadjs_troubleshooting_setting', [$this,'saveEnableUploadJsTroubleShootingSetting']);
        add_action('wp_ajax_prime_mover_save_turbomode_setting', [$this,'saveEnableTurboModeSetting']);
        
        add_action('prime_mover_advance_settings', [$this, 'showTroubleShootingSetting'], 10);
        add_action('init', [$this, 'streamDownloadTroubleShootingLog' ], 200);        
        add_filter('prime_mover_persist_troubleshooting_logs', [$this, 'maybePersistTroubleShootingLog'], 10, 1);

        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderEnableTroubleShootingLogMarkup'], 10);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderDownloadLogMarkup'], 11);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderClearLogCallBack'], 12);
        
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderPersistTroubleShootingMarkup'], 13);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderJsTroubleShootingLogMarkup'], 14);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderUploadJsTroubleShootingLogMarkup'], 15);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderTurboModeMarkup'], 16);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderClearLockedSettingsCallBack'], 17);
        add_action('prime_mover_render_troubleshooting_markup', [$this, 'renderDisableUserDiffMarkup'], 18);
        
        add_filter('prime_mover_enable_turbo_mode_setting', [$this, 'maybeEnableTurboModeSetting'], 10, 1);
        add_filter('prime_mover_enable_upload_js_debug', [$this, 'maybeEnableJsUploadErrorAnalysis'], 10, 1);
        add_action('prime_mover_advance_settings', [$this, 'showSiteInfoButton'], 20);
        add_filter('prime_mover_enable_js_error_analysis', [$this, 'maybeEnableJsErrorAnalysis'], 10, 1);
        
        add_action('admin_init', [$this,'exportSiteInformation'], 300);
        add_action('prime_mover_validated_streams', [$this, 'refreshSiteInfoLog'], 10, 1);
        add_filter('prime_mover_disable_serverside_log', [$this, 'maybeDisableTroubleShootingLog'], 10, 1);
        
        add_filter('prime_mover_control_panel_js_object', [$this, 'addSettingsToJs'], 10, 1);
        add_filter('prime_mover_register_setting', [$this, 'registerSetting'], 10, 1);
        add_action('wp_ajax_prime_mover_save_disable_user_diff_settings', [$this,'saveDisableUserDiffSetting']);
        
        add_action('prime_mover_before_doing_export', [$this, 'maybeLog']);
        add_action('prime_mover_before_doing_import', [$this, 'maybeLog']);
        add_filter( 'prime_mover_filter_error_output', [ $this, 'maybeAddWpDebugInfo'], 400, 1);
        
        add_action('init', [$this, 'streamDownloadAutoBackupLog' ], 300);
        add_action('wp_ajax_prime_mover_clear_automatic_backup_log', [$this,'clearAutoBackupLog']);
        add_action('init', [$this, 'streamDownloadRuntimeErrorLog' ], 400);
        
        add_action('wp_ajax_prime_mover_clear_runtime_error_log', [$this,'clearRuntimeErrorLog']);
        add_action('wp_ajax_prime_mover_clear_autobackup_init_meta', [$this,'clearAutoBackupInitKey']);
        add_action('wp_ajax_prime_mover_clear_locked_settings', [$this,'clearLockedSettings']);
        
        add_filter('prime_mover_maybe_bailout_user_diff_check', [$this, 'maybeBailOutUserSiteDiff'], 10, 3);
    }

    /**
     * Maybe bail out user site diff
     * @param boolean $bailout
     * @param array $ret
     * @param number $blog_id
     * @return string|boolean
     */
    public function maybeBailOutUserSiteDiff($bailout = false, $ret = [], $blog_id = 0)
    {
        $setting = $this->getPrimeMoverSettings()->getSetting(self::DISABLE_USER_DIFF_CHECK);
        if (!$setting) {
            return $bailout;
        }
        
        if (is_multisite() && false === $this->getPrimeMover()->getSystemFunctions()->isFreshMultisiteMainSite($blog_id)) {
            return $bailout;
        }
        
        if ('true' === $setting) {
            return true;
        } 
        
        return $bailout;           
    }
    
    /**
     * Clear auto backup init key
     */
    public function clearAutoBackupInitKey()
    {
        $response = [];
        $clear_init = $this->getPrimeMoverSettings()->prepareSettings($response, 'clear_autobackup_init_meta_key',
            'prime_mover_clear_autobackup_init_meta_nonce', false, $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter());
        
        $status = false;
        $message = esc_html__('Clear auto backup init key fails.', 'prime-mover');
        
        $blog_id = 0;
        $value = '';
        
        if (is_array($clear_init)) {
            $blog_id = key($clear_init);
        }
        
        if ($blog_id && isset($clear_init[$blog_id])) {
            $value = $clear_init[$blog_id];
        }
        
        $blog_id = (int)$blog_id;
        $user_id = $this->getPrimeMover()->getSystemFunctions()->getAutoBackupUser();
        $user_id = (int)$user_id;
        
        if ('confirmed' === $value && $blog_id && $user_id) {
            $meta_key = $this->getPrimeMover()->getSystemInitialization()->getAutoBackupInitMeta($blog_id, $user_id);
            delete_user_meta($user_id, $meta_key);
            $status = true;
        }       
        
        if ($status) {
            $message = esc_html__('Clear auto backup init key success.', 'prime-mover');
        }
        
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, ['status' => $status, 'message' => $message]);
    }
    
    /**
     * Clear runtime error log
     */
    public function clearRuntimeErrorLog()
    {
        $response = [];
        $clearlog = $this->getPrimeMoverSettings()->prepareSettings($response, 'clear_runtime_error_log_key',
            'prime_mover_clear_runtime_error_log_nonce', false, $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter());
        
        global $wp_filesystem;
        $status = false;
        $message = esc_html__('Clear log error fails.', 'prime-mover');
        
        $blog_id = 0;
        $value = '';
        $log_path = '';
        
        if (is_array($clearlog)) {
            $blog_id = key($clearlog);
        }
        
        if ($blog_id && isset($clearlog[$blog_id])) {
            $value = $clearlog[$blog_id];
        }
        
        $blog_id = (int)$blog_id;
        if ('confirmed' === $value && $blog_id) {
            $log_path = $this->getShutdownUtilities()->getAutoBackupRuntimeErrorLogPath($blog_id);            
        }
        
        if ($log_path) {
            $status = $wp_filesystem->put_contents($log_path, '', FS_CHMOD_FILE);
        }
        
        if ($status) {
            $message = esc_html__('Clear log success.', 'prime-mover');
        }
        
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, ['status' => $status, 'message' => $message]);
    }
 
    /**
     * Clear locked settings
     */
    public function clearLockedSettings()
    {
        $response = [];        
        $clearlocksettings = $this->getPrimeMoverSettings()->prepareSettings($response, 'clear_locked_settings',
            'prime_mover_clear_locked_settings_nonce', false, $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter());

        $status = false;
        $message = esc_html__('Clear locked settings fails.', 'prime-mover');
        
        $blog_id = 0;
        $value = '';
        
        if (is_array($clearlocksettings)) {
            $blog_id = key($clearlocksettings);
        }
        
        if ($blog_id && isset($clearlocksettings[$blog_id])) {
            $value = $clearlocksettings[$blog_id];
        }
        
        $blog_id = (int)$blog_id;
        if ('confirmed' === $value && $blog_id) {            
            $this->getComponentAuxiliary()->restoreAllFallBackSettings(0, 0, false, true); 
            $status = true;                       
        }
        
        if ($status) {
            $message = esc_html__('Clear locked settings success.', 'prime-mover');
        }
        
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, ['status' => $status, 'message' => $message]);
    }
    
    /**
     * Clear auto backup log
     */
    public function clearAutoBackupLog()
    {
        $response = [];        
        $clearlog = $this->getPrimeMoverSettings()->prepareSettings($response, 'clear_automatic_backup_log',
            'prime_mover_clear_automatic_backup_log_nonce', false, $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter());
        
        global $wp_filesystem;
        $status = false;
        $message = esc_html__('Clear log error fails.', 'prime-mover');
        
        $blog_id = 0;
        $value = '';
        
        if (is_array($clearlog)) {
            $blog_id = key($clearlog);
        }
        
        if ($blog_id && isset($clearlog[$blog_id])) {
            $value = $clearlog[$blog_id];
        }
        
        $blog_id = (int)$blog_id;        
        if ('confirmed' === $value && $blog_id) {
            $log_path = $this->getPrimeMover()->getSystemInitialization()->getTroubleShootingLogPath('automaticbackup', $blog_id);
            $status = $wp_filesystem->put_contents($log_path, '', FS_CHMOD_FILE);
        }
        
        if ($status) {
            $message = esc_html__('Clear log success.', 'prime-mover');
        }
        
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, ['status' => $status, 'message' => $message]);        
    }
    
    /**
     * Stream download automatic backup log
     */
    public function streamDownloadAutoBackupLog()
    {
        $this->getTroubleShootingMarkup()->streamDownloadHelper('prime_mover_automatic_backup_download_log_nonce', 'prime_mover_automatic_backup_download_log', 'automatic_backup_download_log', 'automaticbackup');
    }
    
    /**
     * Stream download autobackup runtime error log
     */
    public function streamDownloadRuntimeErrorLog()
    {
        $this->getTroubleShootingMarkup()->streamDownloadHelper('prime_mover_automatic_backup_runtime_error_nonce', 'prime_mover_automatic_runtime_error_log', 'automatic_backup_runtime_error_log', 'autobackup_error');
    }
    
    /**
     * Added WP Site Health debug info to site information
     * @param array $error_output
     * @return array
     */
    public function maybeAddWpDebugInfo($error_output = [])
    {
        if (!$this->getPrimeMover()->getSystemAuthorization()->isUserAuthorized()) {
            return $error_output;
        }        
        if (!is_array($error_output) || isset($error_output['wpsitehealth_info'])) {
            return $error_output;
        }      
        
        $debug_class = ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
        $site_health_class = ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
        
        if (!$this->getPrimeMover()->getSystemFunctions()->nonCachedFileExists($debug_class, true)) {
            return $error_output;
        }
        
        if (!$this->getPrimeMover()->getSystemFunctions()->nonCachedFileExists($site_health_class, true)) {
            return $error_output;
        }
        
        if (!class_exists('WP_Debug_Data')) {
            require_once $debug_class;
        }
        
        if (!class_exists('WP_Site_Health')) {
            require_once $site_health_class;
        }
        
        if (!method_exists('WP_Debug_Data', 'debug_data')) {
            return $error_output;
        }
        
        $error_output['wpsitehealth_info'] = WP_Debug_Data::debug_data();        
        return $error_output;
    }
    
    /**
     * Set maybe log property
     * @param boolean $log
     */
    public function setMaybeLog($log = false)
    {
        $this->maybe_log = $log;
    }
    
    /**
     * Get maybe log property value
     * @return boolean
     */
    public function getMaybeLog()
    {
        return $this->maybe_log;
    }
    
    /**
     * Maybe log set 
     */
    public function maybeLog()
    {        
        $setting = $this->getPrimeMoverSettings()->getSetting(self::TROUBLESHOOTING_KEY);
        if (!$setting ) {
            $this->setMaybeLog(false);
            return;
        }
        if ('true' === $setting) {
            $this->setMaybeLog(false);
            return;
        }
        
        $this->setMaybeLog(true);
    }
    
    
    /**
     * Register setting
     * @param array $settings
     * @return boolean[]
     */
    public function registerSetting($settings = [])
    {
        $troubleshooting_keys = [self::TROUBLESHOOTING_KEY, self::PERSIST_TROUBLESHOOTING_KEY, self::ENABLE_UPLOAD_JS_LOG, self::ENABLE_JS_LOG];
        foreach ($troubleshooting_keys as $troubleshooting_key) {
            if (!in_array($troubleshooting_key, $settings)) {
                $settings[$troubleshooting_key] = ['encrypted' => false];                
            }
        }
        return $settings;
    }   
    
    /**
     * Add settings to js object
     * @param array $js_object
     * @return array
     */
    public function addSettingsToJs($js_object = []) {
        
        $js_object['enable_troubleshooting'] = self::TROUBLESHOOTING_KEY;
        $js_object['enable_turbo_mode'] = self::ENABLE_TURBO_MODE;
        $js_object['enable_persist_troubleshooting'] = self::PERSIST_TROUBLESHOOTING_KEY;
        $js_object['enable_uploadjs_troubleshooting'] = self::ENABLE_UPLOAD_JS_LOG;
        $js_object['enable_js_troubleshooting'] = self::ENABLE_JS_LOG;
        
        return $js_object;
    }
    
    /**
     * Check if we need to persist troubleshooting log to several sites
     * @param boolean $ret
     * @return boolean
     */
    public function maybePersistTroubleShootingLog($ret = false)
    { 
        $setting = $this->getPrimeMoverSettings()->getSetting(self::PERSIST_TROUBLESHOOTING_KEY);
        if ( ! $setting ) {
            return false;
        }
        if ('true' === $this->getPrimeMoverSettings()->getSetting(self::PERSIST_TROUBLESHOOTING_KEY)) {
            return true;
        }
        return false;        
    }
    
    /**
     * Maybe disable troubleshooting log
     * Returning TRUE disables the log
     * Returning FALSE enables the log
     * @param boolean $ret
     * @return boolean
     */
    public function maybeDisableTroubleShootingLog($ret = false)
    { 
        return $this->getMaybeLog();
    }
    
    /**
     * Refresh site info log
     * @param string $log_type
     */
    public function refreshSiteInfoLog($log_type = '')
    {
        if ('siteinformation' !== $log_type) {
            return;
        }
        $this->getTroubleShootingMarkup()->refreshSiteInfoLog();
    }
    
    /**
     * Export site information
     */
    public function exportSiteInformation()
    {          
        $this->getTroubleShootingMarkup()->streamDownloadHelper('prime_mover_download_site_info_nonce', 'prime_mover_download_siteinfo', 'prime_mover_site_info', 'siteinformation');
    }
 
    /**
     * Show site info button
     */
    public function showSiteInfoButton()
    {
        $this->getTroubleShootingMarkup()->showSiteInfoButton();
    }
    
    /**
     * Maybe enable js upload error analysis
     * @param boolean $enable
     * @return boolean
     */
    public function maybeEnableJsUploadErrorAnalysis($enable = false)
    { 
        $setting = $this->getPrimeMoverSettings()->getSetting(self::ENABLE_UPLOAD_JS_LOG);
        if ( ! $setting ) {
            return false;
        }
        if ('true' === $this->getPrimeMoverSettings()->getSetting(self::ENABLE_UPLOAD_JS_LOG)) {
            return true;
        }
        return false;
    }

    /**
     * Maybe enable turbo mode setting
     * @param boolean $enable
     * @return boolean
     */
    public function maybeEnableTurboModeSetting($enable = false)
    {
        $default = $this->getConfigUtilities()->getTurboModeDefaultConfig();
        $setting = $this->getPrimeMoverSettings()->getSetting(self::ENABLE_TURBO_MODE);
        if (!$setting ) {
            return $default;
        }
        
        if ('true' === $this->getPrimeMoverSettings()->getSetting(self::ENABLE_TURBO_MODE)) {
            return true;            
        } elseif ('false' === $this->getPrimeMoverSettings()->getSetting(self::ENABLE_TURBO_MODE)) {
            return false;
        } else {
            return $default;
        }
    }
    
    /**
     * Save enable upload js troubleshooting
     */
    public function saveEnableUploadJsTroubleShootingSetting()
    {
        $success = [ 'true' => esc_html__('Upload chunk debug enabled.', 'prime-mover'), 'false' => esc_html__('Upload chunk debug disabled.', 'prime-mover') ];
        $error = esc_html__('Upload chunk debug update failed', 'prime-mover');
        
        $this->getPrimeMoverSettings()->saveHelper(self::ENABLE_UPLOAD_JS_LOG,  'prime_mover_save_uploadjs_console_setting_nonce', false,
            $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(), self::ENABLE_UPLOAD_JS_LOG, false, $success, $error, 'checkbox', 'settings_checkbox_validation');
    }
    
    /**
     * Save turbo mode setting
     */
    public function saveEnableTurboModeSetting()
    {
        $success = [ 'true' => esc_html__('Turbo mode enabled.', 'prime-mover'), 'false' => esc_html__('Turbo mode disabled.', 'prime-mover') ];
        $error = esc_html__('Turbo mode update failed', 'prime-mover');
        
        $this->getPrimeMoverSettings()->saveHelper(self::ENABLE_TURBO_MODE,  'prime_mover_save_turbomode_setting_nonce', false,
            $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(), self::ENABLE_TURBO_MODE, false, $success, $error, 'checkbox', 'settings_checkbox_validation');
    }
    
    /**
     * Maybe enable js error analysis
     * @param boolean $enable
     * @return boolean
     */
    public function maybeEnableJsErrorAnalysis($enable = false)
    {
        $setting = $this->getPrimeMoverSettings()->getSetting(self::ENABLE_JS_LOG);
        if ( ! $setting ) {
            return false;
        }
        if ('true' === $this->getPrimeMoverSettings()->getSetting(self::ENABLE_JS_LOG)) {
            return true;
        }
        return false;
    }

    /**
     * Save enable js troubleshooting setting
     */
    public function saveEnableJsTroubleShootingSetting()
    {
        $success = [ 'true' => esc_html__('JavaScript troubleshooting enabled.', 'prime-mover'), 'false' => esc_html__('JavaScript troubleshooting disabled.', 'prime-mover') ];
        $error = esc_html__('JavaScript troubleshooting update failed', 'prime-mover');
        
        $this->getPrimeMoverSettings()->saveHelper(self::ENABLE_JS_LOG,  'prime_mover_save_js_console_setting_nonce', false,
            $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(), self::ENABLE_JS_LOG, false, $success, $error, 'checkbox', 'settings_checkbox_validation');
    }
    
    /**
     * Save disable user diff setting
     */
    public function saveDisableUserDiffSetting()
    {        
        $success = [ 'true' => esc_html__('User diff check disabled.', 'prime-mover'), 'false' => esc_html__('User diff check enabled.', 'prime-mover') ];
        $error = esc_html__('User diff setting update failed', 'prime-mover');
        
        $this->getPrimeMoverSettings()->saveHelper(self::DISABLE_USER_DIFF_CHECK,  'prime_mover_save_disable_user_diff_settings_nonce', false,
            $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(), self::DISABLE_USER_DIFF_CHECK, false, $success, $error, 'checkbox', 'settings_checkbox_validation');
    }
    
    /**
     * Save enable persist troubleshooting setting
     */
    public function saveEnablePersistTroubleShootingSetting()
    {
        $success = [ 'true' => esc_html__('Persist troubleshooting enabled.', 'prime-mover'), 'false' => esc_html__('Persist troubleshooting disabled.', 'prime-mover') ];
        $error = esc_html__('Persist troubleshooting update failed', 'prime-mover');
        
        $this->getPrimeMoverSettings()->saveHelper(self::PERSIST_TROUBLESHOOTING_KEY, 'prime_mover_save_persist_troubleshooting_settings_nonce', false,
            $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(), self::PERSIST_TROUBLESHOOTING_KEY, false, $success, $error, 'checkbox', 'settings_checkbox_validation' );        
    }
    
    /**
     * Clear troubleshooting log
     */
    public function clearTroublesShootingLog()
    {
        $response = [];
        $clearlog = $this->getPrimeMoverSettings()->prepareSettings($response, 'clear_confirmation', 
            'prime_mover_clear_troubleshooting_settings_nonce', false, $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter());

        global $wp_filesystem;
        $status = false;
        $message = esc_html__('Clear log error fails.', 'prime-mover');
        
        if ('clearlog' === $clearlog) {
            $log_path = $this->getPrimeMover()->getSystemInitialization()->getTroubleShootingLogPath('migration');
            $status = $wp_filesystem->put_contents($log_path, '', FS_CHMOD_FILE);
        }
        if ($status) {
            $message = esc_html__('Clear log success.', 'prime-mover');
        }
        $this->getPrimeMoverSettings()->returnToAjaxResponse($response, ['status' => $status, 'message' => $message]);
    }
    
    /**
     * Stream download troubleshooting log
     */
    public function streamDownloadTroubleShootingLog()
    {        
        $this->getTroubleShootingMarkup()->streamDownloadHelper('download_troubleshooting_log_nonce', 'prime_mover_download_troubleshooting_log', 'prime_mover_troubleshooting_log', 'migration');
    }
       
    /**
     * Save enable troubleshooting setting
     */
    public function saveEnableTroubleShootingSetting()
    {
        $success = [ 'true' => esc_html__('Troubleshooting enabled.', 'prime-mover'), 'false' => esc_html__('Troubleshooting disabled.', 'prime-mover') ];
        $error = esc_html__('Troubleshooting update failed', 'prime-mover');
        
        $this->getPrimeMoverSettings()->saveHelper(self::TROUBLESHOOTING_KEY, 'prime_mover_save_troubleshooting_settings_nonce', false,
            $this->getPrimeMover()->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(), self::TROUBLESHOOTING_KEY, false, $success, $error, 'checkbox', 'settings_checkbox_validation' ); 
    }

    /**
     * Show troubleshooting setting
     */
    public function showTroubleShootingSetting()
    {
    ?>
       <h2><?php esc_html_e('Debugging Tools', 'prime-mover') ?></h2>
    <?php     
       $this->outputMarkup();       
    }
    
    /**
     * Render enable troubleshooting log markup
     */
    public function renderEnableTroubleShootingLogMarkup()
    {
        $this->getTroubleShootingMarkup()->renderEnableTroubleShootingLogMarkup(self::TROUBLESHOOTING_KEY);    
    }
 
    /**
     * Render clear log markup
     */
    public function renderClearLogCallBack()
    {
        $this->getTroubleShootingMarkup()->renderClearLogCallBack();    
    }
 
    /**
     * Render clear lock settings markup
     */
    public function renderClearLockedSettingsCallBack()
    {
        if (is_multisite()) {
            return;    
        }
        
        $this->getTroubleShootingMarkup()->renderClearLockedSettingsCallBack();
    }
 
    /**
     * Render disable user diff setting
     */
    public function renderDisableUserDiffMarkup()
    {
        $blog_id = 0;
        if (is_multisite()) {
            $blog_id = get_current_blog_id();
        }
        
        if ($blog_id && false === $this->getPrimeMover()->getSystemFunctions()->isFreshMultisiteMainSite($blog_id)) {   
            return;
        }
        
        $this->getTroubleShootingMarkup()->renderDisableUserDiffMarkup();
    }
    
    /**
     * Render download log markup
     */
    public function renderDownloadLogMarkup()
    {
        $this->getTroubleShootingMarkup()->renderDownloadLogMarkup();     
    }
    
    /**
     * Render Persist troubleshooting markup
     */
    public function renderPersistTroubleShootingMarkup()
    {
        $this->getTroubleShootingMarkup()->renderPersistTroubleShootingMarkup(self::PERSIST_TROUBLESHOOTING_KEY);     
    }
    
    /**
     * Render js troubleshooting markup
     */
    public function renderJsTroubleShootingLogMarkup()
    {
        $this->getTroubleShootingMarkup()->renderJsTroubleShootingLogMarkup(self::ENABLE_JS_LOG);   
    }
    
    /**
     * Render js upload log markup
     */
    public function renderUploadJsTroubleShootingLogMarkup()
    {
        $this->getTroubleShootingMarkup()->renderUploadJsTroubleShootingLogMarkup(self::ENABLE_UPLOAD_JS_LOG);      
    }
   
    /**
     * Render turbo mode markup
     */
    public function renderTurboModeMarkup()
    {
        $this->getTroubleShootingMarkup()->renderTurboModeMarkup(self::ENABLE_TURBO_MODE);
    }
    
    /**
     * Output markup
     */
    private function outputMarkup()
    {
        do_action('prime_mover_render_troubleshooting_markup');  
        $this->getTroubleShootingMarkup()->renderClearLogMarkup();
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