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

use Codexonics\PrimeMoverFramework\classes\PrimeMover;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover event viewer
 * Shows all active cron / scheduled tasks by Prime Mover plugin
 *
 */
class PrimeMoverAutoBackupEventViewer
{     
    private $prime_mover;
    private $utilities;
    private $backup_menus;
    
    /**
     * Construct
     * @param PrimeMover $prime_mover
     * @param PrimeMoverBackupMenus $backup_menus
     * @param array $utilities
     */
    public function __construct(PrimeMover $prime_mover, PrimeMoverBackupMenus $backup_menus, $utilities = [])
    {
        $this->prime_mover = $prime_mover;
        $this->backup_menus = $backup_menus;
        $this->utilities = $utilities;    
    }
          
    /**
     * Get backup menus
     * @return \Codexonics\PrimeMoverFramework\menus\PrimeMoverBackupMenus
     */
    public function getBackupMenus()
    {
        return $this->backup_menus;
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
     * Get utilities
     * @return array
     */
    public function getUtilities()
    {
        return $this->utilities;
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
     * Get system functions
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMoverSystemFunctions
     */
    public function getSystemFunctions()
    {
        return $this->getPrimeMover()->getSystemFunctions();
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
        add_action('prime_mover_run_menus', [$this, 'addEventsViewerMenuPage'], 35);  
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'removeDistractionsOnEventViewerPage'], 5, 1);
        add_filter('prime_mover_inject_db_parameters', [$this, 'maybeIdentifyAutoBackupProcess'], 10, 2);
    }
 
    /**
     * Maybe identify auto backup process
     * @param array $ret
     * @param string $mode
     * @return array
     */
    public function maybeIdentifyAutoBackupProcess($ret = [], $mode = 'export')
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return $ret;
        }
        
        if ('export' !== $mode) {
            return $ret;
        }
        
        if ($this->getSystemAuthorization()->isDoingAutoBackup()) {
            $ret['autobackup_process'] = true;
        }
        
        return $ret;
    }
    
    /**
     * Enqueue scripts
     * @param string $hook
     */
    public function enqueueScripts($hook = '')
    {
        $this->getBackupMenus()->enqueueScripts($hook, false);
    }
    
    /**
     * Get menu list table instance
     * @return \Codexonics\PrimeMoverFramework\menus\PrimeMoverBackupMenuListTable
     */
    protected function getEventViewerListTableInstance()
    {
        $prime_mover = $this->getPrimeMover();
        $utilities = $this->getUtilities();
        $blog_id = $this->getBackupMenus()->getBlogIdUnderQuery(false);
        
        return new PrimeMoverEventViewerListTable($prime_mover, $utilities, $blog_id);
    }
    
    /**
     * Added menu page for backups
     */
    public function addEventViewerPageCallBack()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        $testListTable = $this->getEventViewerListTableInstance();
        $testListTable->prepare_items();       
        
        ?>
      <div class="wrap prime-mover-backup-menu-wrap">
         <h1 class="wp-heading-inline"><?php esc_html_e('Event Viewer', 'prime-mover'); ?></h1>
         
             <?php 
                $blog_id = $this->getBackupMenus()->getBlogIdUnderQuery(false);
                if (is_multisite() && $blog_id) {
             ?>
                  <p class="edit-site-actions prime-mover-edit-site-actions"><a href="<?php echo esc_url($this->getSystemFunctions()->getPublicSiteUrl($blog_id)); ?>"><?php esc_html_e('Visit Site', 'prime-mover');?></a> <span class="prime-mover-divider"> | </span> 
                      <a href="<?php echo $this->getSystemFunctions()->getCreateExportUrl($blog_id, true); ?>"><?php esc_html_e('Migration Tools', 'prime-mover'); ?></a> <span class="prime-mover-divider"> | </span>
                      <a href="<?php echo esc_url($this->getSystemFunctions()->getBackupMenuUrl($blog_id)); ?>"><?php esc_html_e('Package Manager', 'prime-mover'); ?></a> <span class="prime-mover-divider"> | </span>
                      <a href="<?php echo esc_url($this->getSystemFunctions()->getScheduledBackupSettingsUrl($blog_id)); ?>"><?php esc_html_e('Scheduled Backup Settings', 'prime-mover'); ?></a>
                 </p>
             <?php 
                }
             ?>
                          
        <div id="icon-users" class="icon32"><br/></div>     
        <div class="prime-mover-backupmenu-notes-div">        
            <p>
                <?php printf(esc_html__('This page shows all the automatic backup cron events triggered by %s', 'prime-mover'), '<strong>' . PRIME_MOVER_PLUGIN_CODENAME . '</strong>');?>.
            </p> 
        </div>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="prime_mover_backups-filter" method="get">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php esc_attr_e($_REQUEST['page']) ?>" />
            <input type="hidden" name="prime-mover-blog-id-menu" value="<?php esc_attr_e($this->getBackupMenus()->getBlogIdUnderQuery(false))?>" />
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
        </form>         
      </div>       
    <?php
    }    
    
    /**
     * Added menu page for automatic backup event viewer
     */
    public function addEventsViewerMenuPage()
    {
        $required_cap = 'manage_network_options';
        if ( ! is_multisite() ) {
            $required_cap = 'manage_options';
        }
        
        add_submenu_page( 'migration-panel-settings', esc_html__('Event Viewer', 'prime-mover'), esc_html__('Event Viewer', 'prime-mover'),
            $required_cap, 'migration-panel-backup-menu-event-viewer', [$this, 'addEventViewerPageCallBack']);
    }

    /**
     * Check if event viewer menu page
     * @param string $hook
     * @return boolean
     */
    public function isEventViewerMenuPage($hook = '')
    {
        return $this->getSystemInitialization()->isEventViewerMenuPage($hook);
    }
    
    /**
     * Checks if really event viewer menu page
     * @return boolean
     */
    public function isReallyEventViewerMenuPage()
    {
        if (!is_admin()) {
            return false;
        }
        
        if (!function_exists('get_current_screen')) {
            return false;
        }
        
        $current_screen = get_current_screen();
        if (!is_object($current_screen)) {
            return false;
        }
        
        $hook = $current_screen->id;
        return $this->isEventViewerMenuPage($hook);
    }
    
    /**
     * Remove distractions on event viewer page
     */
    public function removeDistractionsOnEventViewerPage()
    {
        if (!$this->getSystemAuthorization()->isUserAuthorized()) {
            return;
        }
        
        if ( $this->isReallyEventViewerMenuPage()) {
            remove_all_actions( 'admin_notices' );
        }
    }
}