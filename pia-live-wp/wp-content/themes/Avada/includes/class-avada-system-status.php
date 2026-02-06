<?php
/**
 * Various helper methods for Avada's System Status page.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      5.6
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Various helper methods for Avada.
 *
 * @since 5.6
 */
class Avada_System_Status {

	/**
	 * The class constructor
	 *
	 * @access public
	 */
	public function __construct() {

		// Check update server API status.
		add_action( 'wp_ajax_fusion_check_api_status', [ $this, 'check_api_status' ] );

		// Re-create Avada Forms DB tables.
		add_action( 'wp_ajax_fusion_create_forms_tables', [ $this, 'create_forms_tables' ] );

		// Copy multisite global options.
		add_action( 'wp_ajax_awb_copy_multisite_global_options', [ $this, 'copy_multisite_global_options' ] );      

		// Convert images in media library.
		add_action( 'wp_ajax_awb_convert_images_in_media_library', [ $this, 'convert_images_in_media_library' ] );


	}

	/**
	 * AJAX callback method, used to check various APIs status.
	 *
	 * @access public
	 */
	public function check_api_status() {

		if ( ! isset( $_GET['api_type'] ) || ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			echo wp_json_encode(
				[
					'code'         => 200,
					'message'      => __( 'API type missing.', 'Avada' ),
					'api_response' => '',
				]
			);
			die();
		}

		$envato_string = '';
		$api_type      = trim( sanitize_text_field( wp_unslash( $_GET['api_type'] ) ) );
		$api_response  = [];
		$response      = [
			'code'         => 200,
			'message'      => __( 'Tested API is working properly.', 'Avada' ),
			'api_response' => '',
		];

		if ( 'tf_updates' === $api_type ) {
			$api_response     = $this->check_tf_updates_status();
			$response['code'] = (int) trim( wp_remote_retrieve_response_code( $api_response ) );
		}

		if ( 'envato' === $api_type ) {
			$api_response = $this->check_envato_status( true );

			if ( is_wp_error( $api_response ) ) {
				$response['code'] = (int) trim( $api_response->get_error_code() );
				$envato_string    = str_replace( [ 'Unauthorized', 'Forbidden' ], '<br />Invalid Token', $api_response->get_error_message() );
			} elseif ( isset( $api_response['headers_data'] ) ) {
				$envato_string       = $api_response['headers_data'];
				$response['message'] = $response['message'] . ' ' . $envato_string;
			}
		}

		// Serialize whole array for easier debugging.
		$response['api_response'] = esc_textarea( maybe_serialize( $api_response ) );
		if ( 401 === $response['code'] ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Server responded with unauthorized response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 3 === (int) ( $response['code'] / 100 ) ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Server responded with redirection response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 4 === (int) ( $response['code'] / 100 ) ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Error occured while checking API status. Response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 5 === (int) ( $response['code'] / 100 ) ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Internal server error occured while checking API status. Response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		} elseif ( 200 !== $response['code'] ) {
			/* translators: HTTP response code */
			$response['message'] = sprintf( __( 'Something went wrong while checking API status. Response code: %1$s. %2$s', 'Avada' ), $response['code'], $envato_string );
		}

		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Helper method, pings ThemeFusion server.
	 *
	 * @access private
	 * @return array wp_remote_get response.
	 */
	private function check_tf_updates_status() {
		return wp_remote_get( Fusion_Patcher_Client::$remote_patches_uri );
	}

	/**
	 * Helper method, pings Envato server.
	 *
	 * @access private
	 * @param bool $headers_data Set to true if response headers should be provided.
	 * @return mixed array|WP_Error Depending on server response.
	 */
	private function check_envato_status( $headers_data = false ) {
		return Avada()->registration->envato_api()->request( 'https://api.envato.com/v2/market/buyer/download?item_id=2833226', [ 'headers_data' => $headers_data ] );
	}

	/**
	 * Ajax callback for creating Avada Forms database tables.
	 *
	 * @access public
	 */
	public function create_forms_tables() {

		$response = [ 'message' => __( 'Creating database tables failed.' ) ];

		if ( ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			$response = [ 'message' => __( 'Security check failed.' ) ];
		}

		// Fusion Builder is active and Forms are enabled.
		if ( class_exists( 'Fusion_Form_Builder' ) && Fusion_Form_Builder::is_enabled() ) {

			// Include Form Installer.
			if ( ! class_exists( 'Fusion_Form_DB_Install' ) ) {
				include_once FUSION_BUILDER_PLUGIN_DIR . 'inc/class-fusion-form-db-install.php';
			}

			$fusion_form_db_install = new Fusion_Form_DB_Install();
			$fusion_form_db_install->create_tables();

			$response = [ 'message' => __( 'Database tables are created successfully.' ) ];
		}

		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Copy Avada Global Options from main site to all sites across the multisite install.
	 *
	 * @since 7.11.12
	 * @access public
	 * @return void
	 */
	public function copy_multisite_global_options() {
		$response = [ 'message' => __( 'This is not a multisite.' ) ];

		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			$response = [ 'message' => __( 'Security check failed.' ) ];
		}       

		if ( is_multisite() ) {
			$main_site_id      = get_main_site_id();
			$main_site_options = get_blog_option( $main_site_id, 'fusion_options', [] );
			$sites             = get_sites();

			foreach ( $sites as $site ) {
				$site_id = $site->blog_id;
				if ( $site_id !== $main_site_id ) {
					update_blog_option( $site->blog_id, 'fusion_options', $main_site_options );
				}
			}
			
			$response = [ 'message' => __( 'Global options were successfully copied across the network.' ) ];
		}

		echo wp_json_encode( $response );
		die();      
	}

	/**
	 * Converts images in media library in batches to new image formats.
	 *
	 * @since 7.14.0
	 * @access public
	 * @return void
	 */
	public static function convert_images_in_media_library() {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'fusion_system_status_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
	
		$batch_size           = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 10;
		$format               = Fusion_Images::get_target_format();
		$mime                 = 'image/' . $format;
		$fusion_settings      = awb_get_fusion_settings();
		$keep_original_images = 'enable' === $fusion_settings->get( 'keep_original_images' );
		$upload_dir           = wp_get_upload_dir();

		if ( ! isset( $upload_dir['basedir'] ) ) {
			wp_send_json_error( 'Could not access upload folder.' );
		}

		if ( ! $format ) {
			wp_send_json_error( 'Target mime type is unsupported.' );
		}

		$args = [
			'post_type'      => 'attachment',
			'post_mime_type' => 'image/webp' === $mime ? [ 'image/jpeg', 'image/jpg', 'image/png' ] : [ 'image/jpeg', 'image/jpg', 'image/png', 'image/webp' ],
			'posts_per_page' => $batch_size,
			'post_status'    => 'inherit',
		];
		$query = new WP_Query( $args );
	
		if ( ! $query->have_posts() ) {
			wp_send_json_success( [
				'finished'  => true,
				'processed' => 0,
			] );
		}

		$processed = 0;
		foreach ( $query->posts as $attachment ) {
			$file = get_attached_file( $attachment->ID );
			if ( ! $file || ! file_exists( $file ) ) {
				continue;
			}

			$original_mime = get_post_mime_type( $attachment->ID );

			// Can happen in case of webP.
			if ( $mime === $original_mime ) {
				continue;
			}

			$abs_dir_path           = path_join( $upload_dir['basedir'], dirname( $file ) );
			$metadata               = wp_get_attachment_metadata( $attachment->ID );
			$size_data              = [];
			$size_data['size_name'] = 'full';
			$size_data['width']     = $metadata['width'] ? $metadata['width'] : 0;
			$size_data['height']    = $metadata['height'] ? $metadata['height'] : 0;
			$size_data['crop']      = false;
	
			// Convert original
			$new_file = Fusion_Images::convert_image_to_modern_format( $attachment->ID, $size_data, $mime );
	
			if ( is_array( $new_file ) ) {

				// Make sure the custom "sources" index is set and an array.
				$metadata['sources'] = ! isset( $metadata['sources'] ) || ! is_array( $metadata['sources'] ) ? [] : $metadata['sources'];

				// Set the original mime to sources.
				$metadata['sources'][ $original_mime ] = [
					'file'     => wp_basename( $file ),
					'filesize' => $metadata['filesize'],
				];

				$metadata['original_image'] = isset( $metadata['original_image'] ) ? $metadata['original_image'] : wp_basename( $file );

				// Always store the modern format mime.
				$metadata['sources'][ $mime ] = [
					'file'     => $new_file['file'],
					'filesize' => $new_file['filesize'],
				];

				// Update the attached file.
				update_attached_file( $attachment->ID, $new_file['path'] );

				// Update the MIME type.
				wp_update_post( [
					'ID'             => $attachment->ID,
					'post_mime_type' => $mime,
				] );

				// Update the file attribute in metadata.
				$metadata['file'] = _wp_relative_upload_path( $new_file['path'] );

			}
	
       		 // Convert each registered size.
			if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$registered_subsizes = wp_get_registered_image_subsizes();
				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					$sub_size_data              = [];
					$sub_size_data['size_name'] = $size_name;
					$sub_size_data['width']     = $registered_subsizes[ $size_name ]['width'] ?? $size_data['width'] ?? 0;
					$sub_size_data['height']    = $registered_subsizes[ $size_name ]['height'] ?? $size_data['height'] ?? 0;
					$sub_size_data['crop']      = $registered_subsizes[ $size_name ]['crop'] ?? false;
		
					// Convert original
					$new_file = Fusion_Images::convert_image_to_modern_format( $attachment->ID, $sub_size_data, $mime );

					if ( is_array( $new_file ) ) {

						// Make sure the custom "sources" index is set and an array.
						$size_data['sources'] = ! isset( $size_data['sources'] ) || ! is_array( $size_data['sources'] ) ? [] : $size_data['sources'];

						// Set the original mime to sources if we keep the files.
						if ( $keep_original_images ) {
							$size_data['sources'][ $original_mime ] = [
								'file'     => $size_data['file'],
								'filesize' => $size_data['filesize'],
							];
						} else {
							$abs_file_path = path_join( $abs_dir_path, $size_data['file'] );

							// Remove the original source file.
							if ( file_exists( $abs_file_path ) ) {
								wp_delete_file_from_directory( $abs_file_path, $abs_dir_path );
							}
						}

						// Update the size metadata.
						$size_data['file']      = $new_file['file'];
						$size_data['filesize']  = $new_file['filesize'];
						$size_data['mime-type'] = $mime;

						// Always store the modern format mime.
						$size_data['sources'][ $mime ] = [
							'file'     => $new_file['file'],
							'filesize' => $new_file['filesize'],
						];

						$metadata['sizes'][ $size_name ] = $size_data;
	
					}
				}
			}

			wp_update_attachment_metadata( $attachment->ID, $metadata );
			$processed++;
		}

		wp_send_json_success( [
			'finished'    => false,
			'processed'   => $processed,
			'file'        => $file,
		] );
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
