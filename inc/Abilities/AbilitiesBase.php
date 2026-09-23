<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base class for all Abilities.
 *
 * Defines the interface that all ability classes must implement.
 */
abstract class AbilitiesBase {

	/**
	 * Get ability configuration.
	 *
	 * @return array Configuration array.
	 */
	abstract public function get_config(): array;

	/**
	 * Get ability name.
	 *
	 * @return string Ability name.
	 */
	abstract public function get_name(): string;

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	abstract public function get_description(): string;

	/**
	 * Get input schema definition.
	 *
	 * @return array Input schema array.
	 */
	abstract public function get_input_schema(): array;

	/**
	 * Get output schema definition.
	 *
	 * @return array Output schema array.
	 */
	abstract public function get_output_schema(): array;

	/**
	 * Execute the ability callback.
	 *
	 * @param array $args Ability arguments.
	 * @return array|\WP_Error Result array or error object.
	 */
	abstract public function execute_callback( array $args = array() );

	/**
	 * Permission callback to check if user can execute this ability.
	 *
	 * @return bool Whether the user has permission.
	 */
	public function permission_callback(): bool {
		return is_user_logged_in();
	}

	/**
	 * Check whether a mime type is permitted for upload on this site.
	 *
	 * Uses WordPress's own allowlist (get_allowed_mime_types), so it inherits
	 * site settings, multisite restrictions and plugins that enable extra types
	 * such as SVG.
	 *
	 * @param string $mime_type Mime type to check.
	 * @return bool Whether the mime type is allowed.
	 */
	protected function is_allowed_mime( string $mime_type ): bool {
		return in_array( $mime_type, array_values( get_allowed_mime_types() ), true );
	}

	/**
	 * Settings that belong to the network rather than to a site.
	 *
	 * On multisite these are read with get_site_option(), so writing them with
	 * update_option() stores a row nothing reads and reports success. Names that
	 * also exist as a genuine per-site option - admin_email, WPLANG - are
	 * deliberately absent, because writing those per site is correct.
	 *
	 * @var string[]
	 */
	protected const NETWORK_ONLY_OPTIONS = array(
		'active_sitewide_plugins',
		'add_new_users',
		'allowedthemes',
		'banned_email_domains',
		'blog_upload_space',
		'fileupload_maxk',
		'first_comment',
		'first_comment_author',
		'first_comment_url',
		'first_page',
		'first_post',
		'global_terms_enabled',
		'illegal_names',
		'limited_email_domains',
		'menu_items',
		'ms_files_rewriting',
		'registration',
		'registrationnotification',
		'site_admins',
		'site_name',
		'subdomain_install',
		'upload_filetypes',
		'upload_space_check_disabled',
		'welcome_email',
		'welcome_user_email',
	);

	/**
	 * Whether a name is a network setting being addressed on a network.
	 *
	 * @param string $name Option name.
	 * @return bool True when the name must be read and written with the site option functions.
	 */
	protected function is_network_only_option( string $name ): bool {
		return is_multisite() && in_array( $name, self::NETWORK_ONLY_OPTIONS, true );
	}

	/**
	 * Check a pending upload against the limits this site enforces.
	 *
	 * On a network the allowed types, the per-file size and the storage quota are
	 * set once for every site (Network Admin -> Settings -> Network Settings) and
	 * override what a single site allows - a plugin that permits SVG on this site
	 * cannot widen the network list. That is the usual reason an upload which
	 * works on one install fails on another, so the limit is named here rather
	 * than left to a generic failure further down.
	 *
	 * @param string $mime_type Mime type of the file.
	 * @param int    $size      File size in bytes.
	 * @return \WP_Error|null WP_Error when the upload cannot proceed, null when it can.
	 */
	protected function check_upload_limits( string $mime_type, int $size ): ?\WP_Error {
		if ( ! $this->is_allowed_mime( $mime_type ) ) {
			return new \WP_Error(
				'upload_type_not_allowed',
				is_multisite()
					? sprintf(
						'File type %s is not in the allowed upload types for this network. Add its extension at Network Admin -> Settings -> Network Settings -> Upload file types. A site-level plugin cannot override this list.',
						$mime_type
					)
					: sprintf( 'File type %s is not an allowed upload type on this site.', $mime_type )
			);
		}

		if ( ! is_multisite() ) {
			return null;
		}

		$max_k = (int) get_site_option( 'fileupload_maxk', 1500 );

		if ( $max_k > 0 && $size > $max_k * KB_IN_BYTES ) {
			return new \WP_Error(
				'upload_too_large',
				sprintf(
					'File is %d KB and this network allows %d KB per file. Raise it at Network Admin -> Settings -> Network Settings -> Max upload file size.',
					(int) ceil( $size / KB_IN_BYTES ),
					$max_k
				)
			);
		}

		if ( ! is_upload_space_available() ) {
			return new \WP_Error(
				'upload_quota_exceeded',
				sprintf(
					'This site has used its %d MB upload quota. Raise it at Network Admin -> Settings -> Network Settings -> Site upload space, or for this site alone under Network Admin -> Sites.',
					(int) get_site_option( 'blog_upload_space', 100 )
				)
			);
		}

		return null;
	}

