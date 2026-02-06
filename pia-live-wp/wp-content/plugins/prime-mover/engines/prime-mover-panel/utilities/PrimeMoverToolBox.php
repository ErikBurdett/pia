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

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover ToolBox Class
 *
 */
class PrimeMoverToolBox
{    
    private $autobackup_setting;
    private $autobackup_events;
    
    /**
     * Constructor
     * @param PrimeMoverAutoBackupSetting $autobackup_setting
     */
    public function __construct(PrimeMoverAutoBackupSetting $autobackup_setting) 
    {
        $this->autobackup_setting = $autobackup_setting;
        $this->autobackup_events = ['primeMoverAutomaticBackupEvent', 'primeMoverProgressIntervalEvent'];
    }
    
    /**
     * Get autobackup events
     */
    public function getAutoBackupEvents()
    {
        return $this->autobackup_events;
    }
    
    /**
     * Get Progress handlers
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverProgressHandlers
     */
    public function getProgressHandlers()
    {
        return $this->getPrimeMover()->getHookedMethods()->getProgressHandlers();
    }
    
    /**
     * Get Prime Mover settings template
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsTemplate
     */
    public function getPrimeMoverSettingsTemplate()
    {
        return $this->getAutoBackupSetting()->getPrimeMoverSettingsTemplate();
    }
    
    /**
     * Get Utilities
     * @return array
     */
    public function getUtilities()
    {
        return $this->getPrimeMoverSettingsTemplate()->getUtilities();
    }
    
    /**
     * Get backup utilities
     */
    public function getBackupUtilities()
    {
        $utilities = $this->getUtilities();
        return $utilities['backup_utilities'];
    }
    
    /**
     * Get autobackup setting
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverAutoBackupSetting
     */
    public function getAutoBackupSetting()
    {
        return $this->autobackup_setting;
    }
    
    /**
     * Get Prime Mover
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->getAutoBackupSetting()->getPrimeMover();
    }
    
    /**
     * Get system authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()
    {
        return $this->getPrimeMover()->getSystemAuthorization();
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
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getPrimeMover()->getSystemFunctions();
    }
    
    /**
     * Initialize init hooks
     */
    public function initHooks()
    {
        add_action('admin_init', [$this, 'initializeAutoBackupRuntimeErrorLog'], 17);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputAutoBackupLog'], 50, 1); 
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputRuntimeErrorLog'], 55, 1);          
        
        add_action('prime_mover_after_error_is_logged', [$this, 'cloneRunTimeErrorLogToAutoBackup'], 1000, 1);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputClearAutoBackupLog'], 60, 1); 
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputClearRuntimeErrorLog'], 65, 1);  
        
        add_action('prime_mover_before_db_processing', [$this, 'maybeBackupAutoBackupEventsCron'], 10, 2);
        add_action('prime_mover_after_db_processing', [$this, 'maybeRemoveEventsCron'], 10, 2);
        add_action('prime_mover_after_db_processing', [$this, 'maybeRestoreAutoBackupEventsCron'], 100, 2);
        
        add_filter('prime_mover_excluded_meta_keys', [$this, 'excludeAutoBackupRetryInitMetas'], 100, 3);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputLogToolsHeading'], 49); 
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputClearAutoBackupInit'], 70, 1);
        
        add_filter('prime_mover_filter_error_output', [$this, 'appendCustomScheduleToLog'], 51, 1);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputSiteUtilitiesHeading'], 71, 1);
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputNonUserIdAdjustment'], 75, 1);
        
        add_action('prime_mover_scheduled_backup_settings_per_site', [$this, 'outputExcludeTables'], 80, 1);
    }

