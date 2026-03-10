<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class ImageUpload extends AbilitiesBase {
	/**
	 * Get configuration for the post update ability.
	 *
	 * @return array Empty array as no configuration is needed.
	 */
	public function get_config(): array {
		return array();
	}

	/**
	 * Get the name of the ability.
	 *
	 * @return string The ability name.
	 */
	public function get_name(): string {
		return 'Image - Upload';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Upload an image to the media library. Provide either image_url (remote URL) or local_path (absolute path to a local file).';
	}

	/**
	 * Get the input schema for the ability.
	 *
	 * @return array Schema defining required input parameters.
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'image_url'  => array(
					'type'        => 'string',
					'description' => 'The URL of the image to upload (remote)',
				),
				'local_path' => array(
					'type'        => 'string',
					'description' => 'Absolute path to a local image file to upload',
				),
			),
		);
	}

	/**
	 * Get the output schema for the ability.
	 *
	 * @return array Schema defining the structure of the response.
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'updated' => array(
					'type'        => 'boolean',
					'description' => 'Whether the post was updated successfully',
				),
			),
		);
	}

	/**
	 * Execute the post update.
	 *
	 * @param array $args Arguments for updating a post.
	 * @return array|\WP_Error Array with status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$image_url  = $args['image_url'] ?? '';
		$local_path = $args['local_path'] ?? '';

		if ( $local_path ) {
			return $this->upload_image_from_local_path( $local_path );
		}

		if ( ! $image_url ) {
			return new \WP_Error( 'not_found', 'Provide either image_url or local_path' );
		}

		return $this->upload_image_from_url( $image_url );
	}

	/**
	 * Upload image from url to the media library.
	 *
	 * @param string $image_url - Image URL to upload.
	 *
	 * @return array|object  array with error on error or image data on success
	 */
	private function upload_image_from_url( string $image_url ): array|object {
		// Require to allow us to use download_url() and wp_handle_sideload() functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Download to temp dir.
		add_filter( 'http_request_args', array( $this, 'allow_local_download_url' ), 10, 2 );
		$temp_file = download_url( $image_url );
		remove_filter( 'http_request_args', array( $this, 'allow_local_download_url' ), 10, 2 );

		if ( is_wp_error( $temp_file ) ) {
			if ( $temp_file->get_error_code() === 'http_request_failed' ) {
				// Bypass http error and download again.
				add_filter( 'https_ssl_verify', '__return_false' );
				add_filter( 'https_local_ssl_verify', '__return_false' );

				$temp_file = download_url( $image_url );

				remove_filter( 'https_ssl_verify', '__return_false' );
				remove_filter( 'https_local_ssl_verify', '__return_false' );

				if ( is_wp_error( $temp_file ) ) {
					return array(
						'error' => sanitize_text_field( $temp_file->get_error_message() ),
					);
				}
			} else {
				return array(
					'error' => sanitize_text_field( $temp_file->get_error_message() ),
				);
			}
		}

		// Move the temp file into the uploads directory.
		$file_name = basename( wp_parse_url( $image_url, PHP_URL_PATH ) );

		// If filename has no extension, try to determine it from mime type.
		if ( strpos( $file_name, '.' ) === false ) {
			$mime_type  = mime_content_type( $temp_file );
			$extensions = array(
				'image/jpeg' => 'jpg',
				'image/png'  => 'png',
				'image/gif'  => 'gif',
				'image/webp' => 'webp',
			);

			if ( isset( $extensions[ $mime_type ] ) ) {
				$file_name .= '.' . $extensions[ $mime_type ];
			}
		}

		// Move the temp file into the uploads directory.
		$file = array(
			'name'     => $file_name,
			'type'     => mime_content_type( $temp_file ),
			'tmp_name' => $temp_file,
			'size'     => filesize( $temp_file ),
		);

		$sideload = wp_handle_sideload(
			$file,
			array(
				'test_form' => false, // No needs to check $_POST['action'] parameter as we are not in the admin.
			)
		);

		// @unlink( $temp_file );

		if ( ! empty( $sideload['error'] ) ) {
			return array(
				'error' => sanitize_text_field( $sideload['error'] ),
			);
		}

		// Add image into WordPress media library.
		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $sideload['url'],
				'post_mime_type' => $sideload['type'],
				'post_title'     => basename( $sideload['file'] ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$sideload['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return array(
				'error' => sanitize_text_field( $attachment_id->get_error_message() ),
			);
		}

		// Update medatata, regenerate image sizes.
		require_once ABSPATH . 'wp-admin/includes/image.php';

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $sideload['file'] )
		);

		$image_size = 'large';

		return array(
			'id'  => $attachment_id,
			'url' => wp_get_attachment_image_url( $attachment_id, $image_size ),
		);
	}

	/**
	 * Upload image from a local file path to the media library.
	 *
	 * @param string $local_path - Absolute path to a local image file.
	 *
	 * @return array|object Array with error on error or image data on success.
	 */
	private function upload_image_from_local_path( string $local_path ): array|object {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! file_exists( $local_path ) ) {
			return array( 'error' => 'File not found: ' . sanitize_text_field( $local_path ) );
		}

		if ( ! is_readable( $local_path ) ) {
			return array( 'error' => 'File is not readable: ' . sanitize_text_field( $local_path ) );
		}

		$file_name = basename( $local_path );
		$mime_type = mime_content_type( $local_path );

		$allowed_mime_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $mime_type, $allowed_mime_types, true ) ) {
			return array( 'error' => 'Unsupported image type: ' . sanitize_text_field( $mime_type ) );
		}

		// Copy to a temp file so wp_handle_sideload can move it safely.
		$tmp = wp_tempnam( $file_name );
		if ( ! copy( $local_path, $tmp ) ) {
			return array( 'error' => 'Failed to create temporary copy of the file' );
		}

		$file = array(
			'name'     => $file_name,
			'type'     => $mime_type,
			'tmp_name' => $tmp,
			'size'     => filesize( $tmp ),
		);

		$sideload = wp_handle_sideload(
			$file,
			array( 'test_form' => false )
		);

		if ( ! empty( $sideload['error'] ) ) {
			wp_delete_file( $tmp );
			return array( 'error' => sanitize_text_field( $sideload['error'] ) );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $sideload['url'],
				'post_mime_type' => $sideload['type'],
				'post_title'     => basename( $sideload['file'] ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$sideload['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return array( 'error' => sanitize_text_field( $attachment_id->get_error_message() ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $sideload['file'] )
		);

		return array(
			'id'  => $attachment_id,
			'url' => wp_get_attachment_image_url( $attachment_id, 'large' ),
		);
	}

	/**
	 * Allow download for images with localhost .test domain.
	 *
	 * @param array  $parsed_args - Parsed arguments for the HTTP request.
	 * @param string $url - URL to download.
	 *
	 * @return array - Parsed arguments for the HTTP request.
	 */
	public function allow_local_download_url( $parsed_args, $url ) {
		if ( strpos( $url, '.test' ) !== false ) {
			$parsed_args['reject_unsafe_urls'] = false;
		}

		return $parsed_args;
	}
}
