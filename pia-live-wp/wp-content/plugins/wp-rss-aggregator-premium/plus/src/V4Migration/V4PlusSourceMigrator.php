<?php

namespace RebelCode\Aggregator\Plus\V4Migration;

use WP_User;
use RebelCode\Aggregator\Plus\Templates\TokenType;
use RebelCode\Aggregator\Plus\Templates\TokenRenderer;
use RebelCode\Aggregator\Plus\Source\TaxonomyRule;
use RebelCode\Aggregator\Plus\Source\PostDateToUse;
use RebelCode\Aggregator\Plus\Source\ExcerptToUse;
use RebelCode\Aggregator\Plus\Source\AuthorToUse;
use RebelCode\Aggregator\Plus\Source\AuthorMethod;
use RebelCode\Aggregator\Core\Utils\Bools;
use RebelCode\Aggregator\Core\Source;
use RebelCode\Aggregator\Core\Logger;
use RebelCode\Aggregator\Basic\Conditions\Expression;
use RebelCode\Aggregator\Basic\Conditions\Condition;

class V4PlusSourceMigrator {

	private array $ftpSettings;

	public function __construct( array $ftpSettings ) {
		$this->ftpSettings = $ftpSettings;
	}

	public function migrate( Source $src, array $meta ): Source {
		try {
			$src->settings->postStatus = $meta['wprss_ftp_post_status'] ?? 'draft';
		$src->settings->postFormat = $meta['wprss_ftp_post_format'] ?? 'standard';

		$commentStatus = $meta['wprss_ftp_comment_status'] ?? '0';
		$src->settings->commentsOpen = Bools::normalize( $commentStatus );

		$canonicalLink = $meta['wprss_ftp_canonical_link'] ?? '0';
		$src->settings->canonicalLink = Bools::normalize( $canonicalLink );

		// date settings
		$postDate = $meta['wprss_ftp_post_date'] ?? '';
		$src->settings->whichPostDate = ( strtolower( $postDate ) === 'imported' )
			? PostDateToUse::IMPORT_DATE
			: PostDateToUse::PUBLISHED_DATE;

		// author settings
		$author = $meta['wprss_ftp_def_author'] ?? '.';
		$fbAuthorId = $meta['wprss_ftp_fallback_author'] ?? '0';
		$fbAuthorMethod = $meta['wprss_ftp_author_fallback_method'] ?? '';
		$noAuthorFound = $meta['wprss_ftp_no_author_found'] ?? '';

		if ( $author === '.' ) {
			$user = get_user_by( 'id', $fbAuthorId );
			if ( ! ( $user instanceof WP_User ) ) {
				$user = get_user_by( 'login', $fbAuthorId );
				if ( $user instanceof WP_User ) {
					$fbAuthorId = $user->ID;
				}
			}

			$create = strtolower( $fbAuthorMethod ) === 'create';
			$src->settings->whichAuthor = AuthorToUse::FEED;
			$src->settings->fallbackAuthorId = (int) $fbAuthorId;
			$src->settings->authorMethod = $create ? AuthorMethod::CREATE : AuthorMethod::FALLBACK;
			$src->settings->mustHaveAuthor = strtolower( $noAuthorFound ) !== 'fallback';
		} else {
			$src->settings->whichAuthor = AuthorToUse::USER;
			$src->settings->fallbackAuthorId = (int) $author;
		}

		// audio settings
		$audioPlayer = $meta['wprss_ftp_audio_player'] ?? '0';
		$audioPlayerPos = $meta['wprss_ftp_audio_player_pos'] ?? 'before';
		$src->settings->enableAudioPlayer = Bools::normalize( $audioPlayer );
		$src->settings->audioPlayerPos = strtolower( $audioPlayerPos );
		$powerPress = $meta['wprss_ftp_powerpress_enabled'] ?? false;
		$src->settings->enablePowerPress = Bools::normalize( $powerPress );

		// word trimming settings
		$wordLimitEnabled = $meta['wprss_ftp_word_limit_enabled'] ?? '0';
		$wordLimitEnabled = strtolower( $wordLimitEnabled );
		if ( $wordLimitEnabled === 'general' ) {
			$wordLimitEnabled = true;
		} else {
			$wordLimitEnabled = Bools::normalize( $wordLimitEnabled );
		}

		$wordLimit = $meta['wprss_ftp_word_limit'] ?? '0';
		$wordLimit = empty( $wordLimit )
			? (int) ( $this->ftpSettings['word_limit'] ?? 0 )
			: (int) $wordLimit;

		$src->settings->contentNumWords = $wordLimit;

		$trimmingEllipsis = $meta['wprss_ftp_trimming_ellipsis'] ?? '0';
		$trimmingEllipsis = Bools::normalize( $trimmingEllipsis );

		$trimmingType = $meta['wprss_ftp_trimming_type'] ?? 'general';
		$trimmingType = strtolower( $trimmingType );
		if ( $trimmingType === 'general' ) {
			$trimmingType = $this->ftpSettings['trimming_type'] ?? 'db';
			$trimmingType = strtolower( $trimmingType );
		}

		if ( $wordLimitEnabled && $trimmingType === 'db' ) {
			$src->settings->trimContent = true;
		}

		// excerpt settings

		$importExcerpt = $meta['wprss_ftp_import_excerpt'] ?? '0';
		$importExcerpt = Bools::normalize( $importExcerpt );
		$src->settings->enableExcerpt = $importExcerpt || ($wordLimitEnabled && $trimmingType === 'excerpt');
		$src->settings->whichExcerpt = $importExcerpt ? ExcerptToUse::IMPORT : ExcerptToUse::GENERATE;
		$src->settings->genMissingExcerpt = ( $wordLimitEnabled && $trimmingType === 'excerpt' );
		$src->settings->excerptNumWords = $src->settings->excerptGenNumWords = $wordLimit;
		$src->settings->excerptSuffix = $src->settings->excerptGenSuffix = $trimmingEllipsis ? '...' : '';

		// taxonomy settings

		$srcTaxonomies = isset( $meta['wprss_ftp_taxonomies'] ) ? maybe_unserialize( $meta['wprss_ftp_taxonomies'] ) : array();
		$srcTaxonomies = is_array( $srcTaxonomies ) ? $srcTaxonomies : array();
		$globalTaxonomies = ( ! empty( $this->ftpSettings['taxonomies'] ) && is_array( $this->ftpSettings['taxonomies'] ) )
		? $this->ftpSettings['taxonomies']
		: array();
		$taxonomies = array_merge( $globalTaxonomies, $srcTaxonomies );

		foreach ( $taxonomies as $rule ) {
			$taxonomy = $rule['taxonomy'] ?? '';
			if ( ! empty( $taxonomy ) ) {
				$src->settings->taxonomies[ $taxonomy ] ??= array();
				$src->settings->taxonomies[ $taxonomy ][] = $this->convertTaxonomyRule( $rule );
			}
		}

		// template settings
		$prepend = $meta['wprss_ftp_post_prepend'] ?? '';
		$append = $meta['wprss_ftp_post_append'] ?? '';
		$prependSingle = $meta['wprss_ftp_singular_prepend'] ?? '0';
		$appendSingle = $meta['wprss_ftp_singular_append'] ?? '0';

		$src->settings->enablePreContent = strlen( $prepend ) > 0;
		$src->settings->enablePostContent = strlen( $append ) > 0;
		$src->settings->preContentTemplate = $this->convertTemplate( $prepend );
		$src->settings->postContentTemplate = $this->convertTemplate( $append );
		$src->settings->preContentSingleOnly = Bools::normalize( $prependSingle );
		$src->settings->postContentSingleOnly = Bools::normalize( $appendSingle );

		// attribution settings
		$srcLink = $this->ftpSettings['source_link'] ?? false;
		$srcLinkSingle = $this->ftpSettings['source_link_singular'] ?? '';
		$srcLinkText = $this->ftpSettings['source_link_text'] ?? '';
		$srcLinkPos = $this->ftpSettings['source_link_position'] ?? 'before';
		$src->settings->enableAttribution = Bools::normalize( $srcLink );
		$src->settings->attributionSingleOnly = Bools::normalize( $srcLinkSingle );
		$src->settings->attributionTemplate = $this->convertSourceLink( $srcLinkText );
		$src->settings->attributionPosition = strtolower( $srcLinkPos );

			// Logger::debug( "Source {$src->id} plus is migrated." ); // Removed debug log
		} catch ( \Exception $e ) {
			Logger::error( sprintf(
				"Error migrating plus source settings for source ID %s (Name: %s): %s",
				$src->id,
				$src->name,
				$e->getMessage()
			) );
			// Optionally, return $src without changes or handle more gracefully
		}
		return $src;
	}

