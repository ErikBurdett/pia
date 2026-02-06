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

use Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions;
use Codexonics\PrimeMoverFramework\general\PrimeMoverMustUsePluginManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover Lock Utilities
 * Helper functionality for locking resources during export/import processes
 *
 */
class PrimeMoverLockUtilities
{        
    private $system_functions;
    
    /**
     * Constructor
     * @param PrimeMoverSystemFunctions $system_functions
     */
    public function __construct(PrimeMoverSystemFunctions $system_functions)
    {
        $this->system_functions = $system_functions;
    }
    
    /**
     * Init hooks
     */
    public function initHooks()
    {        
        add_action('prime_mover_load_module_apps', [$this, 'lockCurrentDbExportUser'], -2);  
        add_action('prime_mover_load_module_apps', [$this, 'lockCanonicalUploads'], 0);        
        
        add_filter('prime_mover_filter_other_information', [$this, 'lockRootUploads'], 10, 1);        
        add_action('admin_enqueue_scripts', [$this, 'maybeDisableHeartBeat'], 99);        
        
        add_action('wp_enqueue_scripts', [$this, 'maybeDisableHeartBeat'], 99);
        add_action('prime_mover_load_module_apps', [$this, 'maybeUpgradeDatabaseVersion2'], -1);        
        add_filter('pre_get_ready_cron_jobs', [$this, 'maybeDisableCronSystemSchedulerOnMigration'], 10, 1); 

        add_action('prime_mover_db_dump_lock_start', [$this, 'lockDbExportUser']);
        add_action('prime_mover_db_dump_lock_end', [$this, 'unLockDbExportUser'], 10, 3);
        add_filter('prime_mover_db_is_still_locked', [$this, 'checkifDBExportIsLockedToUsers'], 10, 1);

        add_action('prime_mover_after_db_dump_export', [$this, 'unLockAfterDbExport'], 0);
        add_action('prime_mover_shutdown_actions', [$this, 'unLockAfterDbExport'], 0);        
        add_filter('prime_mover_excluded_meta_keys', [$this, 'excludePrimeMoverCoreMetaKeys'], 50, 1);  

        add_filter('prime_mover_filter_error_output', [$this, 'appendLockUserDiagnosticsToLog'], 52, 1);
    }

    /**
     * Append locked user diagnostics to log
     * @param array $error_output
     * @return array
     */
    public function appendLockUserDiagnosticsToLog($error_output = [])
    {
        if (!is_array($error_output) ) {
            return $error_output;
        }

        if (is_multisite()) {
            return $error_output;
        }
        
        $current_db_export_user = $this->getSystemInitialization()->getPrimeMoverCurrentDbExportUser();
        $enabled = 'no';
        if ($current_db_export_user) {
            $enabled = 'yes';
        }

        $error_output['lock_user_diagnostics']['enabled'] = $enabled;
        $error_output['lock_user_diagnostics']['lock_user_id'] = $current_db_export_user;
        $error_output['lock_user_diagnostics']['current_user_id'] = $this->getSystemInitialization()->getCurrentUserId();
        $error_output['lock_user_diagnostics']['current_user_hash'] = $this->getSystemInitialization()->generateHashByUser(true);       
        $error_output['lock_user_diagnostics']['locked_users'] = $this->getLockedDBUsers();

        return $error_output;
    }
    
    /**
     * Exclude lock fall backup user meta keys by Prime Mover in export - import
     * @param array $excluded
     * @return string
     */
    public function excludePrimeMoverCoreMetaKeys($excluded = [])
    {
        $meta_keys = $this->getSystemInitialization()->getExcludedMetaKeyOnExportImport();
        foreach ($meta_keys as $meta_key) {
            if (!in_array($meta_key, $excluded)) {
                $excluded[] = $meta_key;
            }
        }        
        
        return $excluded;
    }
    
