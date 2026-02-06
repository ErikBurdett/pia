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

use Codexonics\PrimeMoverFramework\users\PrimeMoverUserFunctions;
use stdclass;
use SplFixedArray;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover User Utilities
 * Helper functionality for user import - export processes
 *
 */
class PrimeMoverUserUtilities
{         
    private $user_functions;
    private $user_equivalence_methods;
    
    /**
     * Get user functions
     * @param PrimeMoverUserFunctions $user_functions
     */
    public function __construct(PrimeMoverUserFunctions $user_functions)
    {
        $this->user_functions = $user_functions; 
        $this->user_equivalence_methods = [
                'fixed',
                'native'
            ];
    }
    
    /**
     * Get user equivalence methods
     * @return string[]
     */
    public function getUserEquivaelenceMethods()
    {
        return $this->user_equivalence_methods;
    }
    
    /**
     * Get user functions
     * @return \Codexonics\PrimeMoverFramework\users\PrimeMoverUserFunctions
     */
    public function getUserFunctions()
    {
        return $this->user_functions;
    }
    
    /**
     * Get system authorization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemAuthorization
     */
    public function getSystemAuthorization()
    {
        return $this->getUserFunctions()->getCliArchiver()->getSystemAuthorization();
    }  
  
    /**
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getUserFunctions()->getCliArchiver()->getSystemFunctions();
    }
    
    /**
     * Get system initialization
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemInitialization
     */
    public function getSystemInitialization()
    {
        return $this->getUserFunctions()->getCliArchiver()->getSystemInitialization();
    }
    
    /**
     * Exclude user meta keys
     * @param number $user_id_updated
     * @param string $meta_key
     * @return mixed|NULL|array
     */
    public function getExcludedUserMetaKeys($user_id_updated = 0, $meta_key = '')
    {        
        return apply_filters('prime_mover_excluded_meta_keys', ['session_tokens'], $user_id_updated, $meta_key);
    }
    
    /**
     * Checks if user meta is excluded
     * @param string $meta_key
     * @param number $user_id_updated
     * @return boolean
     */
    public function isUserMetaExcluded($meta_key = '', $user_id_updated = 0)
    {
        return in_array($meta_key, $this->getExcludedUserMetaKeys($user_id_updated, $meta_key));    
    }
           
    /**
     * Get all user metas given user ID
     * @param number $user_id
     * @return array
     */
    public function getAllUserMeta($user_id = 0)
    {
        return get_user_meta($user_id, '', true);
    }
    
    /**
     * Get progress handlers
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverProgressHandlers
     */
    public function getProgressHandlers()
    {
        return $this->getUserFunctions()->getCliArchiver()->getProgressHandlers();    
    }
    
