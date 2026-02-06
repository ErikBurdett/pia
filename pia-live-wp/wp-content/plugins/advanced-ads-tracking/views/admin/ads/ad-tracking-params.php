<?php
/**
 * Single ad tracking settings.
 *
 * @since   2.6.0
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 *
 * @var bool $sponsored value for sponsored rel attribute.
 * @var Ad $ad the current ad.
 * @var bool $cloaking_enabled Is link cloaking enabled for this ad.
 * @var bool $cloaking_cb_disabled Is link cloaking filtered and checkbox does not have effect.
 */

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Utilities\Tracking;

$content         = $ad->get_content();
$options         = wp_advads_tracking()->options->get_all();
$general_options = Advanced_Ads::get_instance()->options();
?>
<div class="advads-option-list">
	<span class="label"><?php esc_html_e( 'Tracking', 'advanced-ads-tracking' ); ?></span>
	<div>
		<select name="advanced_ad[tracking][enabled]">
			<?php foreach ( $tracking_choices as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $enabled, $key ); ?>>
				<?php
				printf(
					/* translators: %1$s is the option value and %1$s is the default value */
					'%1$s' . ( 'default' === $key ? ' (%2$s)' : '' ),
					esc_html( $value ),
					Helpers::get_default_tracking_method() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				?>
				</option>
			<?php endforeach; ?>
		</select>
		<a href="https://wpadvancedads.com/manual/tracking-documentation/?utm_source=advanced-ads?utm_medium=link&utm_campaign=ad-edit-tracking" class="advads-manual-link" target="_blank">
			<?php esc_html_e( 'Manual', 'advanced-ads-tracking' ); ?>
		</a>
	</div>
	<hr/>
	<label for="advads-url" class="label"><?php esc_html_e( 'Target URL', 'advanced-ads-tracking' ); ?></label>
	<div>
		<?php if ( $link && strpos( $content, '%link%' ) === false && ( strpos( $content, 'href=' ) !== false || strpos( $content, '<script' ) !== false || strpos( $content, '<iframe' ) !== false ) ) : ?>
			<p class="advads-notice-inline advads-idea">
				<?php
					esc_html_e( 'Based on your configuration, it seems that you do not need to specify an external link. You can leave the URL field empty.', 'advanced-ads-tracking' );

					printf(
						/* translators: %s is an inline code snippet */
						esc_html__( 'If you enable the link cloaking option, please replace the links you want to track in the ad code with the tracking placeholder (%s). Otherwise, Advanced Ads will not record clicks on this ad.', 'advanced-ads-tracking' ),
						'<code>%link%</code>'
					);
				?>
				<a href="https://wpadvancedads.com/manual/tracking-documentation/?utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-edit-cloaking#Click_tracking_and_link_cloaking" class="advads-manual-link" target="_blank"><?php esc_html_e( 'Manual', 'advanced-ads-tracking' ); ?></a>
			</p>
		<?php endif; ?>

		<input type="url" name="advanced_ad[url]" id="advads-url" class="advads-ad-url" style="width:60%;"
			value="<?php echo esc_url( $link ); ?>"
			placeholder="<?php echo 'https://www.example.com/'; ?>"/>
		<a href="https://wpadvancedads.com/manual/tracking-documentation/?utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-edit-tracking-url#Click_tracking_and_link_cloaking" class="advads-manual-link" target="_blank"><?php esc_html_e( 'Manual', 'advanced-ads-tracking' ); ?></a>
		<p class="description">
			<?php
				esc_html_e( 'Links your ad to the target URL.', 'advanced-ads-tracking' );
				echo ' ';
				echo wp_kses(
					__( 'If the ad code contains an <code>&lt;a&gt;</code> tag with a link target, copy the URL into the Target URL field and add <code>"%link%"</code> to your ad code.', 'advanced-ads-tracking' ),
					[
						'code' => [],
					]
				);
				?>
		</p>
		<ul id="ad-parameters-box-notices__target-url" class="advads-metabox-notices">
			<li class="advads-ad-notice-tracking-link-placeholder-missing error hidden">
				<?php
				printf(
					/* translators: %s is the HTML tags displayed in the frontend. */
					esc_html__( 'Use the %1$s parameter in your editor, or remove %2$s from Target URL.', 'advanced-ads-tracking' ),
					'<code>%link%</code>',
					'<code>URL</code>'
				);
				?>
			</li>
		</ul>
		<p>
			<label for="advads-cloaking">
				<input type="checkbox" name="advanced_ad[tracking][cloaking]" id="advads-cloaking" <?php checked( $cloaking_enabled ); ?> <?php disabled( $cloaking_cb_disabled ); ?>/>
				<?php
				printf(
					/* translators: %s is the URL displayed in the frontend, wrapped in <code> tags. */
					esc_html__( 'Cloak your link. The link will be displayed as %s.', 'advanced-ads-tracking' ),
					sprintf( '<code>%s</code>', esc_url( Tracking::build_click_tracking_url( $ad ) ) )
				);
				?>
			</label>
			<?php
			// show a notice if link cloaking universally filtered.
			if ( $cloaking_cb_disabled ) :
				?>
				<br/>
				<span class="advads-message-warning">
					<?php
					/* translators: %s is the filter name wrapped in <code> tags. */
					printf( esc_html__( 'The value for link cloaking is defined for all ads by the %s filter.', 'advanced-ads-tracking' ), '<code>advanced-ads-ad-option-tracking.cloaking</code> or <code>advanced-ads-ad-get-tracking.cloaking</code>' );
					?>
				</span>
			<?php endif; ?>
		</p>
	</div>
	<hr/>
	<span class="label"><?php esc_html_e( 'target window', 'advanced-ads-tracking' ); ?></span>
	<div>
		<label>
			<input name="advanced_ad[tracking][target]" type="radio" value="default" <?php checked( $target, 'default' ); ?>/>
			<?php esc_html_e( 'default', 'advanced-ads-tracking' ); ?>
		</label>
		<label>
			<input name="advanced_ad[tracking][target]" type="radio" value="same" <?php checked( $target, 'same' ); ?>/>
			<?php esc_html_e( 'same window', 'advanced-ads-tracking' ); ?>
		</label>
		<label>
			<input name="advanced_ad[tracking][target]" type="radio" value="new" <?php checked( $target, 'new' ); ?>/>
			<?php esc_html_e( 'new window', 'advanced-ads-tracking' ); ?>
		</label>
		<span class="advads-help"><span class="advads-tooltip">
			<?php esc_html_e( 'Where to open the link (if present).', 'advanced-ads-tracking' ); ?>
			<br/>
			<?php
			printf(
				/* translators: %s is the default value ("new window" or "same window") */
				esc_html__( 'It is "%s" by default. You can change this in the General settings.', 'advanced-ads-tracking' ),
				esc_html(
					( $general_options['target-blank'] ?? false )
						? __( 'new window', 'advanced-ads-tracking' )
						: __( 'same window', 'advanced-ads-tracking' )
				)
			);
			?>
		</span></span>
	</div>
	<hr/>
	<span class="label"><?php esc_html_e( 'Add “nofollow”', 'advanced-ads-tracking' ); ?></span>
	<div>
		<label>
			<input name="advanced_ad[tracking][nofollow]" type="radio" value="default" <?php checked( $nofollow, 'default' ); ?>/>
			<?php esc_html_e( 'default', 'advanced-ads-tracking' ); ?>
		</label>
		<label>
			<input name="advanced_ad[tracking][nofollow]" type="radio" value="1" <?php checked( $nofollow, 1 ); ?>/>
			<?php esc_html_e( 'yes', 'advanced-ads-tracking' ); ?>
		</label>
		<label>
			<input name="advanced_ad[tracking][nofollow]" type="radio" value="0" <?php checked( $nofollow, 0 ); ?>/>
			<?php esc_html_e( 'no', 'advanced-ads-tracking' ); ?>
		</label>
		<span class="advads-help">
			<span class="advads-tooltip">
				<?php
					echo wp_kses( __( 'Add <code>rel="nofollow"</code> to tracking links.', 'advanced-ads-tracking' ), [ 'code' => [] ] );
				?>
				<br/>
				<?php
				printf(
					/* translators: %1$s is the default value ("yes" or "no") */
					esc_html__( 'It is "%1$s" by default. You can change this in the Tracking settings.', 'advanced-ads-tracking' ),
					esc_html(
						$options['sponsored']
							? __( 'yes', 'advanced-ads-tracking' )
							: __( 'no', 'advanced-ads-tracking' )
					)
				);
				?>
			</span>
		</span>
	</div>
	<hr/>
	<span class="label"><?php esc_attr_e( 'Add “sponsored”', 'advanced-ads-tracking' ); ?></span>
	<div>
		<label>
			<input name="advanced_ad[tracking][sponsored]" type="radio" value="default" <?php checked( $sponsored, 'default' ); ?>/>
			<?php esc_attr_e( 'default', 'advanced-ads-tracking' ); ?>
		</label>
		<label>
			<input name="advanced_ad[tracking][sponsored]" type="radio" value="1" <?php checked( $sponsored, '1' ); ?>/>
			<?php esc_attr_e( 'yes', 'advanced-ads-tracking' ); ?>
		</label>
		<label>
			<input name="advanced_ad[tracking][sponsored]" type="radio" value="0" <?php checked( $sponsored, '0' ); ?>/>
			<?php esc_attr_e( 'no', 'advanced-ads-tracking' ); ?>
		</label>
		<span class="advads-help">
			<span class="advads-tooltip">
				<?php
					/* translators: %s <code>rel="sponsored"</code> */
					printf( esc_html__( 'Add %s to tracking links.', 'advanced-ads-tracking' ), '<code>rel="sponsored"</code>' );
				?>
				<br/>
				<?php
				printf(
					/* translators: %1$s is the default value ("yes" or "no") */
					esc_html__( 'It is "%1$s" by default. You can change this in the Tracking settings.', 'advanced-ads-tracking' ),
					esc_html(
						$options['sponsored']
							? __( 'yes', 'advanced-ads-tracking' )
							: __( 'no', 'advanced-ads-tracking' )
					)
				);
				?>
			</span>
		</span>
	</div>
	<hr/>
</div>
