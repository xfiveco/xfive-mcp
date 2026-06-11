<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AcfFieldUpdate extends AbilitiesBase {
	/**
	 * Get configuration for the ACF field update ability.
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
		return 'ACF - Field Update';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Update one or more ACF fields. Set object_id to "option" for ACF Options pages, a numeric post ID for post/page fields, "term_{id}" (e.g. "term_47") for taxonomy-term fields, or "user_{id}" (e.g. "user_5") for user fields. Supports all ACF field types — pass the value in the format ACF expects (string for text/URL, integer for image/file attachment IDs, array for repeaters/galleries, object for groups/links).';
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
				'object_id' => array(
					'type'        => array( 'string', 'integer' ),
					'description' => 'The target to update fields on. Use "option" for ACF Options pages, a numeric post ID for post/page fields, "term_{id}" for taxonomy-term fields, or "user_{id}" for user fields.',
					'default'     => 'option',
				),
				'fields'  => array(
					'type'        => 'object',
					'description' => 'Object of field_name => value pairs to update. Values should match the ACF field type: string for text/textarea/URL/email/WYSIWYG, number for number/range, boolean for true_false, integer for image/file (attachment ID), array of integers for gallery, array of objects for repeater, object for group/link, post ID(s) for relationship/post_object, term ID(s) for taxonomy.',
				),
			),
			'required'   => array( 'fields' ),
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
					'type'        => 'array',
					'description' => 'List of field names that were successfully updated.',
					'items'       => array(
						'type' => 'string',
					),
				),
				'failed'  => array(
					'type'        => 'array',
					'description' => 'List of field names that failed to update.',
					'items'       => array(
						'type' => 'string',
					),
				),
				'hint'    => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the ACF field update.
	 *
	 * @param array $args Arguments for updating ACF fields.
	 * @return array|\WP_Error Array with results on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		if ( ! function_exists( 'update_field' ) ) {
			return new \WP_Error( 'acf_missing', 'Advanced Custom Fields plugin is not active.' );
		}

		$post_id = $args['object_id'] ?? 'option';
		$fields  = $args['fields'] ?? array();

		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return new \WP_Error( 'missing_param', 'fields must be a non-empty object of field_name => value pairs.' );
		}

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

		$updated = array();
		$failed  = array();

		foreach ( $fields as $field_name => $value ) {
			$result = update_field( $field_name, $value, $post_id );

			if ( $result ) {
				$updated[] = $field_name;
			} else {
				$failed[] = $field_name;
			}
		}

		$result = array(
			'updated' => $updated,
			'failed'  => $failed,
		);

		if ( ! empty( $failed ) ) {
			$result['hint'] = 'For failed fields: confirm the field exists on this post type (check ACF field group location rules), use the field NAME (not key), and that the value matches the field type (image=int ID, repeater=array, link={url,title,target}). update_field returns false when the value is unchanged from the existing one — that also lands in "failed".';
		}

		return $result;
	}
}
