<?php
/**
 * The class hold functions for tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 *
 * phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText
 * phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
 * phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralContext
 */

/**
 * Wrapper for translation from other domains
 *
 * @param string $text   Text to translate.
 * @param string $domain Domain to translate from.
 *
 * @return string
 */
function advads__( $text, $domain = 'default' ): string {
	return translate( $text, $domain );
}

/**
 * Wrapper for translation from other domain with context
 *
 * @param string $text    Text to translate.
 * @param string $context Context information for the translators.
 * @param string $domain  Domain to translate from.
 *
 * @return string
 */
function advads_x( $text, $context, $domain = 'default' ): string {
	return translate_with_gettext_context( $text, $context, $domain );
}

/**
 * Wrapper for translation from other domain with plural
 *
 * @param string $single  Single text to translate.
 * @param string $plural  Plural text to translate.
 * @param int    $number  Number to use for plural.
 * @param string $domain  Domain to translate from.
 *
 * @return string
 */
function advads_n( $single, $plural, $number, $domain = 'default' ): string {
	$translations = get_translations_for_domain( $domain );
	$translation  = $translations->translate_plural( $single, $plural, $number );

	return apply_filters( 'ngettext', $translation, $single, $plural, $number, $domain );
}

/**
 * Echo translation from other domains
 *
 * @param string $text   Text to translate.
 * @param string $domain Domain to translate from.
 *
 * @return void
 */
function advads_e( $text, $domain = 'default' ): void {
	echo advads__( $text, $domain ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
