<?php
namespace Codexonics\PrimeMoverFramework\extensions;

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
use Codexonics\PrimeMoverFramework\utilities\PrimeMoverAutoBackupSetting;
use wpdb;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover AutoUser Adjustment Compatibility Class
 * Helper class for interacting with third party plugins having custom user ID columns
 *
 */
class PrimeMoverAutoUserAdjustment
{     
    
    private $prime_mover;
    private $force_blogid_one;
    private $autobackup_setting;
    
    /**
     * Constructor
     * @param PrimeMover $prime_mover
     * @param PrimeMoverAutoBackupSetting $autobackup_setting
     * @param array $utilities
     */
    public function __construct(PrimeMover $prime_mover, PrimeMoverAutoBackupSetting $autobackup_setting, $utilities = [])
    {
        $this->prime_mover = $prime_mover;
        $this->force_blogid_one = [
            'bb_user_reactions'
        ];
        
        $this->autobackup_setting = $autobackup_setting;
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
     * Get known specialized custom tables to be forced to Blog ID one
     * @return string[]
     */
    public function getTableForceBlogIdOne()
    {
        return $this->force_blogid_one;
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
     * Initialize hooks
     */
    public function initHooks()
    {      
        add_filter('prime_mover_filter_export_footprint', [$this, 'maybeAddAutoUserAdjustMentExportFootPrint'], 500, 3);       
        add_action('prime_mover_before_thirdparty_data_processing', [$this, 'maybeAddAutoUserAdjustmentHooks'], 0, 2);  
        
        add_action('wp_ajax_prime_mover_save_non_user_adjustment', [$this,'saveNonUserIdColAutoAdjustment']);         
        add_filter('prime_mover_custom_user_id_col', [$this, 'injectCustomUserIDAdjustment'], 10, 2);
    }   
  
    /**
     * Inject csutom user ID adjustment settings during export
     * @param array $return
     * @param number $blog_id
     * @return array
     */
    public function injectCustomUserIDAdjustment($return = [], $blog_id = 0)
    {
        if (!$blog_id) {
            return $return;
        }

        $setting = $this->getAutoBackupSetting()->getPrimeMoverSettings()->getSetting('non_user_column_id_adjustment', false, '', false, $blog_id, true);
        if (!$setting || !is_array($setting) || empty($setting)) {
            return $return;
        }
        
        return $setting;
    }
    
    /**
     * Parse get blog ID and posted value
     * @param mixed $non_user_id_col
     * @return array
     */
    protected function getBlogIDAndValue($non_user_id_col = null)
    {
        $blog_id = 0;
        if (is_array($non_user_id_col)) {
            $blog_id = key($non_user_id_col);
        }
        
        if ($blog_id && isset($non_user_id_col[$blog_id])) {
            $value = $non_user_id_col[$blog_id];
        }
        
        if (is_string($value)) {
            $value = preg_split('/\r\n|\r|\n/', $value);
        }
        
        $value = array_filter($value);        
        return [$blog_id, $value];
    }
    
    /**
     * Save non-user ID column auto adjustment
     * This applies to both single-site and multisites
     */
    public function saveNonUserIdColAutoAdjustment()
    {
        $response = [];
        $settings_key = 'non_user_column_id_adjustment';
        
        $non_user_id_col = $this->getAutoBackupSetting()->getPrimeMoverSettings()->prepareSettings($response, 'non_user_column_id_adjustment',
            'prime_mover_save_non_user_column_adjustment_nonce', false, FILTER_DEFAULT); 
        
        list($blog_id, $value) = $this->getBlogIDAndValue($non_user_id_col);       
        if (empty($value)) {
            $this->getAutoBackupSetting()->getPrimeMoverSettings()->saveSetting($settings_key, [], false, $blog_id, true);
            $message = esc_html__('Saving non-user column ID succeeds.', 'prime-mover');
            $this->bailOutMessage($response, false, $message, true);           
        }
        
        if (!is_array($value)) {
            $this->bailOutMessage($response);
        }
        
        $validated = $this->processTableLevelValidation($blog_id, $value, $response);        
        $this->getAutoBackupSetting()->getPrimeMoverSettings()->saveSetting($settings_key, $validated, false, $blog_id, true);
        $message = esc_html__('Saving non-user column ID succeeds.', 'prime-mover');
        $this->bailOutMessage($response, false, $message, true);
    }
    
    /**
     * Parse table name and columns
     * @param wpdb $wpdb
     * @param string $entry
     * @param array $response
     * @param number $blog_id
     * @return string[]|mixed[]
     */
    protected function parseTableNameAndColumns(wpdb $wpdb, $entry = '', $response = [], $blog_id = 0)
    {
        $pieces = [];
        if ($entry) {
            $pieces = explode(":", $entry);
        }
        
        if (2 !== count($pieces)) {
            $this->bailOutMessage($response);
        }
        
        if (!isset($pieces[0])) {
            $this->bailOutMessage($response);
        }
        
        $table = $pieces[0];
        $table = trim($table);
        $table_exists = false;
        
        $sql = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table));
        if ($wpdb->get_var($sql)) {
            $table_exists = true;
        }
        
        if (!$table_exists) {
            $message = sprintf(esc_html__('%s: The settings have not been saved. The database table name %s does not exist. Please ensure that the table name is correct.', 'prime-mover'),
                '<strong>' . esc_html__('Error', 'prime-mover') . '</strong>',
                "<code>{$table}</code>");
            $this->bailOutMessage($response, true, $message, false);
        }
        
        if (!$this->isTableBelongsToThisSite($blog_id, $table)) {
            $message = sprintf(esc_html__('%s: The settings have not been saved. The database table name %s does not belong to %s of this multisite.', 'prime-mover'),
                '<strong>' . esc_html__('Error', 'prime-mover') . '</strong>',
                "<code>{$table}</code>",
                '<strong>' . esc_html__('blog ID: ', 'prime-mover') . $blog_id . '</strong>'
            );
            $this->bailOutMessage($response, true, $message, false);
        }
        
        if (!isset($pieces[1])) {
            $this->bailOutMessage($response, true);
        }
        
        $col = $pieces[1];
        if (!$col) {
            $this->bailOutMessage($response, true);
        }
        
        return [$table, $col];
    }
    