    /**
     * Output exclude tables setting
     * @param number $blog_id
     */
    public function outputExcludeTables($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = 'excluded-tables';
        
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
        $heading_text = esc_html__('Excluded tables', 'prime-mover');
        
        $first_paragraph = '';
        $first_paragraph .= '<div class="prime-mover-setting-description">';
        $first_paragraph .= '<p class="description prime-mover-settings-paragraph">';
        
        $first_paragraph .= esc_html__('All database tables belonging to the exported site will be included in the export package by default.', 'prime-mover');
        $first_paragraph .= '</p>';
        $first_paragraph .= '<p class="description">';
        
        $first_paragraph .= esc_html__('It is possible to exclude some custom database tables from being exported by clicking the button below to expand and check them. You can only exclude custom database tables created by other plugins or themes.',
            'prime-mover');
        
        $first_paragraph .= '</p>';
        $toggle_btn_title = esc_attr(esc_html__('Click this button to expand the database tables of this site.', 'prime-mover'));
        $validated_array = $this->buildTablesArray($blog_id);
        $empty_text = esc_html__('No tables found', 'prime-mover');
        
        $second_paragraph = '';
        $second_paragraph .= '<p class="description prime-mover-settings-paragraph">';
        $second_paragraph .= esc_html__('Use this tool to reduce the size of your database export and speed up the database import at the restore end. This is useful if the site no longer uses these plugins but still has large database tables.',
            'prime-mover');
        
        $second_paragraph .= '</p>';
        $second_paragraph .= '<p class="description">';
        $second_paragraph .= esc_html__('Take note you cannot exclude WordPress core tables because they are required for your site to work. Please note that if you exclude tables still actively used by your plugins or themes, you will have missing functionality after import, or your site will have errors.',
            'prime-mover');
        
        $second_paragraph .= '</p>';
        
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxesTextAreaDisplayTemplate($heading_text, $identifier, $config, true, $first_paragraph, $toggle_btn_title,
            $validated_array, $empty_text, $second_paragraph, $button_specs, $blog_id, false); 
    }
  
    /**
     * Build database tables array for markup
     * @param number $blog_id
     * @return array
     */
    protected function buildTablesArray($blog_id = 0)
    {
        $tables = apply_filters('prime_mover_tables_to_export', $this->getSystemFunctions()->getTablesToExport($blog_id), $blog_id, [], true);
        $core = $this->getSystemInitialization()->getCoreWpTables($blog_id);
        
        $validated = [];
        foreach ($tables as $table) {
            if (!in_array($table, $core)) {
                $validated[] = $table;
            }
        }
        
        return $validated;
    }
    
