<?php
namespace Codexonics\PrimeMoverFramework\classes;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Freemius;
use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The Prime Mover System Authorization Class
 *
 * The Prime Mover System Authorization provides authentication layer for the class methods and usage.
 */
class PrimeMoverSystemAuthorization
{
    
    /** @var boolean is_authorized */
    private $is_authorized;
    
    /** @var integer Prime Mover user ID */
    private $prime_mover_user_id;

    /** @var Freemius object $freemius */    
    private $freemius;
    
    /**
     * Constructor
     * @param WP_User $user
     * @param Freemius $freemius
     */
    public function __construct(WP_User $user, Freemius $freemius)
    {
        $this->is_authorized = $this->checksIfUserIsAuthorized($user);
        $this->freemius = $freemius;
    }
    
    /**
     * Get Freemius instance
     * @return Freemius
     */
    public function getFreemius()
    {
        return $this->freemius;
    }
    
    /**
     * Checks if this user is authorized to use the classes and methods
     * @param WP_User object $user
     * @compatibility 5.6
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itDoesNotAuthorizeIfNotMultisite() 
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itDoesNotAuthorizeIfNotCurrentUser()
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itReturnsFalseIfUserIsNotSuperAdmin()
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itChecksIfUserIsAuthorizedSuperAdmin() 
     */
    final protected function checksIfUserIsAuthorized($user = null)
    {
        $authorized = false;
        if (! $user) {
            return $authorized;
        }
        $multisite = false;
        if (is_multisite()) {
            $multisite = true;
        }
        if ($this->canManageSite($user->ID, $multisite) && get_current_user_id() === $user->ID) {
            $authorized = true;
        }
        
        if ($authorized) {
            $this->prime_mover_user_id = $user->ID;
        }
        
        if (!$authorized && true === $this->isDoingAutoBackup()) {
            $authorized = true;
        }
        
        return $this->isReallyAuthorized($authorized);
    }
 
    /**
     * Complete authorization checks including doing an automatic backup process
     * @param boolean $authorized
     * @return string|boolean
     */
    final protected function isReallyAuthorized($authorized = false)
    {
        if (!$this->isDoingAutoBackup()) {
            return $authorized;
        }
        
        if (!$authorized) {
            return $authorized;
        }
        
        global $prime_mover_plugin_manager;
        if (!$prime_mover_plugin_manager->getAutoBackupIdentity()) {
            return false;
        }
        
        $auto_backup_identity = $prime_mover_plugin_manager->getAutoBackupIdentity();
        $request_api_key = primeMoverGetApiRequestKey();
        if (!$request_api_key || !$auto_backup_identity) {
            return false;
        }
                
        $decrypted = primeMoverOpenSSLDecrypt($auto_backup_identity, $request_api_key, true);
        if (!$decrypted) {
            return false;
        }
        
        if (false === filter_var($decrypted, FILTER_VALIDATE_INT, ["options" => ["min_range"=> 1]])) {
            return false;
        }
        
        $decrypted = (int)$decrypted;
        if ($decrypted < time() - PRIME_MOVER_CRON_AUTOBACKUP_EXPIRATION) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Checks if doing auto backup
     * @return boolean
     */
    final public function isDoingAutoBackup()
    {
        global $prime_mover_plugin_manager;
        if (is_object($prime_mover_plugin_manager) && is_a($prime_mover_plugin_manager, PRIME_MOVER_MUST_PLUGIN_MANAGER_CLASS) && 
            method_exists($prime_mover_plugin_manager, 'doingAutoBackup')) {
            return $prime_mover_plugin_manager->doingAutoBackup();
        }
        return false;
    }    
    
    /**
     * Check if currently logged-in can manage network
     * @param number $user_id
     * @return boolean
     */
    final public function canManageSite($user_id = 0, $multisite = true) 
    {
        if ( ! $user_id ) {
            return false;
        }
        if ($multisite && user_can($user_id, 'manage_network')) {
            return true;
        }
        if ( ! $multisite && user_can($user_id, 'manage_options')) {
            return true;
        }
        return false;
    }
    /**
     * Gets user authorization
     * @compatibility 5.6
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itDoesNotAuthorizeIfNotMultisite() 
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itDoesNotAuthorizeIfNotCurrentUser()
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itReturnsFalseIfUserIsNotSuperAdmin()
     * @tested PrimeMoverFramework\Tests\TestPrimeMoverSystemAuthorization::itChecksIfUserIsAuthorizedSuperAdmin() 
     * 
     */
    final public function isUserAuthorized()
    {
        return $this->is_authorized;
    }
    
    /**
     * Checks if current user is Prime Mover user
     * @param number $user_id
     * @return boolean
     */
    final public function isPrimeMoverUser($user_id = 0)
    {
        if ( ! $this->isUserAuthorized() ) {
            return false;
        }
        
        if ( ! $user_id ) {
            return false;
        }
        
        return ($this->prime_mover_user_id === $user_id);        
    }
}
