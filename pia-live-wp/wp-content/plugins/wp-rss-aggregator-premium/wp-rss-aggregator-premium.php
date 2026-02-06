<?php

/**
 * @wordpress-plugin
 *
 * Plugin Name:       WP RSS Aggregator - Premium
 * Plugin URI:        https://wprssaggregator.com
 * Description:       The premium upgrade for WP RSS Aggregator.
 * Version:           5.0.11
 * Requires at least: 6.2.2
 * Requires PHP:      7.4.0
 * Author:            RebelCode
 * Author URI:        https://rebelcode.com
 * Text Domain:       wp-rss-aggregator-premium
 * Domain Path:       /languages
 * License:           GPL-3.0
 */

use RebelCode\Aggregator\Core\Tier;
use RebelCode\Aggregator\Core\Licensing\License;
use RebelCode\Aggregator\Core\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WPRA_PREMIUM' ) ) {
	return;
}

define( 'WPRA_PREMIUM', __FILE__ );
define( 'WPRA_PREMIUM_DIR', __DIR__ );
define( 'WPRA_PREMIUM_ITEM_ID', 0 );
define( 'WPRA_PREMIUM_VERSION', '5.0.11' );

if ( ! defined( 'WPRA_PREMIUM_ROOT' ) ) {
	define( 'WPRA_PREMIUM_ROOT', __FILE__ );
}
if ( ! defined( 'WPRA_PREMIUM_ROOT_URL' ) ) {
	define( 'WPRA_PREMIUM_ROOT_URL', plugin_dir_url( WPRA_PREMIUM_ROOT ) );
}

