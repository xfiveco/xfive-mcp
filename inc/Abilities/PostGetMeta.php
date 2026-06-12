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
		return 'Read raw post meta (postmeta table) for a post, plus the full post object needed to migrate it: post_title, post_name (slug), post_status, post_type, post_excerpt, post_parent, menu_order, post_author, post_date, post_modified, comment_status, ping_status, post_password and featured_image_id (post thumbnail attachment ID). Omit "keys" to return ALL meta keys for the post; pass an array of keys to return only those. Each value is unserialized as WordPress stores it. Use this for custom meta; for ACF fields prefer xfive-acf-acf-field-get which formats values. Hidden keys (starting with "_") are included. featured_image_id and any attachment/term IDs in meta are site-local — remap when migrating.';
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
				'post' => array(
					'type'        => 'object',
					'description' => 'Core post fields for migration: post_title, post_name (slug), post_status, post_type, post_excerpt, post_parent, menu_order, post_author, post_date, post_modified, comment_status, ping_status, post_password, featured_image_id (post thumbnail attachment ID; site-local).',
				),
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

		$post_obj = $post_id ? get_post( $post_id ) : null;
		if ( ! $post_id || ! $post_obj ) {
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
			'post' => array(
				'post_title'        => $post_obj->post_title,
				'post_name'         => $post_obj->post_name,
				'post_status'       => $post_obj->post_status,
				'post_type'         => $post_obj->post_type,
				'post_excerpt'      => $post_obj->post_excerpt,
				'post_parent'       => (int) $post_obj->post_parent,
				'menu_order'        => (int) $post_obj->menu_order,
				'post_author'       => (int) $post_obj->post_author,
				'post_date'         => $post_obj->post_date,
				'post_modified'     => $post_obj->post_modified,
				'comment_status'    => $post_obj->comment_status,
				'ping_status'       => $post_obj->ping_status,
				'post_password'     => $post_obj->post_password,
				'featured_image_id' => (int) get_post_thumbnail_id( $post_id ),
			),
			'meta' => $meta,
			'hint' => sprintf( '%d meta key(s) returned for post %d. Keys starting with "_" are hidden/protected meta (ACF stores a "_field" reference key alongside each field). The "post" object carries the core post fields for migration; featured_image_id is the attachment ID (site-local — remap on the target).', count( $meta ), $post_id ),
		);
	}
}
