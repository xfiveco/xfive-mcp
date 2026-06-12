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
		return 'Update one or more ACF fields. Set object_id to "option" for ACF Options pages, a numeric post ID for post/page fields, "term_{id}" (e.g. "term_47") for taxonomy-term fields, or "user_{id}" (e.g. "user_5") for user fields. Supports all ACF field types — pass the value in the format ACF expects (string for text/URL, integer for image/file attachment IDs, array for repeaters/galleries, object for groups/links). Returns "values" (read-after-write of each field via get_field) for verification; a field whose stored value already equaled the new value counts as updated, not failed.';
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
					'description' => 'List of field names that genuinely failed to update (excludes "value unchanged" cases, which count as updated).',
					'items'       => array(
						'type' => 'string',
					),
				),
				'values'  => array(
					'type'        => 'object',
					'description' => 'Read-after-write: field_name => the value ACF returns now (get_field), for verification.',
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
		$values  = array();

		foreach ( $fields as $field_name => $value ) {
			$result = update_field( $field_name, $value, $post_id );

			// Read-after-write. update_field returns false both on genuine
			// failure AND when the value was already identical — disambiguate
			// by comparing the stored value to what we tried to set.
			$stored                = get_field( $field_name, $post_id );
			$values[ $field_name ] = $stored;

			if ( $result || $this->values_match( $stored, $value ) ) {
				$updated[] = $field_name;
			} else {
				$failed[] = $field_name;
			}
		}

		$result = array(
			'updated' => $updated,
			'failed'  => $failed,
			'values'  => $values,
		);

		if ( ! empty( $failed ) ) {
			$result['hint'] = 'For failed fields: confirm the field exists on this post type (check ACF field group location rules — see xfive-acf-acf-field-schema), use the field NAME (not key), and that the value matches the field type (image=int ID, repeater=array, link={url,title,target}). Check "values" for what is actually stored now.';
		}

		return $result;
	}

	/**
	 * Whether the stored value matches the value we attempted to set.
	 *
	 * Tolerant by design: ACF may return a media/relationship field in a
	 * different shape than it was written (e.g. an attachment ID written in,
	 * an array read back). Normalize both sides to attachment/object IDs
	 * before comparing, falling back to a loose scalar compare.
	 *
	 * @param mixed $stored  Value read back via get_field().
	 * @param mixed $written Value passed to update_field().
	 * @return bool
	 */
	private function values_match( $stored, $written ): bool {
		$norm = static function ( $v ) {
			if ( is_array( $v ) ) {
				// Single media/object array -> its id.
				if ( isset( $v['id'] ) || isset( $v['ID'] ) ) {
					return (int) ( $v['id'] ?? $v['ID'] );
				}
				// List -> list of ids/scalars.
				return array_map(
					static function ( $item ) {
						if ( is_array( $item ) && ( isset( $item['id'] ) || isset( $item['ID'] ) ) ) {
							return (int) ( $item['id'] ?? $item['ID'] );
						}
						if ( is_object( $item ) && isset( $item->ID ) ) {
							return (int) $item->ID;
						}
						return is_scalar( $item ) ? (string) $item : $item;
					},
					$v
				);
			}
			if ( is_object( $v ) && isset( $v->ID ) ) {
				return (int) $v->ID;
			}
			return $v;
		};

		$a = $norm( $stored );
		$b = $norm( $written );

		if ( is_array( $a ) && is_array( $b ) ) {
			// Compare structurally. Scalars are coerced to strings ("5" === 5),
			// but nested arrays (repeater/group rows) are compared recursively
			// instead of being flattened to the literal "Array".
			return $this->deep_loose_equal( $a, $b );
		}

		// Loose scalar compare ("5" == 5), but guard the null/empty-string trap.
		if ( ( null === $a || '' === $a ) xor ( null === $b || '' === $b ) ) {
			return false;
		}

		return $a == $b; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- intentional cross-type compare for ACF round-trip.
	}

	/**
	 * Recursively compare two normalized values for ACF round-trip equality.
	 *
	 * Scalars compare cross-type via string coercion ("5" matches 5); arrays
	 * compare key-by-key and recurse, so repeater/group rows (arrays of arrays)
	 * are compared by content rather than collapsed to the string "Array".
	 *
	 * @param mixed $a First value.
	 * @param mixed $b Second value.
	 * @return bool
	 */
	private function deep_loose_equal( $a, $b ): bool {
		if ( is_array( $a ) || is_array( $b ) ) {
			if ( ! is_array( $a ) || ! is_array( $b ) ) {
				return false;
			}
			if ( array_keys( $a ) !== array_keys( $b ) ) {
				return false;
			}
			foreach ( $a as $key => $value ) {
				if ( ! $this->deep_loose_equal( $value, $b[ $key ] ) ) {
					return false;
				}
			}
			return true;
		}

		// Scalars/null: guard the null/empty-string trap, then loose-compare.
		if ( ( null === $a || '' === $a ) xor ( null === $b || '' === $b ) ) {
			return false;
		}

		return (string) $a === (string) $b;
	}
}