	private function convertTaxonomyRule( array $rule ): ?TaxonomyRule {
		$terms = $rule['terms'] ?? '';
		$autoCreate = $rule['auto'] ?? false;

		if ( is_string( $terms ) ) {
			$terms = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
		}
		if ( ! is_array( $terms ) ) {
			$terms = array();
		}

		$subjects = $rule['filter_subject'] ?? array();
		$subjects = is_string( $subjects ) ? explode( ',', $subjects ) : (array) $subjects;

		$keywords = $rule['filter_keywords'] ?? array();
		$keywords = is_string( $keywords ) ? explode( ',', $keywords ) : (array) $keywords;

		$wholeWords = $rule['whole_words'] ?? false;
		$wholeWords = Bools::normalize( $wholeWords );

		$compare = $rule['post_taxonomy_compare_method'] ?? 'all';
		$operatorId = strtolower( $compare ) === 'all'
			? 'strContainsAll'
			: 'strContainsAny';

		$exprs = array();
		foreach ( $subjects as $subject ) {
			$subject = strtolower( $subject );
			if ( $subject !== 'title' && $subject !== 'content' ) {
				continue;
			}

			$exprs[] = new Expression(
				$subject,
				$operatorId,
				array(
					'value' => $keywords,
					'wholeWords' => $wholeWords,
				)
			);
		}

		$import = Bools::normalize( $autoCreate );
		$assign = count( $terms ) > 0;

		$useCondition = count( $exprs ) > 0;
		$conditions = $useCondition ? array( new Condition( false, $exprs ) ) : array();

		return new TaxonomyRule( $import, $assign, $terms, $useCondition, $conditions, null );
	}