	/**
	 * Resolve a file extension for a mime type from WordPress's allowlist.
	 *
	 * @param string $mime_type Mime type to look up.
	 * @return string Extension without leading dot, or empty string if unknown.
	 */
	protected function extension_for_mime( string $mime_type ): string {
		foreach ( get_allowed_mime_types() as $extensions => $mime ) {
			if ( $mime === $mime_type ) {
				// $extensions may be a pipe-delimited list like "jpg|jpeg|jpe".
				return explode( '|', $extensions )[0];
			}
		}

		return '';
	}

	/**
	 * Fields forwarded verbatim to wp_insert_post() / wp_update_post().
	 *
	 * _thumbnail_id, meta_input and tax_input are handled separately because
	 * they need validation or post-insert routing.
	 *
	 * @var string[]
	 */
	protected const POST_PASSTHROUGH_FIELDS = array(
		'post_title',
		'post_content',
		'post_type',
		'post_status',
		'post_excerpt',
		'post_name',
		'post_parent',
		'menu_order',
		'post_date',
		'post_author',
		'comment_status',
		'ping_status',
		'post_password',
	);

	/**
	 * Copy the whitelisted post fields present in $args into a wp_*_post() array.
	 *
	 * @param array    $args   Ability arguments.
	 * @param string[] $fields Field names to forward (defaults to POST_PASSTHROUGH_FIELDS).
	 * @return array Subset of $args keyed by field name.
	 */
	protected function forward_post_fields( array $args, array $fields = self::POST_PASSTHROUGH_FIELDS ): array {
		$out = array();
		foreach ( $fields as $field ) {
			if ( isset( $args[ $field ] ) ) {
				$out[ $field ] = $args[ $field ];
			}
		}
		return $out;
	}

	/**
	 * Apply a _thumbnail_id argument to a post, validating it is an image.
	 *
	 * @param int   $post_id      Target post ID.
	 * @param mixed $thumbnail_id The _thumbnail_id argument value.
	 * @param bool  $allow_remove When true, 0 removes the current thumbnail.
	 * @return string|null Warning note when nothing was set, null on success.
	 */
	protected function apply_featured_image( int $post_id, $thumbnail_id, bool $allow_remove = true ): ?string {
		$thumb_id = (int) $thumbnail_id;

		if ( 0 === $thumb_id ) {
			if ( $allow_remove ) {
				delete_post_thumbnail( $post_id );
			}
			return null;
		}

		if ( wp_attachment_is_image( $thumb_id ) ) {
			set_post_thumbnail( $post_id, $thumb_id );
			return null;
		}

		return sprintf( '_thumbnail_id %d is not a valid image attachment; featured image not set.', $thumb_id );
	}

	/**
	 * Assign tax_input terms to a post explicitly (capability-independent).
	 *
	 * @param int   $post_id   Target post ID.
	 * @param array $tax_input Map of taxonomy => term IDs.
	 * @return string[] Warning notes for taxonomies that could not be assigned.
	 */
	protected function apply_tax_input( int $post_id, array $tax_input ): array {
		$notes = array();
		foreach ( $tax_input as $taxonomy => $terms ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$notes[] = sprintf( 'taxonomy "%s" is not registered; terms not assigned.', $taxonomy );
				continue;
			}
			wp_set_object_terms( $post_id, array_map( 'intval', (array) $terms ), $taxonomy );
		}
		return $notes;
	}

	/**
	 * Normalize a post_status argument: pass "any"/single through, split a
	 * comma-separated list into an array.
	 *
	 * @param mixed $status The post_status argument.
	 * @return string|array Normalized status.
	 */
	protected function parse_status_arg( $status ) {
		$status = $status ?? 'any';
		if ( is_string( $status ) && str_contains( $status, ',' ) ) {
			return array_map( 'trim', explode( ',', $status ) );
		}
		return $status;
	}

	/**
	 * Validate that a taxonomy slug is present and registered.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return \WP_Error|null WP_Error when invalid, null when valid.
	 */
	protected function validate_taxonomy( string $taxonomy ): ?\WP_Error {
		if ( '' === $taxonomy ) {
			return new \WP_Error( 'missing_param', 'taxonomy is required.' );
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'invalid_taxonomy', sprintf( 'Taxonomy "%s" is not registered.', $taxonomy ) );
		}

		return null;
	}
}