    /**
     * Check if given table belongs to a given site in multisite
     * @param number $blog_id
     * @param string $given_table
     * @return boolean
     */
    protected function isTableBelongsToThisSite($blog_id = 0, $given_table = '')
    {
        if (!is_multisite()) {
            return true;
        }
        
        $tables = $this->getPrimeMover()->getSystemFunctions()->getTablesforReplacement($blog_id);        
        return in_array($given_table, $tables);        
    }
    
    /**
     * Process table level validation
     * @param number $blog_id
     * @param array $value
     * @param array $response
     * @return array|string[][]|array[]
     */
    protected function processTableLevelValidation($blog_id = 0, $value = [], $response = [])
    {
        $this->getPrimeMover()->getSystemFunctions()->switchToBlog($blog_id);
        $wpdb = $this->getSystemInitialization()->getWpdB();
        $validated = [];
        foreach ($value as $entry) {            
            list($table, $col) = $this->parseTableNameAndColumns($wpdb, $entry, $response, $blog_id);
            $col_pieces = explode(",", $col);
            $col_pieces = array_map('trim', $col_pieces);
            $user_columns = esc_sql($col_pieces);
            $user_columns_in_string = "'" . implode("','", $user_columns) . "'";
            
            $sql = "SHOW COLUMNS FROM `{$table}` WHERE Field IN ($user_columns_in_string)";
            $res = $wpdb->get_results($sql, ARRAY_A);
            $column_label = esc_html__('column', 'prime-mover');
            if (count($col_pieces) > 1) {
                $column_label = esc_html__('columns', 'prime-mover');
            }
            
            if (!is_array($res) || empty($res)) {
                $message = sprintf(esc_html__('%s: The settings have not been saved. The %s %s do not exist in the %s database table. Please ensure that the targeted column names are correct and it exists.', 'prime-mover'),
                    "<strong>" . esc_html__('Error', 'prime-mover') . '</strong>',
                    "<code>{$user_columns_in_string}</code>",
                    $column_label,
                    "<code>{$table}</code>");
                    $this->bailOutMessage($response, true, $message, false);
            }
            
            $fields = wp_list_pluck($res, 'Type', 'Field');
            if (!is_array($fields)) {
                $this->bailOutMessage($response, true);
            }
            
            list($invalid, $validated) = $this->validateColumnsData($table, $fields, $col_pieces, $validated);
            if (!empty($invalid)) {
                $html_error = sprintf(esc_html__('%s: Settings not saved because of the following errors:', 'prime-mover'), '<strong>' . esc_html__('Error', 'prime-mover') . '</strong>');
                $html_error .= '<ol>';
                
                foreach ($invalid as $error) {
                    $html_error .= '<li>' . $error . '</li>';
                }
                $html_error .= '</ol>';                
                $this->bailOutMessage($response, true, $html_error, false);
            }
        }
        
        $this->getPrimeMover()->getSystemFunctions()->restoreCurrentBlog();
        return $validated;
    }
    