    /**
     * Unlock user right after completing database export or hitting a runtime error.
     * This frees user from the lock file
     * Always hooked at priority 0, to make sure users are unlocked before settings got moved.
     */
    public function unLockAfterDbExport()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || is_multisite()) {
            return;
        }
        
        do_action('prime_mover_db_dump_lock_end');       
    }
    
    /**
     * Checks if database export is still locked to any administrators
     * Returns TRUE if there are still users locked otherwise FALSE
     * @param boolean $locked
     * @return boolean
     */
    public function checkifDBExportIsLockedToUsers($locked = false)
    {
        $users = $this->getLockedDBUsers();
        if (!is_array($users)) {
            return false;
        }
       
        if (empty($users)) {
            return false;
        }
        
        $exists = [];
        $users = array_map('trim', $users);
        foreach ($users as $user_hash) {
            $user_hash = trim($user_hash);
            if (!$user_hash) {
                continue;
            }
           
            $valid = primeMoverIsShaString($user_hash, 256);
            if (!$valid) {
                $valid = primeMoverIsShaString($user_hash, 384);
            }
           
            if ($valid) {
                $exists[] = $user_hash;
            }            
        }
        
        $count = count($exists);
        if ($count) {
            return true;
        } else {
            return false;
        }       
    }
    
    /**
     * @param number $current_user_id
     * @param boolean $autobackup_mode
     * @param boolean $clear_all_locks
     */
    public function unLockDbExportUser($current_user_id = 0, $autobackup_mode = false, $clear_all_locks = false)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || is_multisite()) {
            return;
        }
        
        if ($clear_all_locks) {
            $this->clearLock();
            return;
        }
        
        if (!$current_user_id) {
            $current_user_id = $this->getSystemInitialization()->getCurrentUserId();
        }
        
        $current_user_id = (int)$current_user_id;
        if (!$current_user_id) {
            return;
        }
       
        $current_user_hash = $this->getSystemInitialization()->generateHashByUser(true, $autobackup_mode, $current_user_id);
        if (!$current_user_hash) {
            return;
        }        

        if (!$this->isCurrentUserLockedInDbExport($current_user_hash)) {
            return;
        }
      
        $users = $this->getLockedDBUsers();
        foreach ($users as $k => $user_hash) {
            $user_hash = trim($user_hash);
            if (!$user_hash) {
                continue;
            }            
          
            if ($user_hash === $current_user_hash) {
                unset($users[$k]);
                break;
            }
        }
       
        $this->writeLockUsers($users, 'UNLOCKING');        
    }
    
    /**
     * Lock DB export user to lock directory in single-site exports
     */
    public function lockDbExportUser()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || is_multisite()) {
            return;
        }
        
        $current_user_hash = $this->getSystemInitialization()->generateHashByUser(true);        
        if (!$current_user_hash) {
            return;
        }        
        
        if ($this->isCurrentUserLockedInDbExport($current_user_hash)) {
            return;
        }       
        
        $users = $this->getLockedDBUsers();
        if (!is_array($users)) {
            return;
        }
        
        $users[] = $current_user_hash;
        $this->writeLockUsers($users, 'LOCKING');
    }
    
    /**
     * Clear lock
     */
    protected function clearLock()
    {
        $this->getSystemFunctions()->initializeFs(false);
        global $wp_filesystem;
        if (!$this->getSystemFunctions()->isWpFileSystemUsable($wp_filesystem)) {
            return;
        }
        
        $lock_file = $this->getDbLockExportFile();
        if (!$lock_file) {
            return;
        }

        $wp_filesystem->put_contents($lock_file, '', FS_CHMOD_FILE);
    }
    
    /**
     * Write lock users to lock directory
     * @param array $users
     * @param string $mode
     */
    protected function writeLockUsers($users = [], $mode = 'LOCKING')
    {
        $this->clearLock();
        $lock_file = $this->getDbLockExportFile();
        if (!$lock_file) {
            return;
        }
        
        do_action('prime_mover_log_processed_events', "DB LOCK: $mode event update to the lock file:", 1, 'common', __FUNCTION__, $this);
        do_action('prime_mover_log_processed_events', $users, 1, 'common', __FUNCTION__, $this);
        
        foreach ($users as $user) {            
            $user = trim($user);
            if (!$user) {
                continue;
            }
            $user = $user . PHP_EOL;
            $this->getSystemFunctions()->filePutContentsAppend($lock_file, $user);
        } 
    }
    
    /**
     * Get DB lock export file
     * @return boolean|string
     */
    protected function getDbLockExportFile()
    {
        $this->getSystemFunctions()->initializeFs(false);
        global $wp_filesystem;
        
        if (!$this->getSystemFunctions()->isWpFileSystemUsable($wp_filesystem)) {
            return false;
        }
        
        $lock_folder = $this->getSystemInitialization()->getLockFilesFolder();
        $lock_file = wp_normalize_path($lock_folder . '.db_export_lock');
        if (!$wp_filesystem->exists($lock_file)) {
            return false;
        }
        
        return $lock_file;
    }
    
    /**
     * Get locked DB users
     * @return array
     */
    protected function getLockedDBUsers()
    {
        $this->getSystemFunctions()->initializeFs(false);
        global $wp_filesystem;
        if (!$this->getSystemFunctions()->isWpFileSystemUsable($wp_filesystem)) {
            return [];
        }
        
        $lock_file = $this->getDbLockExportFile();
        if (!$lock_file) {
            return [];
        }
        
        $users = $wp_filesystem->get_contents_array($lock_file);
        if (is_array($users)) {
            return array_filter($users, 'trim');
        } else {
            return [];   
        }
    }
    
    /**
     * Checks if current user locked
     * Returns TRUE if usre is already locked, otherwise FALSE
     * @param string $current_user_hash
     * @return boolean
     */
    protected function isCurrentUserLockedInDbExport($current_user_hash = '')
    {        
        $users = $this->getLockedDBUsers();
        if (!is_array($users)) {
            return false;
        }
        
        $users = array_map('trim', $users);
        if (in_array($current_user_hash, $users)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Lock current dB export administrator user in control of the settings while 
     * database dump is in-progress
     * Only affects single-site
     */
    public function lockCurrentDbExportUser()
    {
        if (is_multisite()) {
            return;
        }
        
        $currentdBexport_user = $this->getCurrentDBExportUsers();
        $currentdBexport_user = (int)$currentdBexport_user;
        $this->getSystemInitialization()->setPrimeMoverCurrentDbExportUser($currentdBexport_user);
    }
    
    /**
     * Lock database upgrade to version 2.0 in single-site installation
     * After upgrading to Prime Mover 2.0 for compatibility reasons
     */
    public function maybeUpgradeDatabaseVersion2()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized() || is_multisite()) {
            return;
        }
        
        $latest_backup_markup = PRIME_MOVER_BACKUP_MARKUP_VERSION;
        $option = $this->getSystemInitialization()->getPrimeMoverBackupMarkupVersion();
        
        $backup_markup_db_version = $this->getSystemFunctions()->getBlogOption(1, $option);
        $single_site_db_upgrade_option = $this->getSystemInitialization()->getSingleUpgradedtoVersion2();
       
        if ($this->getSystemFunctions()->getSiteOption($single_site_db_upgrade_option)) {
            return;
        }
        
        $upgrade = false;        
        if (!$backup_markup_db_version ) {
            $upgrade = true;
        }
        
        if (!$upgrade && $backup_markup_db_version !== $latest_backup_markup) {            
            $upgrade = true;
        }
        
        if (!$upgrade) {
            return;
        }
        
        $users = $this->getCurrentDBExportUsers(false);
        if (!is_array($users)) {
            return;
        }
        
        $fallback_keys = $this->getSystemInitialization()->getExcludedMetaKeyOnExportImport();
        foreach ($users as $user_id) {
            foreach($fallback_keys as $key) {
                delete_user_meta($user_id, $key);
            }            
        }
        
        $this->getSystemFunctions()->updateSiteOption($this->getSystemInitialization()->getSingleUpgradedtoVersion2(), 'yes');
    }
 
    /**
     * Get current DB export users
     * If $single is FALSE - returns result as array
     * @param boolean $single
     * @return number|mixed|boolean
     */
    protected function getCurrentDBExportUsers($single = true)
    {
        $args = [];
        $args['role__in'] = 'administrator';
        $args['meta_key'] = $this->getSystemInitialization()->getMigrationCurrentSettings();
        
        $args['meta_value'] = [''];
        $args['meta_compare'] = 'NOT IN';
        
        $args['fields'] = 'ID';
        $string = json_encode($args);
        $param = 'false';
        if ($single) {
            $param = 'true';
        }
        
        $hash_args = hash('adler32', $string . $param);
        $result = wp_cache_get($hash_args);
        if (false === $result) {
            $result = get_users($args);
            if (is_array($result) && $single) {
                $result = reset($result);
                $result = (int)$result;
            }
            
            wp_cache_set($hash_args, $result);
        }
        
        return $result;
    }
    
    /**
     * Disable heartbeat on any Prime Mover page
     * This is to prevent it from interring any locked processes.
     */
    public function maybeDisableHeartBeat()
    {
        if ($this->getSystemFunctions()->isPrimeMoverPage()) {
            wp_deregister_script( 'heartbeat' );
        }        
    }
    
    /**
     * Lock root uploads
     * @param array $ret
     * @return array
     */
    public function lockRootUploads($ret = [])
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $ret;
        }
        
        $ret['root_uploads_information'] = $this->getSystemInitialization()->getInitializedWpRootUploads(true);
        return $ret;
    }
    
    /**
     * Lock canonical uploads
     */
    public function lockCanonicalUploads()
    {
        $this->maybeLockToCanonicalUploadsDir();
        $this->hasCronAction();
    }
    
    /**
     * Maybe lock to canonical uploads directory during restore
     * @param array $ret
     */
    protected function maybeLockToCanonicalUploadsDir()
    {
        $prime_mover_plugin_manager = $this->getSystemInitialization()->getPrimeMoverPluginManagerInstance();
        if (!is_object($prime_mover_plugin_manager)) {
            return;
        }
        
        if (!method_exists($prime_mover_plugin_manager, 'getBlogId')) {
            return;
        }
        
        if (!$this->isLockingPrimeMoverProcesses($prime_mover_plugin_manager, false)) {
            return;
        }
        
        $blog_id = $prime_mover_plugin_manager->getBlogId();
        if (!is_multisite()) {
            $blog_id = 1;
        }
        if (!$blog_id) {
            return;
        }
        
        $ret = apply_filters('prime_mover_get_import_progress', [], $blog_id);
        if (!empty($ret['canonical_uploads_information'])) {
            $this->getSystemInitialization()->setCanonicalUploadsInfo($ret['canonical_uploads_information']);
        }
        
        if (!empty($ret['root_uploads_information'])) {
            $this->getSystemInitialization()->setRootUploadsInfo($ret['root_uploads_information']);
        }        
    }
    
    /**
     * Checks if we are locking any Prime Mover processses
     * @param PrimeMoverMustUsePluginManager $prime_mover_plugin_manager
     * @param boolean $check_lock_file
     * @return boolean
     */
    public function isLockingPrimeMoverProcesses(PrimeMoverMustUsePluginManager $prime_mover_plugin_manager, $check_lock_file = false)
    {        
        $is_locking_prime_mover_process = false;
        if ($prime_mover_plugin_manager->primeMoverMaybeLoadPluginManager()) {
            $is_locking_prime_mover_process = true;
        }
        
        if (!$check_lock_file) {
            return $is_locking_prime_mover_process;
        }
        
        $doing_migration_lock = $this->getDoingMigrationLockFile();        
        if (!$is_locking_prime_mover_process && $this->getSystemFunctions()->nonCachedFileExists($doing_migration_lock)) {
            $is_locking_prime_mover_process = true;
        }
        
        return $is_locking_prime_mover_process;
    }
    
    /**
     * Check if WP cron action is enabled so we can use this to determine if we running a Prime Mover process
     */
    protected function hasCronAction()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $doing_migration_lock = $this->getDoingMigrationLockFile();
        $has_cron_action = false;
        if (has_action('init', 'wp_cron')) {
            $has_cron_action = true;
        }
        
        if ($has_cron_action && $this->getSystemFunctions()->nonCachedFileExists($doing_migration_lock)) {
            $this->getSystemFunctions()->unLink($doing_migration_lock);
        }
        
        if (!$has_cron_action && !$this->getSystemFunctions()->nonCachedFileExists($doing_migration_lock)) {
            $this->getSystemFunctions()->filePutContentsAppend($doing_migration_lock, 'ongoing migration..');
        }
    }
    
    /**
     * Disable cron jobs via direct system scheduler call if we are running migrations
     * @param mixed $cronjobs
     * @return array|NULL
     */
    public function maybeDisableCronSystemSchedulerOnMigration($cronjobs = null)
    {
        if ($this->getSystemFunctions()->nonCachedFileExists($this->getDoingMigrationLockFile())) {
            return [];
        } 
        return null;
    }
    
    /**
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->system_functions;
    }
    
    /**
     * Get system initialization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemInitialization
     */
    public function getSystemInitialization()
    {
        return $this->getSystemFunctions()->getSystemInitialization();
    }
    
    /**
     * Get system authorizations
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()
    {
        return $this->getSystemFunctions()->getSystemAuthorization();
    }

    /**
     * Get doing migration lock file
     * @return string
     */
    public function getDoingMigrationLockFile()
    {
        $lock_files_directory = trailingslashit(wp_normalize_path($this->getSystemInitialization()->getLockFilesFolder()));
        return $lock_files_directory . '.prime_mover_doing_migration';        
    }
    
    /**
     * Open lock file
     * @param string $lock_file
     * @return boolean|resource handle
     * @codeCoverageIgnore
     */
    public function openLockFile($lock_file = '', $render_absolute = true)
    {
        if ( ! $lock_file ) {
            return false;
        }
        global $wp_filesystem;
        if ($render_absolute) {
            $lock_file_path = $wp_filesystem->abspath() . $lock_file;
        } else {
            $lock_file_path = $lock_file;
        }
        
        return @fopen($lock_file_path, "wb");
    }
    
    /**
     * Create lock file using native PHP flock
     * @param $fp
     * @return boolean
     * @codeCoverageIgnore
     */
    public function createProcessLockFile($fp)
    {
        return flock($fp, LOCK_EX);
    }
    
    /**
     * Unlock file after processing
     * @codeCoverageIgnore
     */
    public function unLockFile($fp)
    {
        return flock($fp, LOCK_UN);
    }
    
    /**
     * Close dropbox lock
     * @param $fp
     * @codeCoverageIgnore
     */
    public function closeLock($fp)
    {
        @fclose($fp);
    }    
}