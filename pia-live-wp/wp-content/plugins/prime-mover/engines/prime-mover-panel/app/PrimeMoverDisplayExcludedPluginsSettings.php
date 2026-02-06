<?php
namespace Codexonics\PrimeMoverFramework\app;

/*
 * This file is part of the Codexonics.PrimeMoverFramework package.
 *
 * (c) Codexonics Ltd
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prime Mover display excluded plugins
 *
 */
class PrimeMoverDisplayExcludedPluginsSettings
{
    private $prime_mover_settings;
    private $prime_mover_settings_template;
    
    const EXCLUDED_PLUGINS = 'excluded_plugins';
    
    /**
     * Constructor
     * @param PrimeMoverSettingsTemplate $prime_mover_settings_template
     */
    public function __construct(PrimeMoverSettingsTemplate $prime_mover_settings_template) 
    {
        $this->prime_mover_settings_template = $prime_mover_settings_template;
    }
    
    /**
     * Get settings template
     * @return string|\Codexonics\PrimeMoverFramework\app\PrimeMoverSettingsTemplate
     */
    public function getPrimeMoverSettingsTemplate()
    {
        return $this->prime_mover_settings_template;
    }
    
    /**
     * Settings instance
     * @return \Codexonics\PrimeMoverFramework\app\PrimeMoverSettings
     */
    public function getPrimeMoverSettings()
    {
        return $this->getPrimeMoverSettingsTemplate()->getPrimeMoverSettings();
    }
        
    /**
     * Get Prime Mover instance
     * @return \Codexonics\PrimeMoverFramework\classes\PrimeMover
     */
    public function getPrimeMover()
    {
        return $this->getPrimeMoverSettings()->getPrimeMover();
    }
    
    /**
     * Get settings markup
     * @return \Codexonics\PrimeMoverFramework\utilities\PrimeMoverSettingsMarkups
     */
    public function getSettingsMarkup()
    {
        return $this->getPrimeMoverSettings()->getSettingsMarkup();
    }
    
    /**
     * Build activated plugins array for markup
     */
    protected function buildPluginsArray()
    {
        global $wp_filesystem;        
        $plugins = get_plugins();
        $validated = [];
        
        $empty = false;
        if ( is_array($plugins) && 1 === count($plugins)) {
            $key = key($plugins);
            if ('prime-mover.php' === basename($key)) {
                $empty = true;
            }
        }
        if ($empty) {
            return [];
            
        } else {
           foreach ($plugins as $plugin_basename => $plugin_details) {
                $plugin_file = basename($plugin_basename);
                if ('prime-mover.php' === $plugin_file) {
                    continue;    
                }
                
                $plugin_full_path = PRIME_MOVER_PLUGIN_CORE_PATH . $plugin_basename;                
                if (!$wp_filesystem->exists($plugin_full_path)) {
                    continue;
                }    
                
                if (empty($plugin_details['Name'])) {
                    continue;                    
                }
                
                $validated[$plugin_basename] = $plugin_details;                
            }
        }
        
        return $validated;        
    }
    
    /**
     * Show excluded plugin settings
     */
    public function showExcludedPluginsSetting()
    {
        $settings_api = $this->getPrimeMoverSettingsTemplate()->getSettingsConfig()->getMasterSettingsConfig();
        $identifier = 'excluded-plugins';
        
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
        $heading_text = esc_html__('Excluded plugins', 'prime-mover');
        
        $first_paragraph = '';
        $first_paragraph .= '<div class="prime-mover-setting-description">';
        $first_paragraph .= '<p class="description prime-mover-settings-paragraph">';
        
        $first_paragraph .= esc_html__('By default, all plugins that is activated for the exported site will be included in the export package.', 'prime-mover'); 
        $first_paragraph .= '</p>';
        $first_paragraph .= '<p class="description">';
        
        $first_paragraph .= sprintf(esc_html__('It is possible to exclude plugins from being exported by adding the %s in the above text area. 
                  Use the tool below to add or updated excluded plugins to the text area (Prime Mover is already excluded by default):',
                        'prime-mover'), esc_html__('plugin basename', 'prime-mover'));
        
        $first_paragraph .= '</p>';        
        $toggle_btn_title = esc_attr(esc_html__('Click this button to expand activated plugins.', 'prime-mover'));
        $validated_array = $this->buildPluginsArray();
        $empty_text = esc_html__('No other plugins found', 'prime-mover');
        
        $second_paragraph = '';
        $second_paragraph .= '<p class="description prime-mover-settings-paragraph">';
        $second_paragraph .= esc_html__('Take note this is a global setting and applies to every export generated in this site. You can use this setting to exclude plugins that is is not needed in the target site.',
            'prime-mover'); 
        
        $second_paragraph .= '</p>';
        $second_paragraph .= '<p class="description">';
        $second_paragraph .= sprintf(esc_html__('As a result, this excluded plugin is %s at the target site after the package is imported.',
            'prime-mover'), '<strong>' . esc_html__('DEACTIVATED', 'prime-mover') . '</strong>');
        
        $second_paragraph .= '</p>'; 
                    
        $this->getPrimeMoverSettingsTemplate()->renderCheckBoxesTextAreaDisplayTemplate($heading_text, $identifier, $config, true, $first_paragraph, $toggle_btn_title,
            $validated_array, $empty_text, $second_paragraph, $button_specs);        
    }    
}