    /**
     * Update all affected caps user meta
     * @param array $user_meta
     * @param string $current_cap_prefix
     * @param string $target_cap_prefix
     * @param string $current_level_prefix
     * @param string $target_level_prefix
     * @return array
     */
    public function updateAllAffectedCaps($user_meta = [], $current_cap_prefix = '', $target_cap_prefix = '', $current_level_prefix = '' , $target_level_prefix = '')
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return $user_meta;
        }
        
        if ( ! $current_cap_prefix || ! $target_cap_prefix || ! $current_level_prefix || ! $target_level_prefix  || empty($user_meta) || ! is_array($user_meta)) {
            return $user_meta;
        }
        
        $tmp = [];
        if (array_key_exists($current_cap_prefix, $user_meta)) {
            $tmp = $user_meta[$current_cap_prefix];
            unset($user_meta[$current_cap_prefix]);
            $user_meta[$target_cap_prefix] = $tmp;
        }
       
        if (array_key_exists($current_level_prefix, $user_meta)) {
            $tmp = $user_meta[$current_level_prefix];
            unset($user_meta[$current_level_prefix]);
            $user_meta[$target_level_prefix] = $tmp;
        }
        
        return $user_meta;
    }
    
    /**
     * Write user to json file
     * @param stdclass $user
     * @param string $user_export_filepath
     * @param array $ret
     * @param number $blog_id
     * @return void|number|void
     */
    public function writeUserToJson(stdclass $user, $user_export_filepath = '', $ret = [], $blog_id = 0)
    {        
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }        
        $user_id = $user->ID;
        $user_metas = apply_filters('prime_mover_filter_user_metas', $this->getAllUserMeta($user_id), $ret, $blog_id, $user);
        $encoded_user = apply_filters('prime_mover_filter_export_user_data', json_encode($user), $ret, $blog_id, $user, $user_id); 
       
        $user_written = $this->getSystemFunctions()->filePutContentsAppend($user_export_filepath, $encoded_user . PHP_EOL);  
        $user_exported = 0;
        if (false !== $user_written) {
            $encoded_metas = apply_filters('prime_mover_filter_export_usermeta_data', json_encode($user_metas), $ret, $blog_id, $user, $user_id);
            $user_exported = $this->getSystemFunctions()->filePutContentsAppend($user_export_filepath, $encoded_metas . PHP_EOL);  
        }
       
        return $user_exported;                  
    }   
    
    /**
     * Return path to user export file
     * @param string $tmp_folderpath
     * @return string
     */
    public function returnPathToUserExportFile($tmp_folderpath = '')
    {
        $user_export_file = $this->getUserFunctions()->generateUserExportFileName();
        return $tmp_folderpath . $user_export_file;
    }
 
    /**
     * Write special user metas to json
     * @param array $ret
     */
    public function writeSpecialUserMetasToJson($ret = [])
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return $ret;
        }
        
        if ( ! isset($ret['usermeta_keys_export_adjust']) || ! isset($ret['temp_folder_path']) ) {
            return;
        }
        
        $special_user_metas = $ret['usermeta_keys_export_adjust'];
        $encoded_special_user_metas = json_encode($special_user_metas);
        
        $tmp_folderpath = $ret['temp_folder_path'];
        $user_export_filepath = $this->returnPathToUserMetaExportFile($tmp_folderpath);        
        $this->getSystemFunctions()->filePutContentsAppend($user_export_filepath, $encoded_special_user_metas);
    }
    
    /**
     * Return path to user meta export file
     * @param string $tmp_folderpath
     * @return string
     */
    public function returnPathToUserMetaExportFile($tmp_folderpath = '')
    {
        $usermeta_export_file = $this->getUserFunctions()->generateUserMetaExportFileName();
        return $tmp_folderpath . $usermeta_export_file;
    }
    
    /**
     * Credits: https://gist.github.com/philipnewcomer/59a695415f5f9a2dd851deda42d0552f
     * @param string $username
     * @return string
     */
    public function generateUniqueUserName( $username = '' ) {
        
        $username = sanitize_user( $username );
        
        static $i;
        if ( null === $i ) {
            $i = 1;
        } else {
            $i ++;
        }
        if ( ! username_exists( $username ) ) {
            return $username;
        }
        $new_username = sprintf( '%s-%s', $username, $i );
        if ( ! username_exists( $new_username ) ) {
            return $new_username;
        } else {
            return call_user_func( [$this, __FUNCTION__], $username );
        }
    }    
   
    /**
     * Get user export file
     * @param array $ret
     * @return boolean|boolean|boolean[]|string[]
     */
    protected function getUserExportFile($ret = [])
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return false;
        }
        
        if (empty($ret['unzipped_directory'])) {
            return false;
        }
        
        $unzipped_directory = $ret['unzipped_directory'];
        if (isset($ret['blog_id'])) {
            $unzipped_directory = $this->getSystemInitialization()->getDynamicPathsPreviewDomains($unzipped_directory, $ret['blog_id']);
        }
        
        $users_export_file = $this->getUserFunctions()->getUsersExportFilePath($ret['unzipped_directory']);
        if (!$users_export_file ) {
            return false;
        }
        
        return $users_export_file;
    }
    
    /**
     * Get package first user
     * @param array $ret
     * @return boolean|array
     */
    public function getPackageFirstUser($ret = [])
    {  
        $users_export_file = $this->getUserExportFile($ret);
        if (!$users_export_file ) {            
            return false;
        }
        
        list($users_json, $decrypt) = $users_export_file;
        $handle = fopen($users_json, 'rb'); 
        if (!$handle) {
            return false;
        }
 
        $first_user_id = 0;
        $first_user_email = '';
        
        while(!feof($handle)){
            $line = fgets($handle);
            if (false === $line) {
                break;
            }
            
            if ($decrypt) {
                $line = apply_filters('prime_mover_decrypt_data', $line);
            }  
            
            $user_array = json_decode($line, true);
            if (!isset($user_array['ID']) || !isset($user_array['user_email'])) {
                break;
            }
            
            $first_user_id = $user_array['ID'];
            $first_user_email = $user_array['user_email'];
            
            break;
        }
        
        if (!$first_user_id || !$first_user_email) {
            return false;
        }
        
        return [$first_user_id, $first_user_email];        
    }
    
    /**
     * Process user import
     * @param array $ret
     * @param number $blog_id
     * @param number $start_time
     * @return array
     */
    public function processUserImport($ret = [], $blog_id = 0, $start_time = 0)
    {
        global $wp_filesystem;
        
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $ret;
        }
       
        $this->getSystemFunctions()->switchToBlog($blog_id);        
        $users_export_file = $this->getUserExportFile($ret);
        if (!$users_export_file ) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            return $ret;
        }
        
        list($users_json, $decrypt) = $users_export_file;        
        list($tmp, $ret) = $this->setTmpLog($ret);        
        $handle = fopen($users_json, 'rb');       
        
        if ( ! $handle) {
            $this->getSystemFunctions()->restoreCurrentBlog();
            $ret['error'] = esc_html__('Unable to open user import file.', 'prime-mover');
            return $ret;
        }
       
        if ( ! empty($ret['users_import_offset']) ) {       
            fseek($handle, $ret['users_import_offset']);
            unset($ret['users_import_offset']);
        }                 
        
        $users_imported = $this->getUsersImported($ret);
        $this->doUserImportProgress($users_imported, $ret);
        $user_max_id = 0;
        if (isset($ret['user_max_id_import'])) {
            $user_max_id = $ret['user_max_id_import'];
        }
        $user_max_id = (int)$user_max_id;
        
        $total_users = 0;
        if (isset($ret['total_users_equivalence'])) {
            $total_users = $ret['total_users_equivalence'];
        }
        $total_users = (int)$total_users;        
        while(!feof($handle)){ 
           
            $is_user_meta_retry = false;
            $is_retry = false;
            $do_meta_loops = true;
            
            $orig_pos = ftell($handle);
            $line = fgets($handle);
            if (false === $line) {
                break;
            }
            if ($decrypt) {
                $line = apply_filters('prime_mover_decrypt_data', $line);
            }            
            
            $user_import = false;
            $source_user_id = 0;
            $user_tmp_encrypted = false;
            if (isset($ret['user_tmp_data_encrypted']) && true === $ret['user_tmp_data_encrypted']) {
                $user_tmp_encrypted = true;
            }
            if (isset($ret['correctotherusermetaprefixes'])) {                
                $do_meta_loops = false;
                $user_id_updated = $ret['user_id_updated_under_process'];
                
                $user_array = [];                
                unset($ret['user_id_updated_under_process']);                
                unset($ret['correctotherusermetaprefixes']);
                
            } elseif (!empty($ret['users_meta_import_tmp_log']) && !empty($ret['user_id_updated_under_process'])) {
                
                $user_tmp_data = $wp_filesystem->get_contents($ret['users_meta_import_tmp_log']);
                if ($user_tmp_encrypted) {
                    $user_tmp_data = apply_filters('prime_mover_decrypt_data', $user_tmp_data);
                }                
                $user_array = json_decode($user_tmp_data, true);               
                $this->getSystemFunctions()->primeMoverDoDelete($ret['users_meta_import_tmp_log'], true);
                
                unset($ret['users_meta_import_tmp_log']);
                $is_user_meta_retry = true;
               
                $user_id_updated = $ret['user_id_updated_under_process'];
                unset($ret['user_id_updated_under_process']);
                
            } else {     
                $user_array = json_decode($line, true);
                if (isset($user_array['ID'])) {
                    $source_user_id = $user_array['ID'];
                    $user_import = true;
                }
            }
 
            if ($user_import && !$is_user_meta_retry && $do_meta_loops) {  
                list($user_id_updated, $user_array, $source_user_id, $ret) = $this->doMainUserImport($user_array, $source_user_id, $ret, $tmp);                
                
            }  elseif (isset($user_id_updated) && $user_id_updated && !is_wp_error($user_id_updated) && is_array($user_array)) {                  
                list($users_imported, $user_id_updated, $is_retry, $ret) = $this->doUserMetaImport($user_array, $user_id_updated, $users_imported, $ret, $blog_id, $start_time, $orig_pos, $do_meta_loops); 
            }   
            
            if (isset($source_user_id) && $source_user_id && !is_wp_error($source_user_id)) {
                $source_user_id = (int)$source_user_id;
                if ($source_user_id > $user_max_id) {
                    $user_max_id = $source_user_id;
                }
                
                if ($user_max_id) {
                    $ret['user_max_id_import'] = $user_max_id;
                }
                
                if ($source_user_id) {
                    $total_users = $total_users + 1;
                }
                
                $ret['total_users_equivalence'] = $total_users;
            } 
            
            if ($is_retry) {
                $this->getSystemFunctions()->restoreCurrentBlog();
                return $ret;
            }
            
            $retry_timeout = apply_filters('prime_mover_retry_timeout_seconds', PRIME_MOVER_RETRY_TIMEOUT_SECONDS, __FUNCTION__);
            if ((microtime(true) - $start_time) > $retry_timeout) {   
                /**
                 * In multisite calls, restore_current_blog() called on doUserImportRetry()
                 */
                return $this->doUserImportRetry($ret, $users_imported, $handle, $retry_timeout, $blog_id);
            }           
        }
  
        /**
         * In multisite calls, restore_current_blog() called on cleanUpAfterUserImport()
         */
        return $this->cleanUpAfterUserImport($ret, $handle);
    }  
 
    /**
     * Clean up after user import
     * @param array $ret
     * @param resource $handle
     * @return array
     */
    protected function cleanUpAfterUserImport($ret = [], $handle = null)
    {
        if (is_resource($handle)) {
            fclose($handle);
        }
        $this->getSystemFunctions()->restoreCurrentBlog();
        
        if (isset($ret['users_import_offset'])) {
            unset($ret['users_import_offset']);
        }
        
        if (isset($ret['total_users_imported'])) {
            unset($ret['total_users_imported']);
        }
        
        if (isset($ret['users_meta_import_tmp_log'])) {
            unset($ret['users_meta_import_tmp_log']);
        }
        
        if (isset($ret['user_id_updated_under_process'])) {
            unset($ret['user_id_updated_under_process']);
        }
        
        if (isset($ret['users_meta_processed_count'])) {
            unset($ret['users_meta_processed_count']);
        }
        
        return $ret;
    }
    
    /**
     * Set tmp log
     * @param array $ret
     * @return array
     */
    protected function setTmpLog($ret = [])
    {
        if (isset($ret['user_import_tmp_log']) ) {
            $tmp = $ret['user_import_tmp_log'];
        } else {            
            $tmp = $this->getSystemInitialization()->wpTempNam();
            $ret['user_import_tmp_log'] = $tmp;
        } 
        
        return [$tmp, $ret];
    }
    
    /**
     * Get users imported
     * @param array $ret
     * @return array
     */
    protected function getUsersImported($ret = [])
    {
        $users_imported = 0;
        if (isset($ret['total_users_imported'])) {
            $users_imported = (int)$ret['total_users_imported'];
        } 
        
        return $users_imported;
    }
    
    /**
     * Do user import progress
     * @param number $users_imported
     * @param array $ret
     */
    protected function doUserImportProgress($users_imported = 0, $ret = [])
    {
        $user_import_progress = '';
        if ($users_imported) {
            $user_import_progress = sprintf(esc_html__('%d completed.', 'prime-mover'), $users_imported);
        }        
        
        $user_meta_processed_count = 0;
        if (isset($ret['users_meta_processed_count'])) {
            $user_meta_processed_count = (int)$ret['users_meta_processed_count'];            
        }
        
        $user_meta_prefix_processed = 0;
        if (isset($ret['users_meta_prefix_processed_count'])) {
            $user_meta_prefix_processed = (int)$ret['users_meta_prefix_processed_count'];
        }        
        
        if ($user_meta_prefix_processed && $user_meta_processed_count) {
            $user_import_progress = sprintf(esc_html__('%d completed. %d metas processed. %d prefixes adjusted', 'prime-mover'), $users_imported, $user_meta_processed_count, $user_meta_prefix_processed);
        } elseif ($user_meta_processed_count) {
            $user_import_progress = sprintf(esc_html__('%d completed. %d metas processed.', 'prime-mover'), $users_imported, $user_meta_processed_count);
        }       
        
        $this->getProgressHandlers()->updateTrackerProgress(sprintf(esc_html__('Importing users.. %s', 'prime-mover'), $user_import_progress)); 
    }
    
    /**
     * Do main user import
     * @param array $user_array
     * @param number $source_user_id
     * @param array $ret
     * @param string $tmp
     * @return array
     */
    protected function doMainUserImport($user_array = [], $source_user_id = 0, $ret = [], $tmp = '')
    {
        $this->getUserFunctions()->getUserQueries()->maybeEnableUserImportExportTestMode(1, false);
        
        $user_email = $user_array['user_email'];
        $user_username = $user_array['user_login'];
        $user_email_exist = email_exists($user_email);
        
        $username_exist = username_exists($user_username);
        $original_pass = $user_array['user_pass'];
        $insert = false;
        $update = false;
        $user_id_updated = 0;
        $import_blog_id = $this->getSystemInitialization()->getImportBlogID();
        
        if ($user_email_exist) {            
            $update = true;
            $user_id_updated = $user_email_exist; 
            $user_array['ID'] = $user_id_updated;
        } elseif ($username_exist) {            
            $unique_username = $this->generateUniqueUserName($user_username);
            $user_array['user_login'] = $unique_username;
            $insert = true;            
        } else {
            $insert = true;
        }
        
        if ($insert) {
            unset($user_array['ID']);
            $this->getUserFunctions()->restoreOriginalPass($original_pass, $ret);
            
            do_action('prime_mover_log_processed_events', $user_email . " user email is new, inserting..", $import_blog_id, 'import', __FUNCTION__, $this, false, true);
            $user_id_updated  = $this->getUserFunctions()->getUserQueries()->insertUser($user_array, $source_user_id);
            $this->getUserFunctions()->removeOriginalPass();
        }
        
        if ($update && isset($user_array['user_login'], $user_array['user_pass'], $user_array['user_email'], $user_array['user_activation_key'])) {
            unset($user_array['user_login']);
            unset($user_array['user_pass']);
            unset($user_array['user_email']);
            unset($user_array['user_activation_key']);  
            
            do_action('prime_mover_log_processed_events', $user_email . " user email exist, updating..", $import_blog_id, 'import', __FUNCTION__, $this, false, true);
            $user_id_updated  = wp_update_user($user_array);
        }

        if (is_wp_error($user_id_updated)) {
            $msg_error = $user_id_updated->get_error_message();
            if (is_string($msg_error)) {
                do_action('prime_mover_log_processed_events', "User import error: $msg_error" , $import_blog_id, 'import', __FUNCTION__, $this, false, true);
            }
            if (is_array($user_array)) {
                do_action('prime_mover_log_processed_events', $user_array , $import_blog_id, 'import', __FUNCTION__, $this, false, true);
            }
        }
        
        if (!is_wp_error($user_id_updated) && !is_wp_error($source_user_id) && $user_id_updated && $source_user_id) {
            do_action('prime_mover_log_processed_events', $user_email . " successfully added with user ID: " . $user_id_updated, $import_blog_id, 'import', __FUNCTION__, $this, false, true);
            $user_log = json_encode([$source_user_id => $user_id_updated]);
            $this->getSystemFunctions()->filePutContentsAppend($tmp, $user_log . PHP_EOL);
        }   
        
        return [$user_id_updated, $user_array, $source_user_id, $ret];
    }
   
    /**
     * Do import user retry
     * @param array $ret
     * @param number $users_imported
     * @param mixed $handle
     * @param number $retry_timeout
     * @param number $blog_id
     * @param array $user_array
     * @param number $orig_pos
     * @param number $user_id_updated
     * @param number $user_meta_processed
     * @return boolean[]|number[]
     */
    protected function doUserImportRetry($ret = [], $users_imported = 0, $handle = null, $retry_timeout = 0, $blog_id = 0, $user_array = [], $orig_pos = 0, $user_id_updated = 0, $user_meta_processed = 0)
    {        
        $ret['total_users_imported'] = $users_imported;        
        $user_meta_timeout = false;
        
        if (is_resource($handle)) {
            $ret['users_import_offset'] = ftell($handle);
            fclose($handle);
        } else {
            $user_meta_timeout = true;
            $ret['users_import_offset'] = $orig_pos;
            $ret['users_meta_processed_count'] = $user_meta_processed;
            $ret['user_id_updated_under_process'] = $user_id_updated;
            $ret['user_tmp_data_encrypted'] = false;
        }   
        
        $user_meta_written = false;
        $user_meta_tmp = '';
        $db_encryption_key = $this->getSystemInitialization()->getDbEncryptionKey();
        if ($user_meta_timeout) {
            $user_meta_tmp = $this->getSystemInitialization()->wpTempNam();            
            $encoded_metas = apply_filters('prime_mover_force_encrypt_data', json_encode($user_array));
            $user_meta_written = $this->getSystemFunctions()->filePutContentsAppend($user_meta_tmp, $encoded_metas . PHP_EOL);             
        }
        if ($db_encryption_key && $user_meta_written) {
            $ret['user_tmp_data_encrypted'] = true;
        }
        
        if ($user_meta_written && $user_meta_tmp) {
            $ret['users_meta_import_tmp_log'] = wp_normalize_path($user_meta_tmp);
        }
        
        $this->getSystemFunctions()->restoreCurrentBlog();        
        do_action('prime_mover_log_processed_events', "$retry_timeout seconds time out on user import, $users_imported users imported" , $blog_id, 'import', __FUNCTION__, $this);
       
        if ($user_meta_timeout) {
            return [$users_imported, $user_id_updated, true, $ret];
        } else {
            return $ret;
        }
    }
    
    /**
     * Do user meta import
     * @param array $user_array
     * @param number $user_id_updated
     * @param number $users_imported
     * @param array $ret
     * @param number $blog_id
     * @param number $start_time
     * @param number $orig_pos
     * @param boolean $do_meta_loops
     * @return boolean[]|number[]|number[]|boolean[]|mixed[]|NULL[]|array[]
     */
    protected function doUserMetaImport($user_array = [], $user_id_updated = 0, $users_imported = 0, $ret = [], $blog_id = 0, $start_time = 0, $orig_pos = 0, $do_meta_loops = true)
    {
        $this->getUserFunctions()->getUserQueries()->maybeEnableUserImportExportTestMode(1, false);
       
        $user_meta_processed = 0;
        if (isset($ret['users_meta_processed_count'])) {
            $user_meta_processed = (int)$ret['users_meta_processed_count'];
            unset($ret['users_meta_processed_count']);
        }
        
        if ($do_meta_loops) {
            foreach($user_array as $meta_key => $meta_value) {
                if ($this->isUserMetaExcluded($meta_key, $user_id_updated)) {
                    if (!is_wp_error($meta_key) && !is_wp_error($user_id_updated)) {
                        do_action('prime_mover_log_processed_events', "$meta_key - user meta key excluded for user $user_id_updated." , $blog_id, 'import', __FUNCTION__, $this);
                    }
                    continue;
                }
                $this->maybeTestUserMetaDelay();
                $meta_value = maybe_unserialize(reset($meta_value));                
                do_action('prime_mover_update_user_meta', $user_id_updated, $meta_key, $meta_value);
                
                unset($user_array[$meta_key]);
                $user_meta_processed++;
                
                $retry_timeout = apply_filters('prime_mover_retry_timeout_seconds', PRIME_MOVER_RETRY_TIMEOUT_SECONDS, __FUNCTION__);
                if ( (microtime(true) - $start_time) > $retry_timeout) {
                    return $this->doUserImportRetry($ret, $users_imported, null, $retry_timeout, $blog_id, $user_array, $orig_pos, $user_id_updated, $user_meta_processed);
                }
            }
            
            if ($user_id_updated) {
                $users_imported++;
            }
        }        
        
        $retry_params = [$orig_pos, $user_meta_processed, $user_id_updated, $users_imported];        
        $ret = apply_filters('prime_mover_after_user_meta_import', $ret, $user_id_updated, $blog_id, $start_time, $retry_params, $do_meta_loops);         
        $retry = false;
        if (isset($ret['correctotherusermetaprefixes'])) {
            $retry = true;
        }
        
        return [$users_imported, $user_id_updated, $retry, $ret];        
    }
    
    /**
     * Add user meta delay
     */
    protected function maybeTestUserMetaDelay()
    {
        if (defined('PRIME_MOVER_TEST_USER_META_DELAY') && PRIME_MOVER_TEST_USER_META_DELAY) {
            $delay = (int)PRIME_MOVER_TEST_USER_META_DELAY;
            $this->getSystemInitialization()->setProcessingDelay($delay, true);
        }
    }
    
    /**
     * Get user capability metas
     * @param string $prefix
     * @param number $blog_id
     * @return array|string[]
     */
    public function getUserCapabilityMetas($prefix = '', $blog_id = 0)
    {
        if ( ! $prefix ) {
            $prefix = $this->getSystemFunctions()->getDbPrefixOfSite($blog_id);
        }
        
        if ( ! $prefix ) {
            return [];
        }
        
        $cap_prefix = $prefix . 'capabilities';        
        $user_level_prefix = $prefix . 'user_level';
        
        return [$cap_prefix, $user_level_prefix];        
    }   

    /**
     * Estimated required SPlFixedArray memory from user max ID.
     * @param number $user_max_id
     * @return number
     */
    protected function computeFixedArrayMemory($user_max_id = 0)
    {
        $mem_required = 15.999995 * ($user_max_id) + 1536.2700;
        return ceil($mem_required);
    }
  
    /**
     * Estimated required native array memory from total users
     * @param number $total_user
     * @return number
     */
    protected function computeNativeArrayMemory($total_user = 0)
    {
        $mem_required = 42.96164450 * ($total_user) + 4125183.2059833;
        return ceil($mem_required);
    }
    
    /**
     * Compute user equivalence method
     * @param array $ret
     * @param number $blog_id
     * @param number $user_max_id
     * @param number $total_user_count
     * @return string|mixed|NULL|array
     */
    protected function computeUserEquivalenceMethod($ret = [], $blog_id = 0, $user_max_id = 0, $total_user_count = 0)
    {
        if (defined('PRIME_MOVER_FORCE_USER_EQUIVALENCE_METHOD') && PRIME_MOVER_FORCE_USER_EQUIVALENCE_METHOD && 
            in_array(PRIME_MOVER_FORCE_USER_EQUIVALENCE_METHOD, $this->getUserEquivaelenceMethods())) {
                
                do_action('prime_mover_log_processed_events', "USER EQUVALENCE METHOD: Forcing method to " . PRIME_MOVER_FORCE_USER_EQUIVALENCE_METHOD, $blog_id, 'import', __FUNCTION__, $this);
                return PRIME_MOVER_FORCE_USER_EQUIVALENCE_METHOD;
        }
       
        $method = apply_filters('prime_mover_get_user_equivalence_method', 'fixed', $ret, $blog_id);
        if ('native' === $method) {
            do_action('prime_mover_log_processed_events', "USER EQUIVALENCE METHOD: Setting method to NATIVE as per user settings.", $blog_id, 'import', __FUNCTION__, $this);
            return $method;
        }
        
        $method = 'fixed';
        if (!$user_max_id || !$total_user_count) {
            do_action('prime_mover_log_processed_events', "USER EQUIVALENCE METHOD: Bail out and set method to FIXED because is not available.", $blog_id, 'import', __FUNCTION__, $this);
            return $method;
        }
        
        $fixed_memory_req = $this->computeFixedArrayMemory($user_max_id);
        $native_memory_req = $this->computeNativeArrayMemory($total_user_count); 
        
        $fixed_array_memory_limit = PRIME_MOVER_FIXED_ARRAY_MEMORY_LIMIT;
        $native_array_memory_limit = PRIME_MOVER_NATIVE_ARRAY_MEMORY_LIMIT;
        
        if ($fixed_memory_req > $fixed_array_memory_limit) {
            if ($native_memory_req < $native_array_memory_limit) {
                $method = 'native';
            }
        }        
        
        do_action('prime_mover_log_processed_events', 
            "USER EQUIVALENCE METHOD: Computed method {$method} with fixed mem req {$fixed_memory_req} bytes, native mem req {$native_memory_req} bytes using fixed array mem limit of {$fixed_array_memory_limit} 
            bytes and native array mem limit {$native_array_memory_limit} bytes", 
        $blog_id, 'import', __FUNCTION__, $this);
        
        return $method;
    }
    
    /**
     * Get user equivalence instance
     * @param array $ret
     * @param number $blog_id
     * @return \SplFixedArray|array
     */
    protected function getUserEquivalenceInstance($ret = [], $blog_id = 0)
    {
        $user_equivalence = null;
        if (isset($ret['user_equivalence'])) {
            $user_equivalence = $ret['user_equivalence'];
        } 
        
        if ($user_equivalence) {
            return $user_equivalence;
        }
        
        $size = $this->getUserFunctions()->countUserMaxId();
        $size = $size + 1;
        $user_max_id = 0;
        
        if (isset($ret['user_max_id_import'])) {
            $user_max_id = $ret['user_max_id_import'];
            $user_max_id = (int)$user_max_id;
            $user_max_id = $user_max_id + 1;
        }
        
        if ($user_max_id > $size) {
            $size = $user_max_id;
        }
        
        $total_user_count = 0;
        if (isset($ret['total_users_equivalence'])) {
            $total_user_count = $ret['total_users_equivalence'];
        }
        
        $method = $this->computeUserEquivalenceMethod($ret, $blog_id, $user_max_id, $total_user_count);
        if ('fixed' === $method) {
            return new SplFixedArray($size);
        } else {
            return [];
        }                
    }
    
    /**
     * Get user equivalence
     * @param string $tmp
     * @param number $blog_id
     * @param array $ret
     * @param number $start_time
     * @return array
     */
    public function getUserEquivalence($tmp = '', $blog_id = 0, $ret = [], $start_time = 0)
    {        
        if ( ! $this->getSystemFunctions()->nonCachedFileExists($tmp) || ! $blog_id) {
            return [];
        }
        
        $user_equivalence = $this->getUserEquivalenceInstance($ret);
        $handle = fopen($tmp, 'rb');
        if ( ! $handle ) {
            $ret['error'] = esc_html('Unable to open user equivalence file.', 'prime-mover');
            return $ret;
        }
        if ( ! empty($ret['users_equivalence_offset']) ) {
            fseek($handle, $ret['users_equivalence_offset']);
        }        
        list($mismatch, $count, $processed) = $this->computeEquivalenceParameters($ret);
        
        $user_equivalence_progress = '';
        if ($processed) {
            $user_equivalence_progress = sprintf(esc_html__('%d completed.', 'prime-mover'), $processed);
        }
        
        $this->getProgressHandlers()->updateTrackerProgress(sprintf(esc_html__('Computing user equivalence.. %s', 'prime-mover'), $user_equivalence_progress)); 
        while(!feof($handle)){
            $line = fgets($handle);
            if (false === $line) {
                break;
            }
            $user_array = json_decode($line, true);
            $source_user_id = (int)key($user_array);
            $new_user_id = (int)reset($user_array);    
            if ($new_user_id !== $source_user_id) {
                $mismatch++;
            }            
            $this->getUserFunctions()->getUserQueries()->maybeEnableUserImportExportTestMode(5, false);
            $user_equivalence = $this->getUserFunctions()->addNewElement($user_equivalence, $source_user_id, $new_user_id);            
            $count++;
            
            $retry_timeout = apply_filters('prime_mover_retry_timeout_seconds', PRIME_MOVER_RETRY_TIMEOUT_SECONDS, __FUNCTION__);
            if ( (microtime(true) - $start_time) > $retry_timeout) {
                return $this->doUserEquivalenceRetry($ret, $handle, $user_equivalence, $mismatch, $count, $blog_id, $retry_timeout);
            }        
        }        
        
        return $this->cleanUpAndReturnEquivalence($ret, $user_equivalence, $mismatch);
    }
    
    /**
     * Compute equivalence parameters
     * @param array $ret
     * @return array
     */
    protected function computeEquivalenceParameters($ret = [])
    {
        $mismatch = 0;
        if (isset($ret['user_mismatch_count'])) {
            $mismatch = $ret['user_mismatch_count'];
        }
        
        $count = 0;
        if (isset($ret['equivalence_count'])) {
            $count = (int)$ret['equivalence_count'];
        }
        
        $processed = 0;
        if (isset($ret['equivalence_count'])) {
            $processed = (int)$ret['equivalence_count'];
        }
        
        return [$mismatch, $count, $processed];
    }
    
    /**
     * Do user equivalence retry
     * @param array $ret
     * @param resource $handle
     * @param mixed $user_equivalence
     * @param number $mismatch
     * @param number $count
     * @param number $blog_id
     * @param number $retry_timeout
     * @return array
     */
    protected function doUserEquivalenceRetry($ret = [], $handle = null, $user_equivalence = null, $mismatch = 0, $count = 0, $blog_id = 0, $retry_timeout = 0)
    {
        $ret['users_equivalence_offset'] = ftell($handle);
        $ret['user_equivalence'] = $user_equivalence;
        $ret['user_mismatch_count'] = $mismatch;
        $ret['equivalence_count'] = $count;        
        fclose($handle);                
        
        do_action('prime_mover_log_processed_events', "$retry_timeout seconds time out on user equivalence" , $blog_id, 'import', __FUNCTION__, $this);
        return $ret;
    }
    
    /**
     * Clean up and return equivalence
     * @param array $ret
     * @param mixed $user_equivalence
     * @param number $mismatch
     * @return array
     */
    protected function cleanUpAndReturnEquivalence($ret = [], $user_equivalence = null, $mismatch = 0)
    {
        if (isset($ret['users_equivalence_offset'])) {
            unset($ret['users_equivalence_offset']);
        }       
        
        if ( ! isset($ret['user_mismatch_count'] ) ) {
            $ret['user_mismatch_count'] = $mismatch;
        }
        
        $ret['total_posts_update'] = (int)$this->getUserFunctions()->countTotalPosts();
        $ret['user_equivalence'] = $user_equivalence;
        
        return $ret;
    }
    
    /**
     * Update all affected meta keys using current prefix during export
     * @param array $user_meta
     * @param array $user_meta_keys
     * @param number $blog_id
     * @param array $keys_matrix
     * @return array
     */
    public function updateAllAffectedMetaKeys($user_meta = [], $user_meta_keys = [], $blog_id = 0, $keys_matrix = [])
    {
        if (! $this->getSystemAuthorization()->isUserAuthorized()) {
            return $user_meta;
        }
        
        if ( ! $blog_id || ! is_array($user_meta_keys) || ! is_array($user_meta) || empty($user_meta) || ! is_array($keys_matrix)) {
            return $user_meta;
        }
       
        foreach ($user_meta_keys as $user_meta_key) {
            $tmp = null;
            if (array_key_exists($user_meta_key, $user_meta) && isset($keys_matrix[$user_meta_key])) {
                $tmp = $user_meta[$user_meta_key];
                unset($user_meta[$user_meta_key]);
                $new_meta_key = $keys_matrix[$user_meta_key];
                $user_meta[$new_meta_key] = $tmp;
            }
        }

        return $user_meta;
    }    
}