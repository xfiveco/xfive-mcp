<?php

namespace XfiveMCP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper class for Advanced Custom Fields (ACF) integration.
 *
 * Provides utility methods for reading and writing ACF field values
 * associated with ACF-registered Gutenberg blocks.
 */
class AcfHelper {

	/**
	 * Check whether ACF is active and available.
	 *
	 * @return bool True if ACF's update_field() function exists.
	 */
	public static function is_acf_active(): bool {
		return function_exists( 'update_field' );
	}

	/**
	 * Build the ACF post_id string for a given block client ID.
	 *
	 * ACF stores block field values under a synthetic post_id of the
	 * form "block_{block_id}", where $block_id is the value stored in
	 * the block's attrs['id'] attribute.
	 *
	 * @param string $block_id The block client ID (e.g. "block_abc123").
	 * @return string ACF-compatible post_id string.
	 */
	public static function get_block_acf_post_id( string $block_id ): string {
		return 'block_' . $block_id;
	}

	/**
	 * Generate a unique block ID in the format ACF expects.
	 *
	 * @return string Unique block ID (e.g. "block_682a1c3f4b2e8").
	 */
	public static function generate_block_id(): string {
		return 'block_' . uniqid();
	}

	/**
	 * Update ACF field values for a given block.
	 *
	 * Iterates over the provided key→value map and calls update_field()
	 * for each entry using the block's ACF post_id. Complex types such as
	 * repeater rows, groups, flexible content layouts, galleries, and
	 * relationship arrays should be passed as nested arrays matching
	 * ACF's own update_field() format.
	 *
	 * @param string $block_id  Block client ID (the value from attrs['id']).
	 * @param array  $acf_fields Associative array of field_name => value.
	 * @return true|\WP_Error True on success, WP_Error on the first failure.
	 */
	public static function update_block_fields( string $block_id, array $acf_fields ): bool|\WP_Error {
		$acf_post_id = self::get_block_acf_post_id( $block_id );

		foreach ( $acf_fields as $field_name => $value ) {
			$result = update_field( $field_name, $value, $acf_post_id );

			if ( false === $result ) {
				return new \WP_Error(
					'acf_update_failed',
					sprintf(
						'Failed to update ACF field "%s" for block "%s".',
						$field_name,
						$block_id
					)
				);
			}
		}

		return true;
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
}
