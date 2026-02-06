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
use Freemius;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover common settings markup
 *
 */
class PrimeMoverSettingsMarkups
{     
    private $prime_mover;
    
    /**
     * Construct
     * @param PrimeMover $prime_mover
     * @param array $utilities
     */
    public function __construct(PrimeMover $prime_mover, $utilities = [])
    {
        $this->prime_mover = $prime_mover;
    }

    /**
     * Get multisite migration
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     * @compatible 5.6
     */
    public function getPrimeMover()
    {
        return $this->prime_mover;
    }
    
    /**
     * Get Freemius
     * @return Freemius
     */
    public function getFreemius()
    {
        return $this->getPrimeMover()->getSystemAuthorization()->getFreemius();
    }
    
    /**
     * Render submit button
     * @param string $nonce_key
     * @param string $button_id
     * @param string $spinner_class
     * @param string $wrapper
     * @param string $button_classes
     * @param string $button_text
     * @param string $disabled
     * @param string $title
     * @param boolean $pro
     * @param number $blog_id
     */
    public function renderSubmitButton($nonce_key = '', $button_id = '', $spinner_class = '', $wrapper = 'div', $button_classes = 'button-primary', 
        $button_text = '', $disabled = '', $title = '', $pro = true, $blog_id = 0)
    {
        $spinner_class = "$spinner_class prime_mover_settings_spinner";
        $main_opening_tag = '<div class="p_wrapper_prime_mover_setting">';
        $main_closing_tag = '</div>';
        $spinner_tag = '<div class="' . esc_attr($spinner_class) . '"></div>';
        if ('p' === $wrapper) {
            $main_opening_tag = '<p class="p_wrapper_prime_mover_setting">';
            $main_closing_tag = '</p>';
            $spinner_tag = '<span class="' . esc_attr($spinner_class) . '"></span>';
        }
        if ( ! $button_text ) {
            $button_text =  __('Save', 'prime-mover');
        }
        echo $main_opening_tag;      
        $render = false;
        if ($blog_id) {
            $render = apply_filters('prime_mover_multisite_blog_is_licensed', false, $blog_id);            
        } else {
            $render = apply_filters('prime_mover_is_loggedin_customer', false);
        }
        if (!$render && $pro) {
            $upgrade_url = apply_filters('prime_mover_filter_upgrade_pro_url', $this->getFreemius()->get_upgrade_url(), $blog_id);
    ?>        
            <a title="<?php esc_attr_e('This is PRO feature setting. Please upgrade or activate license to use this setting.', 'prime-mover'); ?>" 
            class="prime-mover-upgrade-button-simple button" href="<?php echo esc_url($upgrade_url); ?>">            
            <?php echo apply_filters('prime_mover_filter_upgrade_pro_text', esc_html__( 'Upgrade to PRO', 'prime-mover' ), $blog_id); ?></a>
     <?php        
        } else {
       ?>
            <button title="<?php echo esc_attr($title);?>" <?php echo esc_html($disabled); ?> data-prime-mover-blogid-panel="<?php echo esc_attr($blog_id); ?>" data-nonce="<?php echo $this->getPrimeMover()->getSystemFunctions()->primeMoverCreateNonce($nonce_key); ?>" 
            id="<?php echo esc_attr($button_id);?>" class="<?php echo esc_attr($button_classes);?>" type="button">
            <?php echo esc_html($button_text);?></button>
            <?php echo $spinner_tag; ?>   
    <?php    
        }          
        echo $main_closing_tag;
    }
    
    /**
     * Generate download URL for log
     * @param string $nonce_key
     * @param string $nonce_arg
     * @param string $arg_key
     * @param number $blog_id
     * @return string|mixed
     */
    public function generateDownloadLogUrl($nonce_key = '', $nonce_arg = '', $arg_key = '', $blog_id = 0)
    {
        $nonce = $this->getPrimeMover()->getSystemFunctions()->primeMoverCreateNonce($nonce_key);
        $params = [$arg_key => 'yes', $nonce_arg => $nonce];
        if ($blog_id) {
            $params['autobackup_blogid'] = $blog_id;
        }
        
        return esc_url(add_query_arg($params));
    }
    
    /**
     * Start markup
     * @param string $heading
     * @param string $doc
     * @param number $blog_id
     */
    public function startMarkup($heading = '', $doc = '', $blog_id = 0)
    {
        ?>
        <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">
                <label><?php echo esc_html($heading); ?></label>
                <?php 
                    if ($doc) { 
                        do_action('prime_mover_last_table_heading_settings', $doc, $blog_id); 
                    }
                ?>
            </th>
            <td>                      
                <div class="prime-mover-setting-description">
    <?php    
    }
    
    /**
     * End markup
     */
    public function endMarkup()
    {
        ?>
                </div>                      
            </td>
        </tr>
        </tbody>
        </table>
    <?php        
    }
}
