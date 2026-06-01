<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class ImageUploadData extends AbilitiesBase {
	/**
	 * Get configuration for the image upload data ability.
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
		return 'Image - Upload Data';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Upload an image to the WordPress media library from raw base64-encoded bytes. Use this when the file exists ONLY on the caller\'s machine (not on the server and not at a URL the server can reach) — e.g. migrating a local image to a remote site. Provide filename (with extension) and data (base64 of the file bytes; a data: URI prefix is also accepted). Allowed types: jpeg, png, gif, webp, svg. Keep payloads small (logos/icons); large files may exceed PHP post_max_size/memory limits — prefer xfive-images-image-upload with image_url for those. Returns the attachment ID + URL.';
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
				'filename' => array(
					'type'        => 'string',
					'description' => 'File name including extension (e.g. "hero-image.png"). The extension should match the actual image data.',
				),
				'data'     => array(
					'type'        => 'string',
					'description' => 'Base64-encoded file bytes. A "data:image/png;base64,..." URI prefix is accepted and stripped automatically.',
				),
			),
			'required'   => array( 'filename', 'data' ),
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
					'description' => 'Attachment ID of the uploaded image (use this as the value for image fields, _thumbnail_id, etc.).',
				),
				'url'   => array(
					'type'        => 'string',
					'description' => 'Public URL of the uploaded image at "large" size.',
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
	 * Execute the upload from base64 data.
	 *
	 * @param array $args Arguments for the upload.
	 * @return array|\WP_Error Array with status on success, error array on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$filename = sanitize_file_name( $args['filename'] ?? '' );
		$data     = $args['data'] ?? '';

		if ( '' === $filename || '' === $data ) {
			return array(
				'error' => 'Provide both filename and data.',
				'hint'  => 'filename must include an extension (e.g. "logo.png"); data must be base64-encoded file bytes.',
			);
		}

		// Strip an optional data URI prefix ("data:image/png;base64,").
		if ( strpos( $data, 'base64,' ) !== false ) {
			$data = substr( $data, strpos( $data, 'base64,' ) + 7 );
		}
		$data = trim( $data );

		$decoded = base64_decode( $data, true );
		if ( false === $decoded || '' === $decoded ) {
			return array(
				'error' => 'data is not valid base64.',
				'hint'  => 'Encode the raw file bytes with standard base64 (no chunking required). A leading data: URI is allowed.',
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Write decoded bytes to a temp file so wp_handle_sideload can move it.
		$tmp = wp_tempnam( $filename );
		if ( ! $tmp || false === file_put_contents( $tmp, $decoded ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( $tmp ) {
				wp_delete_file( $tmp );
			}
			return array( 'error' => 'Failed to write temporary file.' );
		}

		$mime_type          = mime_content_type( $tmp );
		$allowed_mime_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml' );
		if ( ! in_array( $mime_type, $allowed_mime_types, true ) ) {
			wp_delete_file( $tmp );
			return array(
				'error' => 'Unsupported image type: ' . sanitize_text_field( $mime_type ),
				'hint'  => 'Allowed: jpeg, png, gif, webp, svg. The decoded bytes did not match an allowed image type — check the source file and that data was not corrupted in transit.',
			);
		}

		$file = array(
			'name'     => $filename,
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
			return array(
				'error' => sanitize_text_field( $sideload['error'] ),
				'hint'  => 'wp_handle_sideload rejected the file. For SVG the site needs a plugin that whitelists SVG in get_allowed_mime_types().',
			);
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
			return array(
				'error' => is_wp_error( $attachment_id ) ? sanitize_text_field( $attachment_id->get_error_message() ) : 'Failed to insert attachment.',
			);
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $sideload['file'] )
		);

		return array(
			'id'   => $attachment_id,
			'url'  => wp_get_attachment_image_url( $attachment_id, 'large' ),
			'hint' => sprintf( 'Attachment %d ready. Use as featured image with _thumbnail_id (via xfive-acf-acf-field-update), as ACF image field value (just the integer ID), or as block markup id.', (int) $attachment_id ),
		);
	}
}
