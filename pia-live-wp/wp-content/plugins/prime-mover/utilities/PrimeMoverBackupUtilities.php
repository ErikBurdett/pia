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

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover Backups Utilities
 * Helper functionality for backup related tasks
 *
 */
class PrimeMoverBackupUtilities
{       
    private $prime_mover;
    private $is_refreshing_backup;
    private $prime_mover_in_progress_package_option;
    
    /**
     * Constructor
     * @param PrimeMover $prime_mover
     */
    public function __construct(PrimeMover $prime_mover)
    {
        $this->prime_mover = $prime_mover;
        $this->is_refreshing_backup = false;
        $this->prime_mover_in_progress_package_option = 'prime_mover_in_progress_packages';
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
     * Maybe remove WIP status too on error when package is deleted automatically
     * @param string $path_to_delete
     * @param number $blog_id
     * @param array $in_progress
     * @param boolean $update
     * @param boolean $force_update
     * @return array
     */
    public function maybeRemoveWipStatusOnError($path_to_delete = '', $blog_id = 0, $in_progress = [], $update = true, $force_update = false)
    {
        if (!$path_to_delete || !$blog_id) {
            return;
        }
        return $this->updateInProgressPackagesDb($path_to_delete, $blog_id, $in_progress, $update, $force_update); 
    }
    
    /**
     * Maybe mark package completed - remove WIP status
     * @param string $results
     * @param string $hash
     * @param number $blogid_to_export
     * @param array $in_progress
     * @param boolean $update
     * @param boolean $force_update
     * @return array
     */
    public function maybeMarkPackageCompleted($results = '', $hash = '', $blogid_to_export = 0, $in_progress = [], $update = true, $force_update = false)
    {        
        if (!$results || !$blogid_to_export) {
            return;
        }
        
        return $this->updateInProgressPackagesDb($results, $blogid_to_export, $in_progress, $update, $force_update);        
    }
    
    /**
     * Update in-progress packages in dB
     * @param string $results
     * @param number $blogid_to_export
     * @param array $in_progress
     * @param boolean $update
     * @param boolean $force_update
     * @return array | boolean
     */
    protected function updateInProgressPackagesDb($results = '', $blogid_to_export = 0, $in_progress = [], $update = true, $force_update = false)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $in_progress;
        }
        
        if (!$results || !$blogid_to_export) {
            return $in_progress;
        }

        $package = basename($results);
        if (empty($in_progress)) {
            $in_progress = $this->getInProgressPackages();
        }
        
        $option_name = $this->getInProgressPackageOption();        
        if ($force_update) {
            $this->getSystemFunctions()->updateSiteOption($option_name, $in_progress);
           
            return $in_progress;
        }
        
        $hash = sha1($package);
        $update_required = false;
        
        if ($this->packageIsInProgress($in_progress, $package, $blogid_to_export)) {
            unset($in_progress[$blogid_to_export][$hash]); 
            $update_required = true;
        }
        
        if ($update_required && $update) {
            $this->getSystemFunctions()->updateSiteOption($option_name, $in_progress);
        }
        