    /**
     * Validate column data
     * @param string $table
     * @param array $fields
     * @param array $col_pieces
     * @param array $validated
     * @return array
     */
    protected function validateColumnsData($table = '', $fields = [], $col_pieces = [], $validated = [])
    {
        $invalid = [];
        $validated[$table] = [];
        $existing_col = array_keys($fields);
        foreach ($col_pieces as $given) {
            $field_type = '';
            if (isset($fields[$given])) {
                $field_type = $fields[$given];
            }
            
            if (!in_array($given, $existing_col)) {
                $invalid[] = sprintf(esc_html__('The %s column does not exist in %s database table. Targeted columns should exist in database table.', 'prime-mover'), "<code>{$given}</code>", "<code>{$table}</code>");
                continue;
            }
            
            if (!$field_type || !$this->getPrimeMover()->getSystemFunctions()->isNumericKey($field_type, $this->getSystemInitialization()->getIntTypes(), false)) {
                $invalid[] = sprintf(esc_html__('The %s column in %s database table is not using integer type. Please verify if this is the correct column name.', 'prime-mover'), "<code>{$given}</code>", "<code>{$table}</code>");
                continue;
            }
            
            if ('user_id' === $given) {
                $invalid[] = sprintf(esc_html__('The %s column in %s database table is already handled automatically - no need to add this in settings.', 'prime-mover'), "<code>{$given}</code>", "<code>{$table}</code>");
                continue;
            }
            
            $validated[$table][] = $given;
        }
        
        return [$invalid, $validated];
    }
    
    /**
     * Bail out message
     * @param array $response
     * @param boolean $restore_blog
     * @param string $message
     * @param boolean $status
     */
    protected function bailOutMessage($response = [], $restore_blog = false, $message = '', $status = false)
    {
        if (!$message) {
            $message = sprintf(esc_html__('%s: Settings not saved - the settings format is incorrect. It must use the %s format, and the table and column must exist in the database.', 'prime-mover'),
                '<strong>' . esc_html__('Error', 'prime-mover') . '</strong>',
                '<code>TABLENAME : COLUMN_NAME</code>');
        }
        
        $this->getAutoBackupSetting()->getPrimeMoverSettings()->returnToAjaxResponse($response, ['status' => $status, 'message' => $message]);        
        if ($restore_blog) {
            $this->getPrimeMover()->getSystemFunctions()->restoreCurrentBlog();
        }
    }
    
    /**
     * Maybe add auto user adjustment hooks
     * @param array $ret
     * @param number $blogid_to_import
     */
    public function maybeAddAutoUserAdjustmentHooks($ret = [], $blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }

        if (empty($ret['imported_package_footprint']['auto_user_adjustment'])) {
            return;
        }        
        
        $auto_user_adj = $ret['imported_package_footprint']['auto_user_adjustment'];
        $i = 100000;    
        $defaults = $this->getHashOfDefaultUserAdjustments();
        
