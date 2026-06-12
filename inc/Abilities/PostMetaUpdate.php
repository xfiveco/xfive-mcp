<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostMetaUpdate extends AbilitiesBase {
	/**
	 * Get configuration for the post meta update ability.
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
		return 'Post - Meta Update';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Update raw post meta (update_post_meta) for a post. Pass an object of meta_key => value pairs. Use for CORE/custom meta (e.g. "_thumbnail_id" featured image, "page_title_display"); for ACF fields prefer xfive-acf-acf-field-update which formats values. To set a featured image you can use this with "_thumbnail_id", but xfive-posts-post-set-featured validates the attachment is an image — prefer that for featured images. Pass null as a value to delete that meta key.';
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
					'description' => 'The post ID to update meta on.',
				),
				'meta'    => array(
					'type'        => 'object',
					'description' => 'Object of meta_key => value pairs. Value may be string/number/bool/array. Pass null to delete the key.',
				),
			),
			'required'   => array( 'post_id', 'meta' ),
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
					'description' => 'Meta keys that were updated or deleted.',
					'items'       => array( 'type' => 'string' ),
				),
				'failed'  => array(
					'type'        => 'array',
					'description' => 'Meta keys that failed to update.',
					'items'       => array( 'type' => 'string' ),
				),
				'hint'    => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the post meta update.
	 *
	 * @param array $args Arguments for updating post meta.
	 * @return array|\WP_Error Array with results on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = (int) ( $args['post_id'] ?? 0 );
		$meta    = $args['meta'] ?? array();

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return new \WP_Error( 'not_found', sprintf( 'Post ID %d not found.', $post_id ) );
		}

		if ( ! is_array( $meta ) || empty( $meta ) ) {
			return new \WP_Error( 'missing_param', 'meta must be a non-empty object of key => value pairs.' );
		}

		$updated = array();
		$failed  = array();

		foreach ( $meta as $key => $value ) {
			$key = (string) $key;

			if ( null === $value ) {
				$result = delete_post_meta( $post_id, $key );
			} else {
				$result = update_post_meta( $post_id, $key, $value );
			}

			// update_post_meta returns false both on genuine failure AND when the
			// value is unchanged. Disambiguate by re-reading: compare the stored
			// value to what we set using WordPress's own serialized form, so
			// cross-type values (0 vs "0" vs false) aren't loosely conflated.
			if (
				false === $result
				&& null !== $value
				&& maybe_serialize( get_post_meta( $post_id, $key, true ) ) !== maybe_serialize( $value )
			) {
				$failed[] = $key;
			} else {
				$updated[] = $key;
			}
		}

		return array(
			'updated' => $updated,
			'failed'  => $failed,
			'hint'    => sprintf( '%1$d meta key(s) updated, %2$d failed for post %3$d.', count( $updated ), count( $failed ), $post_id ),
		);
	}
}