    /**
     * Output non-user ID adjustment setting
     * @param number $blog_id
     */
    public function outputNonUserIdAdjustment($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = 'non_user_id_adjustment';
        
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
        $heading_text = __('Non-user_id column auto-adjustment', 'prime-mover');        
        $setting = $this->getPrimeMoverSettingsTemplate()->getPrimeMoverSettings()->getSetting('non_user_column_id_adjustment', false, '', false, $blog_id, true);
        $setting = $this->convertNonUserIdToTextAreaDataFormat($setting);
        $placeholder = "wp_custom_table_example : custom_user_column_name";
        
        $description = sprintf(esc_html__('Only the %s database table column is auto-adjusted by default during export and import. If you have a non-user_id column, please define it here to be auto-adjusted on restore. 
Please enter one row per database table using the format %s. Please %s to learn more about this feature.', 'prime-mover'), 
            '<code>user_id</code>', "<code>{$placeholder}</code>",
            '<a target="_blank" class="prime-mover-external-link" href="' . CODEXONICS_NON_USER_ID_ADJUSTMENT_TUTORIAL . '">' . esc_html__('please check out this tutorial', 'prime-mover') . '</a>'                
        );
        $this->getPrimeMoverSettingsTemplate()->renderTextAreaFormTemplate($heading_text, $identifier, $config, $setting, $placeholder, $description, $button_specs, false, $blog_id);
    }
    
    /**
     * Convert non user ID settings to text area data format
     * @param array $setting
     * @return string
     */
    protected function convertNonUserIdToTextAreaDataFormat($setting = [])
    {
        $ret = '';
        if (!is_array($setting)) {
            return $ret;
        }
        
        foreach($setting as $table_name => $column_array) {
            $col_string = '';
            if (is_array($column_array)) {
                $col_string = implode(",", $column_array);
            }
            
            if ($col_string) {
                $ret .= $table_name . ':' . $col_string . PHP_EOL; 
            }                       
        }
        
        return $ret;

    }
    /**
     * Add headings
     */
    public function outputSiteUtilitiesHeading()
    {
        ?>
        <hr/>
            <h2 class="prime_mover_toolbox_headings">*** <?php echo esc_html__('Export-Import Utilities', 'prime-mover'); ?> ***</h2>
        <hr/> 
    <?php 
    }
    
    /**
     * Get cron from options table
     * @return mixed|boolean|NULL|array
     */
    protected function getCron()
    {
        return $this->getSystemFunctions()->getOption('cron', false, '', true, true);
    }
    
    /**
     * Append custom schedule to log
     * @param array $error_output
     * @return array
     */
    public function appendCustomScheduleToLog($error_output = [])
    {
        if (!is_array($error_output) ) {
            return $error_output;
        }
        
        $error_output['prime_mover_autobackup_custom_schedules'] = $this->getAutoBackupSetting()->parseCustomScheduleFromConfig();
        return $error_output;
    }    

    /**
     * Add headings
     */
    public function outputLogToolsHeading()
    {
    ?>   
        <hr/>
            <h2 class="prime_mover_toolbox_headings">*** <?php echo esc_html__('Scheduled backup tools', 'prime-mover'); ?> ***</h2>
        <hr/> 
    <?php 
    }
    
    /**
     * Exclude auto backup retry init user metas
     * @param array $excluded
     * @param number $user_id
     * @param string $meta_key
     * @return mixed
     */
    public function excludeAutoBackupRetryInitMetas($excluded = [], $user_id = 0, $meta_key = '')
    {
        if (!$meta_key || !is_string($meta_key)) {
            return $excluded;
        }
        
        if (!is_array($excluded)) {
            return $excluded;
        }
        
        $match = false;
        if (false !== strpos($meta_key, 'auto_backup_prime_mover_')) {
            $match = true;
        } 
        
        if ($match && !in_array($meta_key, $excluded)) {
            $excluded[] = $meta_key;
        }
        
        return $excluded;
    }
    
    /**
     * Maybe restore autobackup events cron
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function maybeRestoreAutoBackupEventsCron($ret = [], $blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blogid_to_import) {
            return;
        }
        
        if (!is_multisite() || !$this->getSystemFunctions()->isMultisiteMainSite($blogid_to_import)) {
            return;
        }
        
        $process_id = $this->getProgressHandlers()->generateTrackerId($blogid_to_import, 'import');
        $option = '_primemoverautobackup_cron_' . $process_id;
        
        $opt_value = $this->getSystemFunctions()->getSiteOption($option, false, true, true, '', true, true);
        if (!is_array($opt_value)) {
            return;
        }
        
        $this->getSystemFunctions()->switchToBlog($blogid_to_import);
        $cron = $this->getCron();
        if (!is_array($cron)) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return;
        }
        
        foreach ($opt_value as $k => $v) {
            $cron[$k] = $v;
        }
        
        $this->getSystemFunctions()->updateOption('cron', $cron);
        $this->getSystemFunctions()->restoreCurrentBlog();
        $this->getSystemFunctions()->deleteSiteOption($option, false, '', true, true);
    }
    
    /**
     * Maybe remove events cron
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function maybeRemoveEventsCron($ret = [], $blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blogid_to_import) {
            return;
        }
        
        $this->getSystemFunctions()->switchToBlog($blogid_to_import);
        $cron = $this->getCron();
        if (!is_array($cron)) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return;
        }
        
        $cron = $this->loopOverEvents($cron, true);
        $this->getSystemFunctions()->updateOption('cron', $cron);
        
        $this->getSystemFunctions()->restoreCurrentBlog();
    }
    
    /**
     * Maybe backup auto backup crons to network in case of main site restore
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function maybeBackupAutoBackupEventsCron($ret = [], $blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blogid_to_import) {
            return;
        }
        
        if (!is_multisite() || !$this->getSystemFunctions()->isMultisiteMainSite($blogid_to_import)) {
            return;
        }
        
        $process_id = $this->getProgressHandlers()->generateTrackerId($blogid_to_import, 'import');
        $option = '_primemoverautobackup_cron_' . $process_id;
        
        $opt_value = $this->getSystemFunctions()->getSiteOption($option, false, true, true, '', true, true);
        if (is_array($opt_value)) {
            return;
        }
        
        $this->getSystemFunctions()->switchToBlog($blogid_to_import);
        $cron = $this->getCron();
        if (!is_array($cron)) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return;
        }
       
        $events = $this->loopOverEvents($cron, false);         
        $this->getSystemFunctions()->restoreCurrentBlog();
        $this->getSystemFunctions()->updateSiteOption($option, $events, true, '', true, true); 
    }

    /**
     * Loop over events
     * @param array $cron
     * @param boolean $unset_mode
     * @return array
     */
    protected function loopOverEvents($cron = [], $unset_mode = false)
    {
        $events = [];
        foreach ($cron as $unix_time_key => $v) {
            if (!is_array($v)) {
                continue;
            }
            
            foreach ($v as $action => $v1) {
                $actions = $this->getBackupUtilities()->getDetailsromCronAction($action);
                if (!is_array($actions)) {
                    continue;
                }
                
                if (!isset($actions[0])) {
                    continue;
                }
                
                $hook = $actions[0];
                if (!in_array($hook, $this->getAutoBackupEvents())) {
                    continue;
                }
                
                if ($unset_mode) {
                    unset($cron[$unix_time_key][$action]);                    
                } else {                    
                    $events[$unix_time_key][$action] = $v1;
                }                
            }
        }
        
        if ($unset_mode) {
            $cron = array_filter($cron);
            return $cron;
        } else {
            return $events;
        }
    }
    
    /**
     * Clone standard runtime error log to autobackup logs
     * @param string $error_log_file
     */
    public function cloneRunTimeErrorLogToAutoBackup($error_log_file = '')
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$this->getSystemAuthorization()->isDoingAutoBackup() || 
            !$this->getSystemFunctions()->nonCachedFileExists($error_log_file)) {
            return;
        } 
        
