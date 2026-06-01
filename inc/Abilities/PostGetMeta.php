<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostGetMeta extends AbilitiesBase {
	/**
	 * Get configuration for the post get meta ability.
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
		return 'Post - Get Meta';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Read raw post meta (postmeta table) for a post. Omit "keys" to return ALL meta keys for the post; pass an array of keys to return only those. Each value is unserialized as WordPress stores it. Use this for custom meta; for ACF fields prefer xfive-acf-acf-field-get which formats values. Hidden keys (starting with "_") are included.';
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
					'type'        => 'integer',
					'description' => 'The post ID to read meta from.',
				),
				'keys'    => array(
					'type'        => 'array',
					'description' => 'Optional array of meta keys to read. Omit to return all meta for the post.',
					'items'       => array(
						'type' => 'string',
					),
				),
				'single'  => array(
					'type'        => 'boolean',
					'description' => 'When true (default), return a single value per key (the first). When false, return the full array of values for each key.',
					'default'     => true,
				),
			),
			'required'   => array( 'post_id' ),
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
				'meta' => array(
					'type'        => 'object',
					'description' => 'Object of meta_key => value pairs.',
				),
				'hint' => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the post meta read.
	 *
	 * @param array $args Arguments for reading post meta.
	 * @return array|\WP_Error Array with meta on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = (int) ( $args['post_id'] ?? 0 );
		$keys    = $args['keys'] ?? array();
		$single  = $args['single'] ?? true;

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new \WP_Error( 'not_found', sprintf( 'Post ID %d not found.', $post_id ) );
		}

		$meta = array();

		if ( ! empty( $keys ) && is_array( $keys ) ) {
			foreach ( $keys as $key ) {
				$key          = (string) $key;
				$meta[ $key ] = get_post_meta( $post_id, $key, (bool) $single );
			}
		} else {
			$all = get_post_meta( $post_id );
			if ( is_array( $all ) ) {
				foreach ( $all as $key => $values ) {
					// get_post_meta() (no key) returns each value already serialized
					// as a string inside an array; unserialize for readability.
					$values = array_map( 'maybe_unserialize', (array) $values );
					$meta[ $key ] = (bool) $single ? ( $values[0] ?? '' ) : $values;
				}
			}
		}

		return array(
			'meta' => $meta,
			'hint' => sprintf( '%d meta key(s) returned for post %d. Keys starting with "_" are hidden/protected meta (ACF stores a "_field" reference key alongside each field).', count( $meta ), $post_id ),
		);
	}
}
