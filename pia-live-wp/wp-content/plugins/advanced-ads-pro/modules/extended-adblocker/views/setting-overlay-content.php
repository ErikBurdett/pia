<?php
/**
 * Ad Blocker overlay options 'dismiss button'
 *
 * @package    Advanced_Ads_Pro\Module
 * @var string $option_name   array index name
 * @var boolean $checked      True, when the option is checked.
 */

?>
<h4><?php esc_html_e( 'Content', 'advanced-ads-pro' ); ?></h4>
<?php wp_editor( $content, 'adblocker_overlay_content_editor', $args ); ?>