        $blog_id = $this->getAutoBackupSetting()->getShutDownUtilities()->primeMoverGetProcessedID();
        if (!$blog_id) {
            return;
        }
        
        $errorfilename = $this->getSystemInitialization()->generateTroubleShootingLogFileName('autobackup_error', $blog_id);
        if (!$errorfilename) {
            return;
        }
        
        $log_file = $this->getAutoBackupSetting()->getShutdownUtilities()->getPrimeMoverErrorPath($blog_id, $errorfilename);
        if (!$this->getSystemFunctions()->nonCachedFileExists($log_file) ) {
            return;
        }       
        
        $this->getSystemFunctions()->streamCopyToStream($error_log_file, $log_file, 'rb', 'wb', false);
    }
    
    /**
     * Initialize auto backup error log
     */
    public function initializeAutoBackupRuntimeErrorLog()
    {
        $blog_id = $this->getSystemInitialization()->getBlogIdOnScheduledBackupPage();
        if (!$blog_id) {
            return;
        }
        
        if (!$this->getSystemFunctions()->blogIsUsable($blog_id)) {
            return;
        }
        
        $errorfilename = $this->getSystemInitialization()->generateTroubleShootingLogFileName('autobackup_error', $blog_id);
        if (!$errorfilename) {
            return;
        }
        
        $log_file = $this->getAutoBackupSetting()->getShutdownUtilities()->getPrimeMoverErrorPath($blog_id, $errorfilename);
        $this->getSystemInitialization()->initializeAutoBackupRuntimeErrorLog($log_file);
    }
    
    /**
     * Output autobackup log for diagnosing issues
     * @param number $blog_id
     */
    public function outputAutoBackupLog($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupSetting()->getIdentifierAutoBackupLog();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $config = $settings_api[$identifier];
        $heading_text = __('Download auto backup log', 'prime-mover');
        $description = esc_html__('Click this button if you like to download the automatic backup log for this site. Please share this log privately with technical support if requested.', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderButtonFormTemplate($heading_text, $config, $description, $blog_id);
    }
   
    /**
     * Output autobackup runtime error log for diagnosing issues
     * @param number $blog_id
     */
    public function outputRuntimeErrorLog($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupSetting()->getAutoBackupRuntimeErrorLogIdentifier();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $config = $settings_api[$identifier];
        $heading_text = __('Download error log', 'prime-mover');
        $description = esc_html__('Click this button if you like to download the automatic backup runtime error log for this site. This error log is only for automatic backup functionality. Please share this log privately with technical support if requested.', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderButtonFormTemplate($heading_text, $config, $description, $blog_id);
    }
    
    /**
     * Output clear autobackup log button
     * @param number $blog_id
     */
    public function outputClearAutoBackupLog($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupSetting()->getIdentifierClearAutoBackupLog();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'p',
            'button_classes' => 'button-secondary prime-mover-deleteall-button',
            'button_text' => __('Clear auto backup log', 'prime-mover'),
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $heading_text = __('Clear auto backup log', 'prime-mover');
        $description = esc_html__('Click this button if you like to clear the auto backup log of this site.', 'prime-mover');
        if (is_multisite()) {
            $dialog_message = sprintf(esc_html__('Are you sure you want to clear the auto-backup log of blog ID %d?', 'prime-mover'), $blog_id);
        } else {
            $dialog_message = esc_html__('Are you sure you want to clear the auto-backup log?', 'prime-mover');
        }
        
        $dialog_heading = __('Heads Up!', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderButtonFormConfirmTemplate($heading_text, $identifier, $config, $description, $blog_id, false, $button_specs, $dialog_message, $dialog_heading);
    }
    
    /**
     * Output clear autobackup log button
     * @param number $blog_id
     */
    public function outputClearRuntimeErrorLog($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupSetting()->getIdentifierClearErrorLog();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'p',
            'button_classes' => 'button-secondary prime-mover-deleteall-button',
            'button_text' => __('Clear error log', 'prime-mover'),
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $heading_text = __('Clear runtime error log', 'prime-mover');
        $description = esc_html__('Click this button if you like to clear the auto backup error log of this site.', 'prime-mover');
        if (is_multisite()) {
            $dialog_message = sprintf(esc_html__('Are you sure you want to clear the error log of blog ID %d?', 'prime-mover'), $blog_id);
        } else {
            $dialog_message = esc_html__('Are you sure you want to clear the error log?', 'prime-mover');
        }
        
        $dialog_heading = __('Heads Up!', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderButtonFormConfirmTemplate($heading_text, $identifier, $config, $description, $blog_id, false, $button_specs, $dialog_message, $dialog_heading);
    }
    
    /**
     * Output clear autobackup initialization button
     * @param number $blog_id
     */
    public function outputClearAutoBackupInit($blog_id = 0)
    {
        $settings_api = $this->getAutoBackupSetting()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = $this->getAutoBackupSetting()->getIdentifierClearAutoBackupInit();
        if (!isset($settings_api[$identifier])) {
            return;
        }
        
        $button_specs = [
            'button_wrapper' => 'p',
            'button_classes' => 'button-secondary prime-mover-deleteall-button',
            'button_text' => __('Clear autobackup init key', 'prime-mover'),
            'disabled' => '',
            'title' => ''
        ];
        
        $config = $settings_api[$identifier];
        $heading_text = __('Clear autobackup init key', 'prime-mover');
        $description = esc_html__('Use this button to clear the auto backup init key of this site. Only use this if instructed by the tech support team or from a troubleshooting guide.', 'prime-mover');
        if (is_multisite()) {
            $dialog_message = sprintf(esc_html__('Are you sure you want to clear the auto backup init key of blog ID %d?', 'prime-mover'), $blog_id);
        } else {
            $dialog_message = esc_html__('Are you sure you want to clear the auto backup init key?', 'prime-mover');
        }
        
        $dialog_heading = __('Heads Up!', 'prime-mover');
        
        $this->getPrimeMoverSettingsTemplate()->renderButtonFormConfirmTemplate($heading_text, $identifier, $config, $description, $blog_id, false, $button_specs, $dialog_message, $dialog_heading);
    }
}