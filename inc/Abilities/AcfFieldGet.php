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
		return 'Read ACF field values. For options pages use post_id "option" (the default). For post/page/CPT fields use the numeric post ID. Omit "fields" to return ALL ACF fields for that target (formatted as ACF returns them); pass an array of field names to return only those. Read counterpart to xfive-acf-acf-field-update.';
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
				'post_id' => array(
					'type'        => array( 'string', 'integer' ),
					'description' => 'The post ID to read fields from. Use "option" for ACF Options pages, or a numeric post ID for post/page fields.',
					'default'     => 'option',
				),
				'fields'  => array(
					'type'        => 'array',
					'description' => 'Optional array of field names to read. Omit to return all ACF fields for the target.',
					'items'       => array(
						'type' => 'string',
					),
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

		$post_id = $args['post_id'] ?? 'option';
		$names   = $args['fields'] ?? array();

		// Validate post_id for non-option targets.
		if ( 'option' !== $post_id && 'options' !== $post_id ) {
			$post_id = (int) $post_id;
			if ( ! get_post( $post_id ) ) {
				return new \WP_Error( 'not_found', sprintf( 'Post ID %d not found.', $post_id ) );
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

		if ( function_exists( 'acf_get_field' ) ) {
			$meta = array();
			foreach ( $out as $name => $value ) {
				$field = acf_get_field( $name );
				if ( ! is_array( $field ) ) {
					continue;
				}

				$entry = array( 'type' => $field['type'] ?? '' );

				if ( 'textarea' === ( $field['type'] ?? '' ) ) {
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

		$result['hint'] = empty( $out )
			? 'No ACF fields returned. The target may have no values set, or get_fields() found no field groups for it. For options pages confirm post_id is "option".'
			: sprintf( '%d field(s) returned. Image/file fields return attachment IDs (local to THIS site). For textarea fields, check meta[name].raw — when present, the stored value contained new_lines render HTML (wpautop/br); copy "raw" instead of "value" to avoid persisting markup.', count( $out ) );

		return $result;
	}
}
