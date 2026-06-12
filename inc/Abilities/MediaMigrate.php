<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class MediaMigrate extends AbilitiesBase {
	/**
	 * Get configuration for the media migrate ability.
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
		return 'Media - Migrate';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Push an existing media-library file (image or video) from THIS site to a remote WordPress site (e.g. local -> staging), server-to-server, without sending file bytes through the agent. Runs on the source site: reads the attachment from disk and POSTs the file to the remote site\'s built-in REST media endpoint (wp/v2/media) authenticated with an Application Password. When migrating by attachment_id, the source attachment\'s alt text, title, caption and description are carried over to the remote automatically (override any of them with the alt/title/caption/description args). Returns the REMOTE attachment id + url. Configure the remote target ONCE in wp-config.php on this (source) site via the constants XFIVE_MCP_REMOTE_URL (e.g. https://staging.example.com), XFIVE_MCP_REMOTE_USER and XFIVE_MCP_REMOTE_APP_PASSWORD, then call this tool with just attachment_id. The remote site must be reachable over HTTP from this server. Large files (e.g. video) are sent in a single request and are subject to the remote server\'s upload_max_filesize / post_max_size / memory_limit. Any file type this site permits is accepted (see get_allowed_mime_types). Pass attachment_id (preferred) or source_path (absolute path on this server). NOTE: to bring an EXTERNAL file (remote URL or local path) INTO this site\'s library, use Media - Upload instead.';
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
				'attachment_id' => array(
					'type'        => 'integer',
					'description' => 'Attachment ID on THIS site to push to the remote. Preferred. Mutually exclusive with source_path.',
				),
				'source_path'   => array(
					'type'        => 'string',
					'description' => 'Absolute filesystem path on THIS server to a media file, used when there is no attachment_id. Mutually exclusive with attachment_id.',
				),
				'filename'      => array(
					'type'        => 'string',
					'description' => 'Optional filename to use on the remote site. Defaults to the source file basename.',
				),
				'alt'           => array(
					'type'        => 'string',
					'description' => 'Optional alt text for the remote attachment. Overrides the source attachment\'s alt.',
				),
				'title'         => array(
					'type'        => 'string',
					'description' => 'Optional title for the remote attachment. Overrides the source title.',
				),
				'caption'       => array(
					'type'        => 'string',
					'description' => 'Optional caption for the remote attachment. Overrides the source caption.',
				),
				'description'   => array(
					'type'        => 'string',
					'description' => 'Optional description for the remote attachment. Overrides the source description.',
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
				'remote_id'  => array(
					'type'        => 'integer',
					'description' => 'Attachment ID created on the REMOTE site. Use this as the value for remote media fields, _thumbnail_id, etc.',
				),
				'remote_url' => array(
					'type'        => 'string',
					'description' => 'Public URL of the uploaded file on the remote site.',
				),
				'error'      => array(
					'type'        => 'string',
					'description' => 'Present only on failure; describes what went wrong.',
				),
				'hint'       => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the media push.
	 *
	 * @param array $args Arguments for migrating a media file.
	 * @return array|\WP_Error Array with remote attachment data on success, error array on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$source = $this->resolve_source( $args );
		if ( isset( $source['error'] ) ) {
			return $source;
		}

		$credentials = $this->resolve_credentials();
		if ( isset( $credentials['error'] ) ) {
			return $credentials;
		}

		return $this->push_to_remote(
			$source['path'],
			$args['filename'] ?? basename( $source['path'] ),
			$credentials,
			$this->resolve_metadata( $args )
		);
	}

	/**
	 * Resolve the local file path from attachment_id or source_path.
	 *
	 * @param array $args Ability arguments.
	 * @return array Array with 'path' on success or 'error'/'hint' on failure.
	 */
	private function resolve_source( array $args ): array {
		$attachment_id = isset( $args['attachment_id'] ) ? (int) $args['attachment_id'] : 0;
		$source_path   = $args['source_path'] ?? '';

		if ( $attachment_id ) {
			$path = get_attached_file( $attachment_id );

			if ( ! $path ) {
				return array(
					'error' => sprintf( 'No file found for attachment %d on this site.', $attachment_id ),
					'hint'  => 'Confirm the attachment_id exists in this site\'s media library.',
				);
			}

			$source_path = $path;
		}

		if ( ! $source_path ) {
			return array(
				'error' => 'Provide attachment_id or source_path.',
				'hint'  => 'attachment_id is preferred; source_path is an absolute path on this server.',
			);
		}

		if ( ! file_exists( $source_path ) || ! is_readable( $source_path ) ) {
			return array(
				'error' => 'Source file is missing or not readable: ' . sanitize_text_field( $source_path ),
			);
		}

		$mime_type = mime_content_type( $source_path );

		if ( ! $this->is_allowed_mime( (string) $mime_type ) ) {
			return array(
				'error' => 'Unsupported file type: ' . sanitize_text_field( (string) $mime_type ),
			);
		}

		return array( 'path' => $source_path );
	}

	/**
	 * Resolve the attachment metadata to send to the remote: the source
	 * attachment's alt/title/caption/description (when migrating by ID),
	 * overridden by any explicit args. Returns only the keys that have a value.
	 *
	 * @param array $args Ability arguments.
	 * @return array REST media fields (alt_text/title/caption/description).
	 */
	private function resolve_metadata( array $args ): array {
		$meta = array(
			'title'       => '',
			'caption'     => '',
			'description' => '',
			'alt_text'    => '',
		);

		$attachment_id = isset( $args['attachment_id'] ) ? (int) $args['attachment_id'] : 0;
		$att           = $attachment_id ? get_post( $attachment_id ) : null;
		if ( $att ) {
			$meta['title']       = $att->post_title;
			$meta['caption']     = $att->post_excerpt;
			$meta['description'] = $att->post_content;
			$meta['alt_text']    = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		}

		// Explicit overrides. Track which keys the caller set so an intentional
		// "" (clear the field on the remote) is preserved past the empty-drop.
		$explicit = array();
		$overrides = array(
			'title'       => 'title',
			'caption'     => 'caption',
			'description' => 'description',
			'alt'         => 'alt_text',
		);
		foreach ( $overrides as $arg_key => $meta_key ) {
			if ( isset( $args[ $arg_key ] ) ) {
				$meta[ $meta_key ]   = (string) $args[ $arg_key ];
				$explicit[ $meta_key ] = true;
			}
		}

		// Drop empty fields we did NOT explicitly set, so we don't overwrite
		// remote defaults with blanks — but keep an explicit "" override.
		return array_filter(
			$meta,
			static function ( $v, $k ) use ( $explicit ) {
				return '' !== $v || isset( $explicit[ $k ] );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Resolve remote credentials from wp-config constants.
	 *
	 * @return array Array with url/user/password on success or 'error'/'hint' on failure.
	 */
	private function resolve_credentials(): array {
		$url      = defined( 'XFIVE_MCP_REMOTE_URL' ) ? XFIVE_MCP_REMOTE_URL : '';
		$user     = defined( 'XFIVE_MCP_REMOTE_USER' ) ? XFIVE_MCP_REMOTE_USER : '';
		$password = defined( 'XFIVE_MCP_REMOTE_APP_PASSWORD' ) ? XFIVE_MCP_REMOTE_APP_PASSWORD : '';

		if ( ! $url || ! $user || ! $password ) {
			return array(
				'error' => 'Remote target is not configured.',
				'hint'  => 'Define XFIVE_MCP_REMOTE_URL, XFIVE_MCP_REMOTE_USER and XFIVE_MCP_REMOTE_APP_PASSWORD in wp-config.php on this (source) site. The Application Password is created on the remote user\'s profile screen.',
			);
		}

		return array(
			'url'      => untrailingslashit( esc_url_raw( $url ) ),
			'user'     => $user,
			'password' => $password,
		);
	}

	/**
	 * Read a local file via the WP filesystem API.
	 *
	 * @param string $path Absolute local file path.
	 * @return string|false File contents or false on failure.
	 */
	private function read_file( string $path ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return false;
		}

		return $wp_filesystem->get_contents( $path );
	}

	/**
	 * POST the file to the remote site's REST media endpoint.
	 *
	 * @param string $path        Absolute local file path.
	 * @param string $filename    Filename to use on the remote site.
	 * @param array  $credentials Resolved remote credentials.
	 * @param array  $meta        REST media fields to set after upload (alt_text/title/caption/description).
	 * @return array Array with remote_id/remote_url on success or error on failure.
	 */
	private function push_to_remote( string $path, string $filename, array $credentials, array $meta = array() ): array {
		$body = $this->read_file( $path );

		if ( false === $body ) {
			return array( 'error' => 'Failed to read source file: ' . sanitize_text_field( $path ) );
		}

		$endpoint = $credentials['url'] . '/wp-json/wp/v2/media';
		$auth     = 'Basic ' . base64_encode( $credentials['user'] . ':' . $credentials['password'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic Auth header for Application Password.

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 300,
				'headers' => array(
					'Authorization'       => $auth,
					'Content-Type'        => mime_content_type( $path ),
					'Content-Disposition' => 'attachment; filename="' . sanitize_file_name( $filename ) . '"',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => sanitize_text_field( $response->get_error_message() ),
				'hint'  => 'Check that the remote site is reachable from this server and XFIVE_MCP_REMOTE_URL is correct.',
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : 'HTTP ' . $status;

			return array(
				'error' => sanitize_text_field( $message ),
				'hint'  => ( 401 === $status || 403 === $status )
					? 'Authentication failed. Verify XFIVE_MCP_REMOTE_USER / XFIVE_MCP_REMOTE_APP_PASSWORD and that the user can upload_files on the remote site.'
					: ( 413 === $status
						? 'File too large for the remote server. Raise upload_max_filesize / post_max_size on the remote, or transfer the file another way.'
						: 'Remote rejected the upload (HTTP ' . (int) $status . ').' ),
			);
		}

		if ( ! is_array( $data ) || empty( $data['id'] ) ) {
			return array( 'error' => 'Remote response did not include an attachment id.' );
		}

		$remote_id   = (int) $data['id'];
		$meta_status = $this->push_metadata( $endpoint . '/' . $remote_id, $auth, $meta );

		$hint = sprintf( 'Remote attachment %d created. Use this id as a featured image, ACF media field value, or block markup id ON THE REMOTE SITE.', $remote_id );
		if ( 'skipped' !== $meta_status ) {
			$hint .= ' ' . ( 'ok' === $meta_status
				? 'Carried over alt/title/caption/description.'
				: 'NOTE: file uploaded but metadata (alt/title/caption/description) could not be set on the remote — set it manually.' );
		}

		return array(
			'remote_id'  => $remote_id,
			'remote_url' => isset( $data['source_url'] ) ? esc_url_raw( $data['source_url'] ) : '',
			'hint'       => $hint,
		);
	}

	/**
	 * Send a follow-up request to set attachment metadata on the remote.
	 *
	 * @param string $endpoint Remote media item endpoint (…/wp/v2/media/{id}).
	 * @param string $auth     Authorization header value.
	 * @param array  $meta     REST fields to set (alt_text/title/caption/description).
	 * @return string "ok", "failed", or "skipped" (nothing to set).
	 */
	private function push_metadata( string $endpoint, string $auth, array $meta ): string {
		if ( empty( $meta ) ) {
			return 'skipped';
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => $auth,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $meta ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'failed';
		}

		$status = wp_remote_retrieve_response_code( $response );

		return ( $status >= 200 && $status < 300 ) ? 'ok' : 'failed';
	}
}