add_action(
	'init',
	function () {
		load_plugin_textdomain(
			'wp-rss-aggregator-premium',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);

add_action(
	'wpra.run.before',
	function () {
		$wpra = wpra();

		$licensing = $wpra->get( 'licensing' );
		assert( $licensing instanceof Licensing );

		add_action(
			'admin_init',
			function () use ( $licensing ) {
				$license = $licensing->getLicense();
				if ( $license && $license->status !== License::Valid ) {
					return;
				}
				if ( isset( $license->eddId ) ) {
					$licensing->createUpdater( $license->eddId, WPRA_PREMIUM_ROOT, WPRA_PREMIUM_VERSION );
				}
			}
		);

		if ( defined( 'WPRA_VERSION' ) && version_compare( WPRA_VERSION, WPRA_PREMIUM_VERSION, '!=' ) ) {
			add_action(
				'admin_notices',
				function () {
					$plugins_page_url = admin_url( 'update-core.php' );

					printf(
						'<div class="notice notice-error is-dismissible wpra-premium-version-notice">
						<div class="wpra-premium-license-notice-bee">
							<img src="%1$s" alt="Bee Icon" />
						</div>
						<div class="wpra-premium-license-notice-content">
							<h3>%2$s</h3>
							<p>%3$s</p>
						</div>
					</div>',
						esc_url( WPRA_URL . 'core/imgs/bee.svg' ),
						esc_html__( 'Plugin Version Mismatch', 'wp-rss-aggregator-premium' ),
						sprintf(
						/* translators: %1$s: Plugins page url %2$s: Learn more url */
							__( 'Your free and premium Aggregator plugins are running different versions, so Premium features are temporarily paused. <a href="%1$s">Please update</a> both plugins to the same version to restore full compatibility. <a target="_blank" href="%2$s">Learn more</a>', 'wp-rss-aggregator-premium' ),
							esc_url( $plugins_page_url ),
							esc_url( 'https://www.wprssaggregator.com/help/plugin-version-mismatch/' )
						)
					);

					$style = '
					<style type="text/css">
					.wpra-premium-version-notice {
						display: flex;
						align-items: center;
						padding:0;
						border-left-color: #F34413;
					}
					.wpra-premium-version-notice .wpra-premium-license-notice-bee {
						display: flex;
						align-items: center;
						height: 82px;
						padding: 0 20px;
						background-color: #F6F7FB;
						padding-bottom: 20px;
					}
					.wpra-premium-version-notice .wpra-premium-license-notice-content {
						margin-left: 24px;
						display: flex;
						gap: 5px;
						flex-direction: column;
					}
					.wpra-premium-version-notice h3 {
						color:#F34413;
						margin: 0;
						font-size: 14px;
					}
					.wpra-premium-version-notice p {
						margin: 0;
						color:#2F2F2F;
					}
					</style>';
					echo $style;
				}
			);
			return;
		}

		$wpra->premiumInstalled = true;
		$tier = $licensing->getTier();
		if ( $tier >= Tier::Basic ) {
			$wpra->loadPackages( WPRA_PREMIUM_ROOT, array( 'basic' ) );
		}
		if ( $tier >= Tier::Plus ) {
			$wpra->loadPackages( WPRA_PREMIUM_ROOT, array( 'plus' ) );
		}
		if ( $tier >= Tier::Pro ) {
			$wpra->loadPackages( WPRA_PREMIUM_ROOT, array( 'pro' ) );
		}
		if ( $tier >= Tier::Elite ) {
			$wpra->loadPackages( WPRA_PREMIUM_ROOT, array( 'elite' ) );
		}

		add_action(
			'admin_notices',
			function () use ( $licensing ) {
				$license = $licensing->getLicense();
				if ( $license && $license->status === License::Valid ) {
					return;
				}

				$dismissed = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
				if ( in_array( 'wpra_dismiss_notice', explode( ',', (string) $dismissed ), true ) ) {
					return;
				}

				$manage_plan_url = add_query_arg(
					array(
						'page' => 'wprss-aggregator',
						'subPage' => 'upgrade',
					),
					admin_url( 'admin.php' )
				);

				printf(
					'<div class="notice notice-error is-dismissible wpra-premium-license-notice">
					<div class="wpra-premium-license-notice-bee">
						<img src="%1$s" alt="Bee Icon" />
					</div>
					<div class="wpra-premium-license-notice-content">
						<h3>%2$s</h3>
						<p>%3$s</p>
					</div>
				</div>',
					esc_url( WPRA_URL . 'core/imgs/bee.svg' ),
					esc_html__( 'Unlock Your Aggregator Premium Plan', 'wp-rss-aggregator-premium' ),
					sprintf(
					/* translators: %1$s: Manage plan page url */
						__( '<a href="%1$s">Activate your license</a> to access your new premium features.', 'wp-rss-aggregator-premium' ),
						esc_url( $manage_plan_url )
					)
				);

				$script = "
				<script>
					jQuery( function( $ ) {
						// On dismissing the notice, make a POST request to store this notice with the dismissed WP pointers so it doesn't display again.
						$('.wpra-premium-license-notice').on( 'click', '.notice-dismiss', function() {
							$.post( ajaxurl, {
								pointer: " . wp_json_encode( 'wpra_dismiss_notice' ) . ",
								action: 'dismiss-wp-pointer'
							} );
						} );
					} )
				</script>";

				$style = '
				<style type="text/css">
				.wpra-premium-license-notice {
					display: flex;
					align-items: center;
					padding:0;
					border-left-color: #F34413;
				}
				.wpra-premium-license-notice-bee {
					display: flex;
					align-items: center;
					height: 82px;
					padding: 0 20px;
					background-color: #F6F7FB;
					padding-bottom: 20px;
				}
				.wpra-premium-license-notice-content {
					margin-left: 24px;
					display: flex;
					gap: 5px;
					flex-direction: column;
				}
				.wpra-premium-license-notice h3 {
					color:#F34413;
					margin: 0;
					font-size: 14px;
				}
				.wpra-premium-license-notice p {
					margin: 0;
					color:#2F2F2F;
				}
				</style>';
				echo $script;
				echo $style;
			}
		);
	}
);
