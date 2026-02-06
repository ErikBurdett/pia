<?php //phpcs:ignoreFile
/**
 * Placements test weights markup
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var \AdvancedAds\Abstracts\Placement $placement placement of the current row.
 */

$test_id = $placement->get_prop( 'test_id' );
?>
<?php if ( $test_id ) : ?>
	<input type="hidden" name="advads[placements][options][test_id]" value="<?php echo esc_attr( $test_id ); ?>"/>
	<?php echo esc_html_x( 'Testing', 'placement tests', 'advanced-ads-pro' ); ?>
<?php else: ?>
	<label><?php _e( 'Test weight', 'advanced-ads-pro' ); ?>
		<select class="advads-add-to-placement-test" data-slug="<?php echo esc_attr( $placement->get_slug() ); ?>">
			<option value=""></option>
			<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
				<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
			<?php endfor; ?>
		</select>
	</label>
<?php endif; ?>
<br/>
<br/>
<a href="#" class="save-new-test button button-primary hidden"><?php esc_html_e( 'Save new test', 'advanced-ads-pro' ); ?></a>