        return $in_progress;
    }
    
    /**
     * Get in-progress packages
     * @return mixed|boolean|NULL|array
     */
    public function getInProgressPackages()
    {
        return $this->getSystemFunctions()->getSiteOption($this->getInProgressPackageOption());
    }
    
    /**
     * Get package in-progress
     * @param array $in_progress_packages
     * @param string $package_filename
     * @param number $blog_id
     * @param boolean $return_boolean
     * @return boolean|array
     */
    public function packageIsInProgress($in_progress_packages = [], $package_filename = '', $blog_id = 0, $return_boolean = true)
    {
        if (!is_array($in_progress_packages) || !$package_filename || !$blog_id) {
            return false;
        }
        
        $return = false;
        $data = [];
        
        $package_filename = basename($package_filename);
        $hash = sha1($package_filename); 
        
        if (isset($in_progress_packages[$blog_id][$hash])) {
            $return = true;
            $data = $in_progress_packages[$blog_id][$hash];
        } 
        
        if ($return_boolean) {
            return $return;
        } else {
            return $data;
        }
    }
    
    /**
     * Logged in-progress packages
     * @param array $ret
     * @param number $blogid_to_export
     * @return array
     */
    public function logInProgressPackage($ret = [], $blogid_to_export = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blogid_to_export) {
            return $ret;
        }
       
        if (!isset($ret['target_zip_path'])) {
            return $ret;
        }
       
        $wprime = basename($ret['target_zip_path']);
        if (!$this->getSystemFunctions()->hasTarExtension($wprime)) {
            return $ret;
        }
       
        $option_name = $this->getInProgressPackageOption();
        $existing_packages = $this->getSystemFunctions()->getSiteOption($option_name);
        if (!is_array($existing_packages)) {
            $existing_packages = [];
        }       
        
        $hash = sha1($wprime);
        $mode = 'manual';
        if ($this->getSystemAuthorization()->isDoingAutoBackup()) {
            $mode = 'automatic';
        }        
        
        if (!isset($existing_packages[$blogid_to_export][$hash])) {
            $existing_packages[$blogid_to_export][$hash] = [$wprime => $mode];
        }
       
        $this->getSystemFunctions()->updateSiteOption($option_name, $existing_packages);         
        return $ret;
    }
    
    /**
     * Get in-progress package option
     * @return string
     */
    public function getInProgressPackageOption()
    {
        return $this->prime_mover_in_progress_package_option;
    }    
    
    /**
     * Is refreshing backup
     * @param boolean $is_refreshing_backup
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itChecksIfRefreshingbackup() 
     */
    public function setIsRefreshingBackup($is_refreshing_backup = false)
    {
        $this->is_refreshing_backup = $is_refreshing_backup;
    }
    
    /**
     * Checks refresh backup status
     * @return boolean
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itChecksIfRefreshingbackup() 
     */
    public function isRefreshingBackup()
    {
        return $this->is_refreshing_backup;
    }
    
    /**
     * Maybe Refresh backup data
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itMaybeRefreshBackupsData() 
     * @param string $backups_hash_db
     * @param string $current_backup_hash
     * @param boolean $markup
     * @param number $blog_id
     * @param boolean $update
     * @return boolean
     */
    public function maybeRefreshBackupData($backups_hash_db = '', $current_backup_hash = '', $markup = false, $blog_id = 0, $update = false)
    {                
        if ($this->isUserRequestingBackupRefresh($markup, $blog_id, $update)) {
            return true;
        }
        return ( ! $backups_hash_db || $current_backup_hash !== $backups_hash_db );      
    }
    
    /**
     * Is user requesting backup refresh
     * @param boolean $markup
     * @param number $blog_id
     * @param boolean $update
     * @return boolean|mixed|NULL|array
     */
    protected function isUserRequestingBackupRefresh($markup = false, $blog_id = 0, $update = false)
    {
        $get = $this->getSystemInitialization()->getUserInput('get', ['prime_mover_refresh_backups' => $this->getSystemInitialization()->getPrimeMoverSanitizeStringFilter()], 'prime_mover_refresh_backups',
            '', 0, true, true);
        $current_user_id = $this->getSystemInitialization()->getCurrentUserId();
        if ( $this->getSystemAuthorization()->isUserAuthorized() && isset($get['prime_mover_refresh_backups']) && 
            $this->getSystemFunctions()->primeMoverVerifyNonce($get['prime_mover_refresh_backups'], 'refresh_backups_' . $current_user_id)
            ) {
                $this->setIsRefreshingBackup(true);
                return true;
        }
        
        return apply_filters('prime_mover_force_backup_refresh', false, $markup, $blog_id, $update);
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
     * Get System utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverSystemUtilities
     */
    public function getSystemUtilities()
    {
        return $this->getPrimeMover()->getHookedMethods()->getSystemChecks()->getSystemCheckUtilities()->getSystemUtilities();
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
     * Get backups hash in dB
     * @param array $backups
     * @param number $blog_id
     * @return boolean|mixed
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itGetsBackupHashInDb()
     */
    public function getBackupsHashInDb($backups = [], $blog_id = 0)
    {
        if ( ! $backups || ! is_array($backups)) {
            return false;
        }
        if ( empty($backups[$blog_id]) ) {
            return false;
        }
        
        return key($backups[$blog_id]);
    }
    
    /**
     * Get validated backups saved in dB
     * @param string $option_name
     * @return array|mixed|boolean|NULL|array
     */
    public function getValidatedBackupsArrayInDb($option_name = '', $legacy = false)
    {
        if ( ! $option_name ) {
            return [];
        }

        if ($legacy) {
            $main_site = $this->getSystemInitialization()->getMainSiteBlogId();
            $this->getSystemFunctions()->switchToBlog($main_site);
            
            wp_cache_delete('alloptions', 'options');
            $backups = get_option($option_name);
            $this->getSystemFunctions()->restoreCurrentBlog();
        } else {
            $backups = $this->getSystemFunctions()->getSiteOption($option_name, false, true, true);
        }
        
        return $backups;
    }
    
    /**
     * Updated validated backups array
     * @param array $backups_array
     * @param string $backups_hash
     * @param string $option_name
     * @param number $blog_id
     * @param string $previous_hash
     * @return void|boolean
     */
    public function updateValidatedBackupsArrayInDb($backups_array = [], $backups_hash = '', $option_name = '', $blog_id = 0, $previous_hash = '')
    {
        if ( ! $backups_hash || ! $option_name || ! $blog_id ) {
            return;
        }
        if ( ! $this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        $value = [];
        if (is_multisite()) {
            $value = $this->getValidatedBackupsArrayInDb($option_name);
        }        
        if ( false === $value ) {
            $value = [];
        }
        
        if (is_string($previous_hash) && isset($value[$blog_id][$previous_hash])) {
            unset($value[$blog_id][$previous_hash]);
        }
        
        $value[$blog_id][$backups_hash] = $backups_array;
        $value = $this->getSystemFunctions()->mapDeep($value, 'wp_normalize_path', '', ['package_filepath', 'filepath']);
        
        return $this->getSystemFunctions()->updateSiteOption($option_name, $value, true);
    } 
    
    /**
     * Compute backup hash based on latest backup files scenario and customer licensing
     * @param array $backups
     * @param number $blog_id
     * @return string
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itComputesBackupHash() 
     */
    public function computeBackupHash($backups = [], $blog_id = 0)
    {
        $mode = 'free';
        if ( true === apply_filters('prime_mover_is_loggedin_customer', false)) {
            $mode = 'pro';
        }
        $subsite_licensed = '';
        if (is_multisite() && $blog_id) {
            $subsite_licensed = 'free';
            if (true === apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id)) {
                $subsite_licensed = 'pro';
            }
        }
        $string_to_hash = json_encode($backups) . $mode . $subsite_licensed;
        return sha1($string_to_hash);
    }
    
    /**
     * Checks if blog is usable provided by blog ID
     * @param number $blog_id
     * @return boolean
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itChecksIfBlogIdIsUsable()
     * @mainsitesupport_affected
     * 
     * Since 1.2.0, its possible to have blog ID of 1 and that is on a multisite main site.
     * Remove the > 1 check and simply just check if $blog_id is truth value.
     */
    public function blogIsUsable($blog_id = 0)
    {
        return $this->getSystemFunctions()->blogIsUsable($blog_id);
    }
    
    /**
     * Show delete section
     * @param number $blog_id
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itAppendsBlogIdOnBackupMenuUrlOnMultisite()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itRendersBackupSectionWhenItsEmpty()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverBackupUtilities::itRendersBackupSection()
     */
    public function primeMoverManageBackupsSection($blog_id = 0)
    {        
        $note = '';
        $backups_menu_url = $this->getSystemFunctions()->getBackupMenuUrl($blog_id);
        
        if (is_multisite()) {
            $note = '(' . sprintf(esc_html__('blog ID : %d', 'prime-mover'), $blog_id) . ')';
        }
        ?>
        <h3><?php echo sprintf( esc_html__('Manage packages %s', 'prime-mover'), $note );?></h3>	    
	    <p class="prime-mover-managebackups-<?php echo esc_attr($blog_id); ?>"><a href="<?php echo esc_url($backups_menu_url);?>" class="button button-secondary"><?php esc_html_e('Go to Package Manager', 'prime-mover'); ?></a></p> 	
    <?php    
    }
    
    /**
     * Get autobackups user
     * @return number|mixed
     */
    public function getAutoBackupUser()
    {
        return $this->getSystemFunctions()->getAutoBackupUser();
    }

    /**
     * Get blog name and blog ID from cron action
     * @param string $data
     * @return string[]|number[]|mixed[]
     */
    public function getDetailsromCronAction($data = '')
    {
        $blog_id = 0;
        $cron_name = '';
        $exploded = [];
        if (false !== strpos($data, "_")) {
            $exploded = explode("_", $data);
        }
        
        if (!is_array($exploded)) {
            return [$cron_name, $blog_id];
        }
        
        if (!isset($exploded[0]) || !isset($exploded[1])) {
            return [$cron_name, $blog_id];
        }
        
        $cron_name = $exploded[0];
        $blog_id = $exploded[1];
        $blog_id = filter_var($blog_id, FILTER_VALIDATE_INT, ["options" => ["min_range"=> 1]]);
        
        return [$cron_name, $blog_id];
    }
    
    /**
     * Delete autobackup retry meta
     * @param number $blog_id
     */
    public function deleAutoBackupRetryMeta($blog_id = 0)
    {
        if (!$this->getSystemAuthorization()->isDoingAutoBackup()) {
            return;
        }
        
        $userid = $this->getAutoBackupUser();
        if (!$blog_id || !$userid) {
            return;
        }
        
        $auto_backup_retry_key = $this->getSystemInitialization()->getAutoBackupRetryMeta($blog_id, $userid);
        delete_user_meta($userid, $auto_backup_retry_key);
    }
    
    /**
     * Delete progress meta tracker helper for autobackup process.
     * @param number $blog_id
     */
    public function deleteProgressMetaTracker($blog_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blog_id) {
            return;
        }
        
        $user_ip = 'prime_mover_user_anonymous';
        $browser = $this->getSystemInitialization()->getUserAgent(true);
        $user_id = $this->getSystemFunctions()->getAutoBackupUser();
        $mode = 'export';
        
        if (!$user_id || !$browser) {
            return;
        }
        
        $meta_key = $this->getSystemInitialization()->getProgressTrackerMeta($browser, $user_ip, $user_id, $blog_id, $mode);
        delete_user_meta($user_id, $meta_key);
    }
    
    /**
     * Clean up in-progress packages created by auto-backup when its disabled.
     * @param array $in_progress_packages
     * @param array $package_meta
     * @param number $blog_id
     * @return array
     */
    public function maybeCleanUpInProgressAutoBackupPackages($in_progress_packages = [], $package_meta = [], $blog_id = 0)
    {        
        if (!is_multisite() && !$blog_id) {
            $blog_id = 1;
        }
        
        if (!$this->validateCleanUpProceduresData($in_progress_packages, $package_meta, $blog_id)) {
            return $in_progress_packages;
        }        
       
        $packages = [];
        if (isset($in_progress_packages[$blog_id])) {
            $packages = $in_progress_packages[$blog_id];
        }
        
        if (empty($packages)) {
            return $in_progress_packages;
        }
        
        $export_folder = wp_normalize_path($this->getSystemInitialization()->getMultisiteExportFolderPath() . $blog_id . DIRECTORY_SEPARATOR);
       
        
        foreach ($packages as $package) {
            $package_name = $this->validatePackageName($package);
            if (!$package_name) {
                continue;    
            }
            
            $folder_name = pathinfo($package_name, PATHINFO_FILENAME);
            $folder_path_export = wp_normalize_path($export_folder. $folder_name . DIRECTORY_SEPARATOR);            
            $wprime_path_export = wp_normalize_path($export_folder. $package_name);
            
            $in_progress_packages = $this->doDeleteInProgressPackage($folder_path_export, $wprime_path_export, $blog_id, $in_progress_packages);
        }       
        
        return $in_progress_packages;
    }
    
    /**
     * Do delete in-progress package
     * @param string $folder_path_export
     * @param string $wprime_path_export
     * @param number $blog_id
     * @param array $in_progress_packages
     * @param boolean $return
     * @param boolean $update
     * @return array
     */
    public function doDeleteInProgressPackage($folder_path_export = '', $wprime_path_export = '', $blog_id = 0, $in_progress_packages = [], $return = true, $update = false)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || !$blog_id) {
            return $in_progress_packages;
        }
        
        $this->getSystemFunctions()->initializeFs(false);
        global $wp_filesystem;
        
        $folder_deleted = false;
        $file_deleted = false;
        $clear_wip_package_not_exists = false;
        
        if (!$return) {
            $in_progress_packages = $this->getInProgressPackages(); 
        }
        
        if ($this->getSystemFunctions()->nonCachedFileExists($folder_path_export) && $this->getSystemFunctions()->isWpFileSystemUsable($wp_filesystem)) {
            $folder_deleted = $this->getSystemFunctions()->primeMoverDoDelete($folder_path_export);
        }
        
        if ($this->getSystemFunctions()->nonCachedFileExists($wprime_path_export) && $this->getSystemFunctions()->isWpFileSystemUsable($wp_filesystem)) {
            $file_deleted = $this->getSystemFunctions()->primeMoverDoDelete($wprime_path_export);
        }
        
        if (!$this->getSystemFunctions()->nonCachedFileExists($wprime_path_export)) {
            $clear_wip_package_not_exists = true;
        }
        
        if ($folder_deleted && $file_deleted || $clear_wip_package_not_exists) {
            $in_progress_packages = $this->maybeRemoveWipStatusOnError($wprime_path_export, $blog_id, $in_progress_packages, $update);
        }
        
        if ($return) {
            return $in_progress_packages;
        }        
    }
    
    /**
     * Validate package name
     * @param array $package
     * @return boolean|string
     */
    protected function validatePackageName($package = [])
    {
        if (!is_array($package)) {
            return false;
        }
        
        $package_name = key($package);
        if (!is_string($package_name)) {
            return false;
        }
        
        $package_name = trim($package_name);
        if (!$package_name) {
            return false;
        }
        
        if (!$this->getSystemFunctions()->hasTarExtension($package_name)) {
            return false;
        }
        
        return $package_name;
    }
    
    /**
     * Validate clean up procedures of incomplete packages
     * @param array $in_progress_packages
     * @param array $package_meta
     * @param number $blog_id
     * @return boolean
     */
    protected function validateCleanUpProceduresData($in_progress_packages = [], $package_meta = [], $blog_id = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        }
        
        if (!is_array($in_progress_packages) || !is_array($package_meta) || !$blog_id) {
            return false;
        }
        
        if (empty($package_meta['filepath'])) {
            return false;
        }
        
        if (!$this->maybeInProgressPackageIsAutoBackup($in_progress_packages, $package_meta, $blog_id)) {
            return false;
        }
        
        if ($this->maybeSiteCanSupportAutoBackup($blog_id)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if the site can support auto backup
     * @param number $blog_id
     * @return boolean
     */
    public function maybeSiteCanSupportAutoBackup($blog_id = 0)
    {
        if (!is_multisite() && !$blog_id) {
            $blog_id = 1;
        }
              
        $pro_licensed = apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id);
        if (false === $pro_licensed) {
            return false;
        }
      
        if ($this->getSystemUtilities()->maybeDisableAutoBackup()) {
            return false;
        }
        
        if (!is_multisite()) {
            return true;
        }
       
        $subsite_autobackup_status = apply_filters('prime_mover_get_setting', '', 'automatic_backup_subsite_enabled', false, '', true, $blog_id);
        if ('true' !== $subsite_autobackup_status) {
            return false;
        }  
       
        return true;
    }
    
    /**
     * Check if we are processsing in progress packages that created by auto backup
     * @param array $in_progress_packages
     * @param array $package_meta
     * @param number $blog_id
     * @return boolean
     */
    public function maybeInProgressPackageIsAutoBackup($in_progress_packages = [], $package_meta = [], $blog_id = 0)
    {
        $backup_mode = 'manual';
        $progress_data = $this->packageIsInProgress($in_progress_packages, $package_meta['filepath'], $blog_id, false);
        if (is_array($progress_data)) {
            $backup_mode = reset($progress_data);
        }       
        
        return ('automatic' === $backup_mode);
    }
    
    /**
     * For every blog, process clean up
     * @param number $blog_id
     * @param number $autobackup_user
     * @param array $in_progress_packages
     */
    public function doProcessCleanPerBlog($blog_id = 0, $autobackup_user = 0, $in_progress_packages = [])
    {
        wp_clear_scheduled_hook("primeMoverAutomaticBackupEvent_{$blog_id}");
        wp_clear_scheduled_hook("primeMoverProgressIntervalEvent_{$blog_id}");
        
        $usermeta_key = $this->getSystemInitialization()->getAutoBackupRetryMeta($blog_id, $autobackup_user);
        if ($usermeta_key) {
            delete_user_meta($autobackup_user, $usermeta_key);
        }
        
        $hash_meta_key = $this->getProgressHandlers()->generateTrackerId($blog_id, 'export', true);
        $export_parameters = [];
        if ($hash_meta_key) {
            $export_parameters = get_user_meta($autobackup_user, $hash_meta_key, true);
        }
        
        if (!is_array($export_parameters)) {
            return;
        }
        
        $folder_path_export = '';
        $wprime_path_export = '';
        $is_autobackup_process = false;
        
        if (isset($export_parameters['autobackup_process'])) {
            $is_autobackup_process = $export_parameters['autobackup_process'];
        }
        
        if (!$is_autobackup_process) {
            return;
        }
        
        if (!empty($export_parameters['temp_folder_path']) && !empty($export_parameters['target_zip_path'])) {
            $folder_path_export = $export_parameters['temp_folder_path'];
            $wprime_path_export = $export_parameters['target_zip_path'];
        }
        
        if ($hash_meta_key) {
            delete_user_meta($autobackup_user, $hash_meta_key);
        }
        
        return $this->doDeleteInProgressPackage($folder_path_export, $wprime_path_export, $blog_id, $in_progress_packages);
    }
}