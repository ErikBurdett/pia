<?php
/**
 * Rollover buttons linebreak.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      5.1.0
 */

$button_delimiter = 'clean' === Avada()->settings->get( 'woocommerce_product_box_design' ) ? '/' : '';
$button_delimiter = apply_filters( 'awb_rollover_button_delimiter', $button_delimiter );
?>
<span class="fusion-rollover-linebreak">
	<?php echo $button_delimiter; ?>
</span>
