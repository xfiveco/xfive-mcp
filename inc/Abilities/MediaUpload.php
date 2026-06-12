<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class MediaUpload extends AbilitiesBase {
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
		return 'Media - Upload';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Bring an EXTERNAL file INTO this site\'s media library and return its attachment ID + URL. Source is outside the library: provide ONE of file_url (remote http/https URL, will be downloaded) OR local_path (absolute filesystem path on this server). Optionally set image metadata on the new attachment: alt, title, caption, description, and post_parent (attach to a post ID). Any file type this site permits is accepted (see get_allowed_mime_types - typically images, video, audio, pdf, etc.; SVG requires a plugin that allows it). Use the returned id as a featured image, ACF media field value, or block attribute. NOTE: to send a file that is ALREADY in this library OUT to another WordPress site (e.g. local -> staging), use Media - Migrate instead.';
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
				'file_url'    => array(
					'type'        => 'string',
					'description' => 'Remote URL to download (http/https). Mutually exclusive with local_path.',
				),
				'local_path'  => array(
					'type'        => 'string',
					'description' => 'Absolute filesystem path on the server. Mutually exclusive with file_url.',
				),
				'alt'         => array(
					'type'        => 'string',
					'description' => 'Optional alt text for the image (_wp_attachment_image_alt).',
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'Optional attachment title. Defaults to the filename.',
				),
				'caption'     => array(
					'type'        => 'string',
					'description' => 'Optional caption (post_excerpt).',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional description (post_content).',
				),
				'post_parent' => array(
					'type'        => 'integer',
					'description' => 'Optional post ID to attach this media to.',
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
				'id'    => array(
					'type'        => 'integer',
					'description' => 'Attachment ID of the uploaded file (use this as the value for media fields, _thumbnail_id, etc.).',
				),
				'url'   => array(
					'type'        => 'string',
					'description' => 'Public URL of the uploaded file (image attachments resolve to "large" size).',
				),
				'error' => array(
					'type'        => 'string',
					'description' => 'Present only on failure; describes what went wrong.',
				),
				'hint'  => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the media upload.
	 *
	 * @param array $args Arguments for uploading a file.
	 * @return array|\WP_Error Array with status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$file_url   = $args['file_url'] ?? '';
		$local_path = $args['local_path'] ?? '';

		if ( $local_path ) {
			$result = $this->upload_from_local_path( $local_path );
		} elseif ( $file_url ) {
			$result = $this->upload_from_url( $file_url );
		} else {
			return array(
				'error' => 'Provide either file_url or local_path.',
				'hint'  => 'Pass file_url for remote downloads (http/https) or local_path for an absolute filesystem path.',
			);
		}

		if ( is_array( $result ) && isset( $result['id'] ) ) {
			$this->apply_metadata( (int) $result['id'], $args );
		}

		return $this->annotate_result( $result );
	}

	/**
	 * Apply optional alt/title/caption/description/post_parent to an attachment.
	 *
	 * @param int   $attachment_id The new attachment ID.
	 * @param array $args          Ability arguments.
	 * @return void
	 */
	private function apply_metadata( int $attachment_id, array $args ): void {
		if ( isset( $args['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $args['alt'] ) );
		}

		$postarr = array();
		if ( isset( $args['title'] ) ) {
			$postarr['post_title'] = (string) $args['title'];
		}
		if ( isset( $args['caption'] ) ) {
			$postarr['post_excerpt'] = (string) $args['caption'];
		}
		if ( isset( $args['description'] ) ) {
			$postarr['post_content'] = (string) $args['description'];
		}
		if ( isset( $args['post_parent'] ) ) {
			$postarr['post_parent'] = (int) $args['post_parent'];
		}

		if ( ! empty( $postarr ) ) {
			$postarr['ID'] = $attachment_id;
			wp_update_post( $postarr );
		}
	}

	/**
	 * Add a hint string to a successful or failed upload result.
	 *
	 * @param array|object $result Result from one of the uploader methods.
	 * @return array|object
	 */
	private function annotate_result( $result ) {
		if ( ! is_array( $result ) ) {
			return $result;
		}

		if ( isset( $result['error'] ) ) {
			$result['hint'] = 'Check the file type is allowed on this site (get_allowed_mime_types), that the file is readable, and under PHP upload limits. For SVG you may need a plugin that allows SVG uploads.';
		} elseif ( isset( $result['id'] ) ) {
			$result['hint'] = sprintf( 'Attachment %d ready. Use as featured image with _thumbnail_id (via xfive-acf-acf-field-update), as ACF media field value (just the integer ID), or as block markup id.', (int) $result['id'] );
		}

		return $result;
	}

	/**
	 * Upload a file from url to the media library.
	 *
	 * @param string $file_url - File URL to upload.
	 *
	 * @return array|object  array with error on error or file data on success
	 */
	private function upload_from_url( string $file_url ): array|object {
		// Require to allow us to use download_url() and wp_handle_sideload() functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Download to temp dir.
		add_filter( 'http_request_args', array( $this, 'allow_local_download_url' ), 10, 2 );
		$temp_file = download_url( $file_url );
		remove_filter( 'http_request_args', array( $this, 'allow_local_download_url' ), 10, 2 );

		if ( is_wp_error( $temp_file ) ) {
			if ( $temp_file->get_error_code() === 'http_request_failed' ) {
				// Bypass http error and download again.
				add_filter( 'https_ssl_verify', '__return_false' );
				add_filter( 'https_local_ssl_verify', '__return_false' );

				$temp_file = download_url( $file_url );

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
		$file_name = basename( wp_parse_url( $file_url, PHP_URL_PATH ) );

		// If filename has no extension, try to determine it from mime type.
		if ( strpos( $file_name, '.' ) === false ) {
			$extension = $this->extension_for_mime( mime_content_type( $temp_file ) );

			if ( $extension ) {
				$file_name .= '.' . $extension;
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

		// Add file into WordPress media library.
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
	 * Upload a file from a local file path to the media library.
	 *
	 * @param string $local_path - Absolute path to a local file.
	 *
	 * @return array|object Array with error on error or file data on success.
	 */
	private function upload_from_local_path( string $local_path ): array|object {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! file_exists( $local_path ) ) {
			return array( 'error' => 'File not found: ' . sanitize_text_field( $local_path ) );
		}

		if ( ! is_readable( $local_path ) ) {
			return array( 'error' => 'File is not readable: ' . sanitize_text_field( $local_path ) );
		}

		$file_name = basename( $local_path );
		$mime_type = mime_content_type( $local_path );

		if ( ! $this->is_allowed_mime( $mime_type ) ) {
			return array( 'error' => 'Unsupported file type: ' . sanitize_text_field( $mime_type ) );
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
	 * Allow download for files with localhost .test domain.
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