        foreach ($auto_user_adj as $v) {
            if (!is_array($v)) {
                continue;
            }
            
            list($table, $primary_index, $column, $unique) = $this->getRequiredParametersToHook($v);
            if (!$table || !$primary_index || !$column) {
                continue;
            }                       
            
            $func_signature = $this->generateFunctionSignature($table, $primary_index, $column);               
            if (in_array($func_signature, $defaults)) {
                continue;
            }
            
            add_filter('prime_mover_do_process_thirdparty_data', function($ret = [], $blogid_to_import = 0, $start_time = 0) use ($i, $table, $column, $primary_index, $func_signature, $unique) {                
                if ($this->userDoesNotNeedAdjustment($ret, $blogid_to_import)) {
                    return $ret;
                }               
                
                if (!empty($ret['3rdparty_current_function']) && $func_signature !== $ret['3rdparty_current_function']) {
                    return $ret;
                }            
                
                $ret['3rdparty_current_function'] = $func_signature;                
                $leftoff_identifier = "3rdparty_{$table}_leftoff";                
                $column_strings = "{$primary_index}, {$column}";
                
                $update_variable = "3rdparty_{$table}_updated";                
                $progress_identifier = "{$table} table";
                $auto_user_adj_args = [
                    'table' => $table,
                    'primary_index' => $primary_index,
                    'column' => $column
                    ];
                
                $last_processor = apply_filters('prime_mover_is_thirdparty_lastprocessor', false, $this,  $func_signature, $ret, $blogid_to_import, $auto_user_adj_args);
                $handle_unique_constraint = '';
                if ('yes' === $unique) {
                    $handle_unique_constraint = $column;
                }
                
                if (in_array($table, $this->getTableForceBlogIdOne())) {
                    $blogid_to_import = 1;
                }
                
                return apply_filters('prime_mover_process_userid_adjustment_db', $ret, $table, $blogid_to_import, $leftoff_identifier, $primary_index, $column_strings,
                    $update_variable, $progress_identifier, $start_time, $last_processor, $handle_unique_constraint);
                
            }, $i, 3);
                
            $i++;
        }        
    }

    /**
     * Get required parameters to hook
     * @param array $v
     * @return string[]
     */
    protected function getRequiredParametersToHook($v = [])
    {
        $table = '';
        $primary_key = '';
        $column = '';
        $unique = 'no';
        
        foreach($v as $k => $params) {
            $table = $k;
            if (!is_array($params)) {
                continue;
            }
            
            if (isset($params['primary'])) {
                $primary_key = $params['primary'];
            }
            
            if (isset($params['column'])) {
                $column = $params['column'];
            }
            
            if (isset($params['unique'])) {
                $unique = $params['unique'];
            }
        }
        
        return [$table, $primary_key, $column, $unique];
    }
    
    /**
     * Generate unique identifiable function signature
     * @param string $table
     * @param string $primary_key
     * @param string $col
     * @return string
     */
    protected function generateFunctionSignature($table = '', $primary_key = '', $col = '')
    {
        $string = $table . $primary_key . $col;        
        $hash_algo = $this->getSystemInitialization()->getFastHashingAlgo();
        
        return hash($hash_algo, $string);
    }
    
    /**
     * Checks if user does not need adjustment
     * @param array $ret
     * @param number $blogid_to_import
     * @return boolean
     */
    protected function userDoesNotNeedAdjustment($ret = [], $blogid_to_import = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return true;
        }
        
        if (!isset($ret['user_equivalence']) || !$blogid_to_import) {
            return true;
        }
        
        $mismatch_count = 0;
        if (isset($ret['user_mismatch_count'])) {
            $mismatch_count = $ret['user_mismatch_count'];
        }
        
        if (!$mismatch_count) {
            do_action('prime_mover_log_processed_events', "User equivalence check enabled - but post mismatch count is zero, skipping third party processing user update.", $blogid_to_import, 'import', __FUNCTION__, $this);
            return true;
        }
        
        return false;
    }
    
    /**
     * Maybe add automatic user adjustment to footprint config.
     * @param array $export_system_footprint
     * @param array $ret
     * @param number $blogid_to_export
     * @return array
     */
    public function maybeAddAutoUserAdjustMentExportFootPrint($export_system_footprint = [], $ret = [], $blogid_to_export = 0)
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $export_system_footprint;
        }
        
        if (!is_array($export_system_footprint) || !is_array($ret)) {
            return $export_system_footprint;
        }
        
        if (!isset($ret['autouser_id_adjust'])) {
            return $export_system_footprint;
        }
        
        if (!is_array($ret['autouser_id_adjust']) || empty($ret['autouser_id_adjust'])) {
            return $export_system_footprint;
        }
        
        $export_system_footprint['auto_user_adjustment'] = $ret['autouser_id_adjust'];          
        return $export_system_footprint;
    }
    
    /**
     * Get hash of default user adjustments
     * @return array
     */
    protected function getHashOfDefaultUserAdjustments()
    {
        $default = primeMoverDefaultUserAdjustments();
        $hashed = array_map([$this, 'implodeValues'], array_values($default));
        
        return array_unique($hashed);        
    }
    
    /**
     * Implode and hash values
     * @param array $v
     * @return string
     */
    protected function implodeValues($v = [])
    {
        $hash_algo = $this->getSystemInitialization()->getFastHashingAlgo();        
        return hash($hash_algo, implode($v));
    }    
}
