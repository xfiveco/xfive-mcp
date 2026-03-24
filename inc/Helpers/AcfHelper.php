<?php

namespace XfiveMCP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper class for Advanced Custom Fields (ACF) integration.
 *
 * ACF blocks serialise their field values directly inside the block comment
 * as an attrs['data'] object:
 *
 *   <!-- wp:acf/my-block {"data":{"field_name":"value","_field_name":"field_key"}} /-->
 *
 * This helper builds that data structure and injects it into block attrs
 * before the block is serialised, so no separate postmeta write is needed.
 */
class AcfHelper {

	/**
	 * Check whether ACF is active and available.
	 *
	 * @return bool True if ACF's acf_get_field() function exists.
	 */
	public static function is_acf_active(): bool {
		return function_exists( 'acf_get_field' );
	}

	/**
	 * Generate a unique block ID in the format ACF expects.
	 *
	 * ACF block IDs look like "block_682a1c3f4b2e8" (the "block_" prefix is
	 * part of the ID itself, stored in attrs['id']).
	 *
	 * @return string Unique block ID (e.g. "block_682a1c3f4b2e8").
	 */
	public static function generate_block_id(): string {
		return 'block_' . uniqid();
	}

	/**
	 * Extract the block client ID from a normalised WP block array.
	 *
	 * Reads attrs['id'] from the block data as serialised by serialize_blocks().
	 *
	 * @param array $block Normalised block array (WordPress block format).
	 * @return string Block ID, or empty string if not set.
	 */
	public static function extract_block_id( array $block ): string {
		return $block['attrs']['id'] ?? '';
	}

	/**
	 * Build the ACF data array for a block's attrs['data'] entry.
	 *
	 * ACF stores field values in the serialised block comment under a "data"
	 * key. Each field contributes two entries:
	 *   - "field_name"  => the raw value
	 *   - "_field_name" => the ACF field key (e.g. "field_abc123")
	 *
	 * If a field key cannot be resolved (field not registered), the entry is
	 * still written without the _field_name reference so the value is not lost.
	 *
	 * @param array $acf_fields Associative array of field_name => value.
	 * @return array Data array suitable for attrs['data'].
	 */
	public static function build_block_data( array $acf_fields ): array {
		$data = array();

		foreach ( $acf_fields as $field_name => $value ) {
			$data[ $field_name ] = $value;

			// Resolve the ACF field key for the _field_name reference.
			$field_object = acf_get_field( $field_name );
			if ( $field_object && ! empty( $field_object['key'] ) ) {
				$data[ '_' . $field_name ] = $field_object['key'];
			}
		}

		return $data;
	}

	/**
	 * Merge ACF field data into a block attributes array.
	 *
	 * Reads any existing attrs['data'], merges the new field values on top,
	 * and returns the full attrs array with the updated 'data' key.
	 *
	 * @param array $attrs      Existing block attributes array.
	 * @param array $acf_fields Associative array of field_name => value.
	 * @return array Updated attributes array.
	 */
	public static function merge_acf_data( array $attrs, array $acf_fields ): array {
		$existing_data = $attrs['data'] ?? array();
		$new_data      = self::build_block_data( $acf_fields );

		$attrs['data'] = array_merge( $existing_data, $new_data );

		return $attrs;
	}
}
