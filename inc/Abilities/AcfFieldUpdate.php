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
		return 'Update one or more ACF fields. For options pages use post_id "option". For post/page fields use the numeric post ID. Supports all ACF field types — pass the value in the format ACF expects (string for text/URL, integer for image/file attachment IDs, array for repeaters/galleries, object for groups/links).';
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
					'description' => 'The post ID to update fields on. Use "option" for ACF Options pages, or a numeric post ID for post/page fields.',
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

		$post_id = $args['post_id'] ?? 'option';
		$fields  = $args['fields'] ?? array();

		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return new \WP_Error( 'missing_param', 'fields must be a non-empty object of field_name => value pairs.' );
		}

		// Validate post_id for non-option targets.
		if ( 'option' !== $post_id && 'options' !== $post_id ) {
			$post_id = (int) $post_id;
			if ( ! get_post( $post_id ) ) {
				return new \WP_Error( 'not_found', sprintf( 'Post ID %d not found.', $post_id ) );
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
