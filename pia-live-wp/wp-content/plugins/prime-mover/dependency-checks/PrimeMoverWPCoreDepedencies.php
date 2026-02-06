<?php
/**
 *
 * This is the WP core version dependency class, purpose is to manage required WP core version dependency checks.
 *
 */
class PrimeMoverWPCoreDependencies
{
    /**
     * WP Version
     * @var string
     */
    private $wp = '4.9.5';

    /**
     * Constructor
     * @param string $minimum_version
     */
    public function __construct($minimum_version = '')
    {
        $this->wp = $minimum_version;        
    }    
   
    /**
     * Checks if WordPress version meets the minimum requirement
     * @return boolean
     * @compatible 5.6
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverRequirements::itChecksCorrectPluginRequirementsSingleSite()
     * @tested Codexonics\PrimeMoverFramework\Tests\TestPrimeMoverRequirements::itChecksCorrectPluginRequirementsMultisite()
     */
    public function wpPasses()
    {
        $notice_hook = 'admin_notices';
        if (is_multisite()) {
            $notice_hook = 'network_admin_notices';
        }
        if ($this->wpAtLeast($this->wp)) {
            return true;
        } else {
            add_action($notice_hook, array( $this, 'wpVersionNotice' ));
            return false;
        }
    }
    
    /**
     * Prime Mover cannot support sites without table prefix set
     * @return boolean
     */
    public function tablePrefixPass()
    {
        $notice_hook = 'admin_notices';
        if (is_multisite()) {
            $notice_hook = 'network_admin_notices';
        }
        
        if ($this->dBPrefixSet()) {
            return true;
        } else {
            add_action($notice_hook, array($this, 'noDbPrefixNotice'));
            return false;
        }
    }
 
    /**
     * Prime Mover only supports WordPress core installations using MariaDB and MySQL.
     * @return boolean
     */
    public function databasePass()
    {
        $notice_hook = 'admin_notices';
        if (is_multisite()) {
            $notice_hook = 'network_admin_notices';
        }
        
        if ($this->usingSQlite()) {
            add_action($notice_hook, array($this, 'unsupportedDatabaseNotice'));
            return false;
        } else {            
            return true;
        }
    }
    
     /**
     * Check for sites configured without table base prefix
     * @return boolean
     */
    private function dBPrefixSet()
    {
        global $wpdb;
        if (!is_string($wpdb->base_prefix) || '' === $wpdb->base_prefix) {
            return false;
        }        
        return true;
    }

    /**
     * Check if using SQLite database
     * Returns TRUE if using SQLite
     * @return boolean
     */
    private function usingSQlite()
    {
        global $wpdb;
        if ($wpdb instanceof WP_SQLite_DB) {
            return true;
        }
        return false;        
    }
    
    /**
     * Helper method to compare WordPress version
     * @param string $min_version
     * @return boolean
     * @compatible 5.6
     */
    private function wpAtLeast($min_version)
    {
        return version_compare(get_bloginfo('version'), $min_version, '>=');
    }

    /**
     * Display WP Version notice if non-compliant
     * @compatible 5.6
     */
    public function wpVersionNotice()
    {
        ?>
        <div class="error">
             <p>
             <?php printf( esc_html__( 'The %s plugin cannot run on WordPress versions older than %s. Please update WordPress.', 'prime-mover' ), 
                 '<strong>' . esc_html(PRIME_MOVER_PLUGIN_CODENAME) . '</strong>', $this->wp ) ?>
             </p>             
        </div>
        <?php 
    }
    
    /**
     * Display no DB prefix notice if non-compliant
     * @compatible 5.6
     */
    public function noDbPrefixNotice()
    {
        ?>
        <div class="error">
             <p>
             <?php printf( esc_html__( 'Your %s file has an empty database table prefix, which is not supported by %s plugin.', 'prime-mover' ),
                 '<code>wp-config.php</code>',
                 '<strong>' . esc_html(PRIME_MOVER_PLUGIN_CODENAME) . '</strong>'); ?>
             </p>             
        </div>
        <?php 
    }
    
    /**
     * Display unsupported database notice
     */
    public function unsupportedDatabaseNotice()
    {
        ?>
        <div class="error">
             <p>
             <?php printf(esc_html__('The %s plugin is not compatible with the SQLite database, so it cannot be activated on this site.', 'prime-mover' ),
                 '<strong>' . esc_html(PRIME_MOVER_PLUGIN_CODENAME) . '</strong>'); 
             ?>
             </p>
             
              <?php printf(esc_html__('If you are in a local environment and using %s - consider using %s that utilize MySQL or MariaDB.', 'prime-mover'),
                 '<strong>Studio by WordPress.com</strong>',
                  '<a class="prime-mover-external-link" target="_blank" href="' . CODEXONICS_COMPATIBLE_LOCAL_APPS . '">' . esc_html__('compatible apps', 'prime-mover') . '</a>')
             ?>
             </p>               
        </div>
        <?php 
    }
}
