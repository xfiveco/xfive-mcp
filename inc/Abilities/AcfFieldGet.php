<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AcfFieldGet extends AbilitiesBase {
	/**
	 * Get configuration for the ACF field get ability.
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
		return 'ACF - Field Get';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Read ACF field values. Set object_id to "option" for ACF Options pages (the default), a numeric post ID for post/page/CPT fields, "term_{id}" for taxonomy-term fields, or "user_{id}" for user fields. Omit "fields" to return ALL ACF fields for that target (formatted as ACF returns them); pass an array of field names to return only those. By default image/file/gallery fields are reported in the "media" map as {id,url,filename} so you know the source file when migrating (the raw value stays the attachment ID, site-local); set expand_media=false to skip that lookup. To learn a field group\'s STRUCTURE (sub-fields, types, choices) without values, use xfive-acf-acf-field-schema. Read counterpart to xfive-acf-acf-field-update.';
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
				'object_id'    => array(
					'type'        => array( 'string', 'integer' ),
					'description' => 'The target to read fields from. Use "option" for ACF Options pages, a numeric post ID for post/page fields, "term_{id}" for taxonomy-term fields, or "user_{id}" for user fields.',
					'default'     => 'option',
				),
				'fields'       => array(
					'type'        => 'array',
					'description' => 'Optional array of field names to read. Omit to return all ACF fields for the target.',
					'items'       => array(
						'type' => 'string',
					),
				),
				'expand_media' => array(
					'type'        => 'boolean',
					'description' => 'When true (default), add a "media" map resolving image/file/gallery attachment IDs to {id,url,filename}. Set false to skip the lookup.',
					'default'     => true,
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
				'fields' => array(
					'type'        => 'object',
					'description' => 'Object of field_name => value pairs as ACF returns them.',
				),
				'meta'   => array(
					'type'        => 'object',
					'description' => 'Object of field_name => { type, new_lines, raw, hint } describing how each field was set up. "type" is the ACF field type (e.g. textarea, wysiwyg, image). "new_lines" is the textarea formatting mode (wpautop|br|""). "raw" is given for textarea fields whose stored value contains render HTML, with that formatting reversed to plain text/newlines — use it when copying the field to avoid persisting render markup. "hint" accompanies "raw" and explains why to prefer it.',
				),
				'media'  => array(
					'type'        => 'object',
					'description' => 'Present when expand_media is on. field_name => {id,url,filename} for single image/file fields, or an array of those for gallery/multi fields. Attachment IDs are site-local — use url/filename to migrate.',
				),
				'hint'   => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the ACF field read.
	 *
	 * @param array $args Arguments for reading ACF fields.
	 * @return array|\WP_Error Array with field values on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		if ( ! function_exists( 'get_field' ) ) {
			return new \WP_Error( 'acf_missing', 'Advanced Custom Fields plugin is not active.' );
		}

		$post_id = $args['object_id'] ?? 'option';
		$names   = $args['fields'] ?? array();

		// Validate post_id for non-option targets. ACF accepts string selectors
		// "term_{id}" (taxonomy-term meta) and "user_{id}" (user meta) — pass those
		// through unchanged after confirming the underlying object exists.
		if ( 'option' !== $post_id && 'options' !== $post_id ) {
			if ( is_string( $post_id ) && preg_match( '/^term_(\d+)$/', $post_id, $m ) ) {
				$term_id = (int) $m[1];
				if ( ! get_term( $term_id ) instanceof \WP_Term ) {
					return new \WP_Error( 'not_found', sprintf( 'Term ID %d not found.', $term_id ) );
				}
			} elseif ( is_string( $post_id ) && preg_match( '/^user_(\d+)$/', $post_id, $m ) ) {
				$user_id = (int) $m[1];
				if ( ! get_userdata( $user_id ) ) {
					return new \WP_Error( 'not_found', sprintf( 'User ID %d not found.', $user_id ) );
				}
			} else {
				$post_id = (int) $post_id;
				if ( ! get_post( $post_id ) ) {
					return new \WP_Error( 'not_found', sprintf( 'Post ID %d not found.', $post_id ) );
				}
			}
		}

		$out = array();

		if ( ! empty( $names ) && is_array( $names ) ) {
			foreach ( $names as $name ) {
				$name         = (string) $name;
				$out[ $name ] = get_field( $name, $post_id );
			}
		} else {
			$all = get_fields( $post_id );
			$out = is_array( $all ) ? $all : array();
		}

		$result = array( 'fields' => $out );

		$expand_media = ! isset( $args['expand_media'] ) || ! empty( $args['expand_media'] );
		$media        = array();

		if ( function_exists( 'acf_get_field' ) ) {
			$meta = array();
			foreach ( $out as $name => $value ) {
				$field = acf_get_field( $name );
				if ( ! is_array( $field ) ) {
					continue;
				}

				$type  = $field['type'] ?? '';
				$entry = array( 'type' => $type );

				// Resolve image/file/gallery attachment IDs to {id,url,filename}
				// so the source file is known when migrating (value stays the ID).
				if ( $expand_media && in_array( $type, array( 'image', 'file', 'gallery' ), true ) ) {
					$resolved = $this->resolve_media( $value );
					if ( null !== $resolved ) {
						$media[ $name ] = $resolved;
					}
				}

				if ( 'textarea' === $type ) {
					$new_lines          = $field['new_lines'] ?? '';
					$entry['new_lines'] = $new_lines;

					// If the stored value contains render formatting from new_lines,
					// reverse it so callers can copy the field without persisting markup.
					if ( is_string( $value ) ) {
						$raw = $value;
						if ( 'wpautop' === $new_lines ) {
							$raw = preg_replace( '#</p>\s*<p>#i', "\n\n", $raw );
							$raw = preg_replace( '#</?p[^>]*>#i', '', $raw );
							$raw = str_ireplace( array( '<br />', '<br/>', '<br>' ), '', $raw );
						} elseif ( 'br' === $new_lines ) {
							$raw = preg_replace( '#<br\s*/?>\n?#i', "\n", $raw );
						}
						$raw = trim( $raw );
						if ( $raw !== $value ) {
							$entry['raw']  = $raw;
							$entry['hint'] = sprintf(
								'This textarea is set to new_lines="%s", so its stored value contains render HTML. When copying or re-saving, use "raw" (plain text) instead of "value" to avoid persisting markup that would be double-formatted on output.',
								$new_lines
							);
						}
					}
				}

				$meta[ $name ] = $entry;
			}
			$result['meta'] = $meta;
		}

		if ( $expand_media && ! empty( $media ) ) {
			$result['media'] = $media;
		}

		$result['hint'] = empty( $out )
			? 'No ACF fields returned. The target may have no values set, or get_fields() found no field groups for it. For options pages confirm object_id is "option".'
			: sprintf( '%d field(s) returned. Image/file fields return attachment IDs (local to THIS site). For textarea fields, check meta[name].raw — when present, the stored value contained new_lines render HTML (wpautop/br); copy "raw" instead of "value" to avoid persisting markup.', count( $out ) );

		return $result;
	}

	/**
	 * Resolve an image/file/gallery field value to attachment descriptor(s).
	 *
	 * Handles raw attachment IDs, ACF array/object return formats, and arrays
	 * thereof (gallery). Returns a single {id,url,filename} object, an array of
	 * them, or null when nothing resolvable is present.
	 *
	 * @param mixed $value The ACF field value.
	 * @return array|null Descriptor, list of descriptors, or null.
	 */
	private function resolve_media( $value ) {
		// Gallery / multi: a list. Detect a numerically-indexed array whose
		// items are themselves IDs or attachment arrays.
		if ( is_array( $value ) && array_is_list( $value ) ) {
			$items = array();
			foreach ( $value as $item ) {
				$one = $this->resolve_media( $item );
				if ( null !== $one ) {
					$items[] = $one;
				}
			}
			return empty( $items ) ? null : $items;
		}

		// ACF "array" return format for image/file: an associative array with id.
		if ( is_array( $value ) && isset( $value['id'] ) ) {
			$value = (int) $value['id'];
		}

		// ACF "object" return format: a WP_Post (or compatible object) with ID.
		if ( is_object( $value ) && isset( $value->ID ) ) {
			$value = (int) $value->ID;
		}

		// ACF "url" return format: a string URL — resolve back to its attachment.
		if ( is_string( $value ) && '' !== $value && ! ctype_digit( $value ) ) {
			$value = attachment_url_to_postid( $value );
		}

		$id = (int) ( is_scalar( $value ) ? $value : 0 );
		if ( $id <= 0 || ! get_post( $id ) ) {
			return null;
		}

		$file = get_attached_file( $id );

		return array(
			'id'       => $id,
			'url'      => wp_get_attachment_url( $id ),
			'filename' => $file ? basename( $file ) : '',
		);
	}
}
