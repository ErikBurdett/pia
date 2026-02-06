<?php
namespace Codexonics\PrimeMoverFramework\menus;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use WP_List_Table;
use Codexonics\PrimeMoverFramework\classes\PrimeMover;
use WP_Error;

/**
 * Prime Mover Event Viewer List Table
 *
 * The class aims to provide event viewer list for which user has to manage automatic backups.
 * Implementations inspired by WP Control plugin: 
 * https://wordpress.org/plugins/wp-crontrol/
 */

class PrimeMoverEventViewerListTable extends WP_List_Table 
{    
    private $prime_mover;
    private $event_identifier;
    private $backup_utilities;
    private $sys_utilities;
    private $blog_id;
    private $autobackup_cron_events;
    
    /**
     * Get Prime Mover instance
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
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
     * Get export utilities
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverExportUtilities
     */
    public function getExportUtilities()
    {
        return $this->getPrimeMover()->getSystemProcessors()->getExportUtilities();
    }
    
    /**
     * Get system utilities
     * @return array
     */
    public function getSystemUtilities()
    {
        return $this->sys_utilities;
    }
    
    /**
     * Constructor
     * @param PrimeMover $prime_mover
     * @param array $utilities
     * @param number $blog_id
     */
    public function __construct(PrimeMover $prime_mover, $utilities = [], $blog_id = 0)
    {        
        $this->prime_mover = $prime_mover; 
        $this->event_identifier = 'primeMover';
        $this->backup_utilities = $utilities['backup_utilities'];
        $this->sys_utilities = $utilities['sys_utilities'];        
        $this->blog_id = $blog_id;
        $this->autobackup_cron_events = ['primeMoverProgressIntervalEvent', 'primeMoverAutomaticBackupEvent'];
        
        parent::__construct( [
            'singular'  => 'prime_mover_event_viewer',     
            'plural'    => 'prime_mover_event_viewers',    
            'ajax'      => false        
        ] );        
    }    
    
    /**
     * Get auto backup cron events
     * @return string[]
     */
    public function getAutoBackupCronEvents()
    {
        return $this->autobackup_cron_events;
    }
    
    /**
     * Get backup utilities
     * @return array
     */
    public function getBackupUtilities()
    {
        return $this->backup_utilities;
    }
 
    /**
     * Get blog ID
     * @return number
     * @codeCoverageIgnore
     */
    public function getBlogId()
    {
        return $this->blog_id;
    }
    