	private function convertTemplate( string $template ): string {
		// using the template system itself to translate these tokens
		$renderer = new TokenRenderer(
			array(
				'feed_name' => new TokenType( '', fn () => '{{source_name}}' ),
				'feed_url' => new TokenType( '', fn () => '{{source_url}}' ),
				'post_title' => new TokenType( '', fn () => '{{post_title}}' ),
				'post_url' => new TokenType( '', fn () => '{{post_url}}' ),
				'post_import_date' => new TokenType( '', fn () => '{{post_import_date}}' ),
				'original_post_url' => new TokenType( '', fn () => '{{original_post_url}}' ),
				'post_author_name' => new TokenType( '', fn () => '{{post_author_name}}' ),
				'post_publish_date' => new TokenType( '', fn () => '{{post_publish_date}}' ),
				'post_author_url' => new TokenType( '', fn () => '{{post_author_url}}' ),
				'original_image_url' => new TokenType( '', fn () => '{{original_image_url}}' ),
				'meta' => new TokenType( '', fn () => '{{meta}}' ),
			)
		);
		$newTemplate = $renderer->renderTemplate( $template, null );

		$num = preg_match_all( '/{{(source_)?meta\s*:\s*([^}]*)}}/ix', $newTemplate, $matches );
		if ( $num === false || $num === 0 ) {
			return $newTemplate;
		}

		$fullMatches = $matches[0] ?? array();
		$sourcePrefixes = $matches[1] ?? array();
		$fieldNames = $matches[2] ?? array();

		for ( $i = 0; $i < count( $fieldNames ); $i++ ) {
			$fullMatch = $fullMatches[ $i ] ?? '';
			$isSource = ( $sourcePrefixes[ $i ] ?? '' ) === 'source_';
			$fieldName = $fieldNames[ $i ] ?? '';
			if ( empty( $fullMatch ) || empty( $fieldName ) ) {
				continue;
			}
			if ( $isSource ) {
				$newToken = sprintf( '{{source_meta name="%s" }}', $fieldName );
			} else {
				$newToken = sprintf( '{{meta name="%s" }}', $fieldName );
			}
			$newTemplate = str_replace( $fullMatch, $newToken, $newTemplate );
		}

		return $newTemplate;
	}

	private function convertSourceLink( string $template ): string {
		$result = str_replace( '[source]', '{{source_name}}', $template );
		$star = stripos( $result, '*' );

		if ( $star === false ) {
			return $result;
		}

		$result = preg_replace(
			'/\*\*(.*?)\*\*/',
			'<a href="{{source_url}}" target="_blank">$1</a>',
			$result
		);
		$result = preg_replace(
			'/\*(.*?)\*/',
			'<a href="{{original_post_url}}" target="_blank">$1</a>',
			$result
		);

		return $result;
	}
}