    /**
     * Get event identifier
     * @return string
     */
    public function getEventIdentifier()
    {
        return $this->event_identifier;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see WP_List_Table::no_items()
     */
    public function no_items() {
        esc_html_e( 'No Prime Mover cron events found.', 'prime-mover' );
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see WP_List_Table::column_default()
     */
    public function column_default($item, $column_name)
    {
        switch($column_name){
            default:
                return print_r($item,true);
        }
    }    
      
    /**
     * Filters for events by blog ID
     * {@inheritDoc}
     * @see WP_List_Table::extra_tablenav()
     */
    protected function extra_tablenav( $which )
    {
        if ('bottom' === $which) {
            return;
        }
       
        $queried_id = $this->getBlogId();        
        $label = esc_html__('Enter blog ID for filter events', 'prime-mover');
        $this->getSystemUtilities()->displayMultisiteBlogIdSelectors([], $queried_id, false, $label);
    }
    
    /**********************
     * COLUMN DEFINITIONS*
     * ********************
     */  
    public function column_blog_id($item)
    {
        return $item['blog_id'];
    } 
    
    public function column_configure($item)
    {
        $disabled = '';
        if ("#" === $item['configure']) {
            $disabled = 'disabled';
        }
        $class = "button prime-mover-menu-button prime-mover-configure-button-events {$disabled}";
        return '<a class="' . $class . '" href="' . esc_url($item['configure']) . '" title="' .
            esc_attr__('Configure this event', 'prime-mover') . '">' . esc_html__('Edit event', 'prime-mover') . '</a>';
    }
    
    public function column_event_hook_name($item)
    {        
        return $item['event_hook_name'];
    }    

    public function column_backup_option($item)
    {
        return $item['backup_option'];
    } 
        
    public function column_description($item)
    {
        return $item['description'];
    }    
    
    /**
     * @param array $event
     * @return string
     */
    public function column_next_run($event = [])
    {        
        $date_local_format = 'Y-m-d H:i:s';
        $offset_site = get_date_from_gmt('now', 'P');
        $offset_event = get_date_from_gmt(gmdate('Y-m-d H:i:s', $event['timestamp']), 'P');
        
        if ($offset_site !== $offset_event) {
            $date_local_format .= ' P';
        }
        
        $date_utc = gmdate('c', $event['timestamp']);
        $date_local = get_date_from_gmt(gmdate('Y-m-d H:i:s', $event['timestamp']), $date_local_format);
        
        $time = sprintf(
            '<time datetime="%1$s">%2$s</time>',
            esc_attr( $date_utc ),
            esc_html( $date_local )
            );
        
        $until = $event['timestamp'] - time();
        
        return sprintf(
            '%s<br>%s',
            $time,
            esc_html($this->interval($until))
            );        
    }

    /**
     * Checks interval
     * @return string
     */
    private function interval($since) 
    {
        $chunks = [            
            [YEAR_IN_SECONDS, _n_noop( '%s year', '%s years', 'prime-mover')],            
            [MONTH_IN_SECONDS, _n_noop( '%s month', '%s months', 'prime-mover')],            
            [WEEK_IN_SECONDS, _n_noop( '%s week', '%s weeks', 'prime-mover')],            
            [DAY_IN_SECONDS, _n_noop( '%s day', '%s days', 'prime-mover')],            
            [HOUR_IN_SECONDS, _n_noop( '%s hour', '%s hours', 'prime-mover')],            
            [MINUTE_IN_SECONDS, _n_noop( '%s minute', '%s minutes', 'prime-mover')],            
            [1, _n_noop( '%s second', '%s seconds', 'prime-mover')],
        ];
        
        if ( $since <= 0 ) {
            return __( 'now', 'prime-mover' );
        }
        
        foreach (array_keys($chunks) as $i) {
            $seconds = $chunks[ $i ][0];
            $name = $chunks[ $i ][1];
            $count = (int) floor( $since / $seconds);
            if ($count) {
                break;
            }
        }
        
        $output = sprintf(translate_nooped_plural( $name, $count, 'prime-mover'), $count);
        if ( $i + 1 < count($chunks)) {
            $seconds2 = $chunks[ $i + 1 ][0];
            $name2 = $chunks[ $i + 1 ][1];
            $count2= (int) floor(($since -($seconds * $count)) / $seconds2);
            if ($count2) {              
                $output .= ' ' . sprintf(translate_nooped_plural($name2, $count2, 'prime-mover'), $count2);
            }
        }
        
        return $output;
    }
    
    /**
     * Check if cron fires too frequently
     * @param array $event
     * @return boolean|mixed
     */
    protected function isTooFrequent($event = []) 
    {
        $schedules = $this->getCronSchedules();
        
        if (!isset($schedules[ $event['schedule']])) {
            return false;
        }
        
        return $schedules[$event['schedule']]['istoofrequent'];
    }
    
    /**
     * Get recurrence column
     * @param array $event
     * @return string
     */
    public function column_recurrence($event = []) 
    {        
        if ($event['schedule']) {
            $schedule_name = $this->getScheduleName($event);
            if (is_wp_error($schedule_name)) {
                return sprintf(
                    '<span class="status-crontrol-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
                    esc_html($schedule_name->get_error_message())
                    );
            } elseif ($this->isTooFrequent($event)) {
                return sprintf(
                    '%1$s<span class="status-crontrol-warning"><br><span class="dashicons dashicons-warning" aria-hidden="true"></span> %2$s</span>',
                    esc_html($schedule_name),
                    sprintf(                        
                        esc_html__('This interval is less than the %1$s constant which is set to %2$s seconds. Events that use it may not run on time.', 'prime-mover'),
                        '<code>WP_CRON_LOCK_TIMEOUT</code>',
                        intval(WP_CRON_LOCK_TIMEOUT)
                        )
                    );
            } else {
                return esc_html($schedule_name);
            }
        } else {
            return esc_html__('Non-repeating', 'prime-mover');
        }
    }    
      
    /**
     * 
     * {@inheritDoc}
     * @see WP_List_Table::get_columns()
     */
    public function get_columns()
    {
        $columns = [       
            'configure' => esc_html__('Configure', 'prime-mover'),
            'blog_id' => esc_html__('Blog ID', 'prime-mover'),           
            'event_hook_name' => esc_html__('Events name', 'prime-mover'),             
            'description' => esc_html__('Description', 'prime-mover'),            
            'next_run' => sprintf(
                esc_html__( 'Next Run (%s)', 'prime-mover' ), $this->getUtcOffset()),
            'recurrence' => esc_html__('Recurrence', 'prime-mover'),
            'backup_option' => esc_html__('Backup info', 'prime-mover')
        ];
        
        return $columns;
    } 
        
    /**
     * {@inheritDoc}
     * @see WP_List_Table::get_sortable_columns()
     */
    public function get_sortable_columns() 
    {
        $sortable_columns = [            
            'blog_id' => ['blog_id',false],
            'event_hook_name' => ['event_hook_name',false],
            'backup_option' => ['backup_option',false],
            'next_run' => ['next_run',false],
            'recurrence' => ['recurrence',false]
        ];
        
        return $sortable_columns;
    }   
 
    /**
     * Returns the schedule display name for a given event.
     *
     * @return string|WP_Error The interval display name, or a WP_Error object if no such schedule exists.
     */
    function getScheduleName($event) 
    {
        $schedules = $this->getCronSchedules();
        
        if (isset( $schedules[$event['schedule']])) {
            return isset($schedules[ $event['schedule'] ]['display'] ) ? $schedules[ $event['schedule']]['display'] : $schedules[$event['schedule']]['name'];
        }
        
        return new WP_Error( 'unknown_schedule', sprintf(
            esc_html__( 'Unknown (%s)', 'prime-mover' ),
            $event['schedule']
            ));
    }
    
    /**
     * Get cron schedules
     * @return array
     */
    protected function getCronSchedules() 
    {
        $schedules = wp_get_schedules();
        uasort( $schedules, function( array $a, array $b ) {
            return ($a['interval'] - $b['interval']);
        } );
            
        array_walk( $schedules, function(array &$schedule, $name) {
            $schedule['name'] = $name;
            $schedule['istoofrequent'] = ( $schedule['interval'] < WP_CRON_LOCK_TIMEOUT );
        } );              
               
        return $schedules;
    }
    
    /**
     * Reorder data
     * @return number
     */
    protected function usortReorder($a,$b)
    {
        $sort_params = $this->getSortRequestParameters();
        
        $orderby = (!empty($sort_params['orderby'])) ? $sort_params['orderby'] : 'blog_id'; 
        $order = (!empty($sort_params['order'])) ? $sort_params['order'] : 'desc'; 
        
        if (in_array($orderby, ['event_hook_name', 'blog_id', 'backup_option'])) {
            $result = strcmp($a[$orderby], $b[$orderby]);
            
            return ($order==='asc') ? $result : -$result; 
            
        } elseif ('recurrence' === $orderby) {
            
            if ('asc' === $order) {                
                
                $first_param = $this->nullCoalescingBackwardCompat($a['interval'], 0);
                $second_param = $this->nullCoalescingBackwardCompat($b['interval'], 0);                
                $result = $this->spaceShipBackwardCompat($first_param, $second_param);
                
            } else {
                
                $first_param = $this->nullCoalescingBackwardCompat($b['interval'], 0);
                $second_param = $this->nullCoalescingBackwardCompat($a['interval'], 0);                 
                $result = $this->spaceShipBackwardCompat($first_param, $second_param);
            }
            
            return $result;
            
        } elseif ('next_run' === $orderby) {
            
            if ('asc' === $order) {
                $result = $this->spaceShipBackwardCompat($a['timestamp'], $b['timestamp']);
            } else {
                $result = $this->spaceShipBackwardCompat($b['timestamp'], $a['timestamp']);
            }
            
            return $result;
        }
    }
 
    /**
     * Reorder data
     * @return number
     */
    protected function usortReorderDefault($a,$b)
    {        
        $orderby = 'event_hook_name';
        $order = 'asc';

        $result = strcmp($a[$orderby], $b[$orderby]);            
        return ($order === 'asc') ? $result : -$result;
    }
    
    /**
     * Null coalescing backward compatible for PHP 5.6
     * Necessary to support very old sites using these outdated versions to migrate to latest PHP versions.
     */
    protected function nullCoalescingBackwardCompat($var, $default)
    {        
        $result = isset($var) ? $var : $default;
        return $result;
    }
    
    /**
     * Spaceship operator backard compatible PHP 5.6
     * Necessary to support very old sites using these outdated versions to migrate to latest PHP versions.
     */
    protected function spaceShipBackwardCompat($first_param, $second_param)
    {
        if ($first_param == $second_param) {
            return 0;
        }
        return ($first_param < $second_param) ? -1 : 1;
    }
    
    /**
     * Sort request parameters
     * @return mixed|NULL|array
     */
    private function getSortRequestParameters()
    {        
        return $this->getSystemInitialization()->getUserInput('get',
            [
                'order' => $this->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
                'orderby' => $this->getSystemInitialization()->getPrimeMoverSanitizeStringFilter(),
                'page' => $this->getSystemInitialization()->getPrimeMoverSanitizeStringFilter()
            ], 'prime_mover_backup_menu_sort');       
    }   
    
    /**
     * Get events data
     * @return array
     */
    protected function getEventsData() {
        $crons = $this->getCoreCronArray();
        $events = [];
        
        if (empty($crons)) {
            return [];
        }
        
        $filtered_blog_id = $this->getBlogId();
        $filtered_blog_id = (int)$filtered_blog_id;
        $export_options = $this->getExportUtilities()->getExportModes();
        $settings = apply_filters('prime_mover_get_all_settings', []);
        $autobackup_disabled = $this->getSystemUtilities()->maybeDisableAutoBackup();
        
        foreach ($crons as $time => $cron) {
            foreach ($cron as $hook => $dings) {
                foreach ($dings as $sig => $data) {
                    $valid = false;
                    $blog_id = 0;
                    $cron_mode = '';
                    if (substr($hook, 0, strlen($this->getEventIdentifier())) === $this->getEventIdentifier() && $hook !== 'primeMoverDeleteSymlinkEvent') {
                        list($valid, $events) = $this->defineEvent($hook, $time, $sig, $data, $valid, $events);
                    }
                    
                    $cron_details = [];
                    if ($valid) {
                        $cron_details = $this->getBackupUtilities()->getDetailsromCronAction($hook);                        
                    }
                    
                    if (!empty($cron_details[0]) && isset($cron_details[1])) {
                        $blog_id = $cron_details[1];
                        $blog_id = (int)$blog_id;
                        $cron_mode = $cron_details[0];
                    }
                    
                    $signature_key = "$hook-$sig-$time";
                    $events = $this->injectExtendableParameters($blog_id, $signature_key, $events, $cron_mode, $hook, $export_options, $valid, $settings);                                   
                    $events = $this->maybeUnSetIfFilteringActive($events, $signature_key, $filtered_blog_id, $blog_id, $hook, $autobackup_disabled);                                    
                }
            }
        }
        
        return $events;
    }
    
    /**
     * Define event
     * @param string $hook
     * @param number $time
     * @param string $sig
     * @param array $data
     * @param boolean $valid
     * @param array $events
     * @return array
     */
    protected function defineEvent($hook = '', $time = 0, $sig = '', $data = [], $valid = false, $events = [])
    {
        $event = [
            'event_hook_name' => $hook,
            'timestamp' => $time,
            'sig' => $sig,
            'args' => $data['args'],
            'schedule' => $data['schedule'],
            'interval' => isset($data['interval']) ? $data['interval'] : null,
        ];
        
        $schedule_name = $this->getScheduleName($event);
        if (!is_wp_error($schedule_name)) {
            $valid = true;
            $events["$hook-$sig-$time"] = $event;
        } 
        
        return [$valid, $events];        
    }
    
    /**
     * Inject extendable parameters
     * @param number $blog_id
     * @param string $signature_key
     * @param array $events
     * @param string $cron_mode
     * @param string $hook
     * @param array $export_options
     * @param boolean $valid
     * @param array $settings
     * @return array
     */
    protected function injectExtendableParameters($blog_id = 0, $signature_key = '', $events = [], $cron_mode = '', $hook = '', $export_options = [], $valid = false, $settings = [])
    {
        $backup_option = '';
        if (!$valid) {
            return $events;
        }
        
        if ($blog_id) {
            $events[$signature_key]['blog_id'] = $blog_id;
            $events[$signature_key]['configure'] = $this->getSystemFunctions()->getScheduledBackupSettingsUrl($blog_id);
        }
        
        if ('primeMoverAutomaticBackupEvent' === $cron_mode && $blog_id) {
            $events[$signature_key]['description'] = sprintf(esc_html__('Automatic backup event scheduled for blog ID: %d'), $blog_id);            
            $backup_option = $this->getSpecificSettingOfSite($blog_id, "automatic_backup_export_options", $settings);
        }
        
        if ('primeMoverProgressIntervalEvent' === $cron_mode && $blog_id) {
            $events[$signature_key]['description'] = sprintf(esc_html__('Event for checking pending backups to continue for blog ID: %d'), $blog_id);
        }
        
        if ('primeMoverDeleteSymlinkEvent' === $hook) {
            $events[$signature_key]['description'] = esc_html__('Event for cleaning up outdated download packages symlinks', 'prime-mover');
            $events[$signature_key]['blog_id'] = 1;
            $events[$signature_key]['configure'] = "#";
        }
        
        if ($backup_option && isset($export_options[$backup_option])) {
            $events[$signature_key]['backup_option'] = $export_options[$backup_option];
        } else {
            $events[$signature_key]['backup_option'] = $this->getAutoBackupProgress($blog_id);
        }         
        
        return $events;
    }
    
    /**
     * Get auto backup progress
     * @param number $blog_id
     * @return string|mixed|string
     */
    protected function getAutoBackupProgress($blog_id = 0)
    {
        $ret = apply_filters('prime_mover_get_export_progress', [], $blog_id, true);        
        $progress = esc_html__('Backup queued', 'prime-mover');
        
        if (!is_array($ret)) {
            return $progress;
        }
        
        if (!isset($ret['autobackup_process'])) {
            return $progress;
        }
      
        if (empty($ret['next_method'])) {
            return $progress;
        }
        
        $export_methods = $this->getSystemInitialization()->getPrimeMoverExportMethods();
        $next_method = $ret['next_method'];
        
        $count = count($export_methods);
        $key = array_search($next_method, $export_methods);
        if (false === $key) {
            return $progress;
        }
        
        $position = $key;
        $percent = ($position / $count) * 100;
        $percent = round($percent, 0);
        
        $progress = $percent . '% ' . esc_html__('completed', 'prime_mover');;
        return $progress;        
    }
    
    /**
     * Get specific setting of site
     * @param number $blog_id
     * @param string $setting
     * @param array $settings
     * @return string
     */
    protected function getSpecificSettingOfSite($blog_id = 0, $setting = '', $settings = [])
    {
        if (!$blog_id || !$setting || empty($settings)) {
            return '';
        }
        
        if (empty($settings['prime_mover_sites'][$blog_id][$setting])) {
            return '';
        }
        
        return $settings['prime_mover_sites'][$blog_id][$setting];
    }
    
    /**
     * Maybe unset some events
     * @param array $events
     * @param string $signature_key
     * @param number $filtered_blog_id
     * @param number $blog_id
     * @param string $hook
     * @param boolean $autobackup_disabled
     * @return array
     */
    protected function maybeUnSetIfFilteringActive($events = [], $signature_key = '', $filtered_blog_id = 0, $blog_id = 0, $hook = '', $autobackup_disabled = false)
    {
        $unset = false;
        if ($filtered_blog_id && $blog_id && $signature_key && $blog_id !== $filtered_blog_id) {
            $unset = true;
        } 
        
        if (!$unset && $filtered_blog_id && 'primeMoverDeleteSymlinkEvent' === $hook && 1 !== $filtered_blog_id) {
            $unset = true;
        }       
        
        $clear_schedule = false;
        $cron_details = [];
        $cron_mode = '';
        $subsite_autobackup_status = '';
        
        if ($hook && !$unset) {
            $cron_details = $this->getBackupUtilities()->getDetailsromCronAction($hook);
        }        
        
        if (!empty($cron_details[0]) && !$unset) {
            $cron_mode = $cron_details[0];
        }

        if (!$unset && $cron_mode && in_array($cron_mode, $this->getAutoBackupCronEvents()) && is_multisite()) {
            $subsite_autobackup_status = apply_filters('prime_mover_get_setting', '', 'automatic_backup_subsite_enabled', false, '', true, $blog_id);           
        }
       
        if (is_multisite()) {
            $autobackup_disabled = true;
            if ('true' === $subsite_autobackup_status) {
                $autobackup_disabled = false;
            }
        }        
        
        if (!$unset && $autobackup_disabled && $cron_mode && in_array($cron_mode, $this->getAutoBackupCronEvents())) {
            $unset = true;
            $clear_schedule = true;            
        }       
        
        if ($unset) {
            unset($events[$signature_key]);
        }
        
        if ($clear_schedule) {
            wp_clear_scheduled_hook($hook);
        }
        
        return $events;
    }

    /**
     * Fetches the list of cron events from WordPress core.
     */
    protected function getCoreCronArray() 
    {
        $crons = _get_cron_array();        
        if ( empty($crons)) {
            $crons = [];
        }
        
        return $crons;
    }
  
    /**
     * Get UTC offset
     * @return string
     */
    protected function getUtcOffset() 
    {
        $offset = get_option('gmt_offset', 0);
        
        if (empty($offset)) {
            return 'UTC';
        }
        
        if (0 <= $offset) {
            $formatted_offset = '+' . (string) $offset;
        } else {
            $formatted_offset = (string) $offset;
        }
        $formatted_offset = str_replace(
            ['.25', '.5', '.75'],
            [':15', ':30', ':45'],
            $formatted_offset
            );
        return 'UTC' . $formatted_offset;
    }
    
    /**
     * Prepare items
     * {@inheritDoc}
     * @see WP_List_Table::prepare_items()
     */
    public function prepare_items() 
    {     
        $per_page = PRIME_MOVER_EVENTS_VIEWER_LIST_TABLE_ITEM;
        $per_page = (int)$per_page; 
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
      
        $this->_column_headers = [$columns, $hidden, $sortable];
        $data = $this->getEventsData();  
        
        $sort_params = $this->getSortRequestParameters();
        if (!empty($sort_params['orderby']) && !empty($sort_params['order'])) {
            usort($data, [$this, 'usortReorder']);
        } else {
            usort($data, [$this, 'usortReorderDefault']);
        }
        
        $current_page = $this->get_pagenum();
        $total_items = count($data);        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);        
        
        $this->items = $data;        
        $this->set_pagination_args([
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)   
        ]);
    }
}