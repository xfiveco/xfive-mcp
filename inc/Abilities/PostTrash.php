<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostTrash extends AbilitiesBase {
	/**
	 * Get configuration for the post trash ability.
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
		return 'Post - Trash';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Move a post to the trash.';
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
					'description' => 'The ID of the post to trash',
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
				'trashed' => array(
					'type'        => 'boolean',
					'description' => 'Whether the post was trashed successfully',
				),
			),
		);
	}

	/**
	 * Execute the post trashing.
	 *
	 * @param array $args Arguments for trashing a post.
	 * @return array|\WP_Error Array with status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		$result = wp_trash_post( $post_id );

		if ( ! $result ) {
			return new \WP_Error( 'trash_failed', 'Failed to move post to trash' );
		}

		return array(
			'trashed' => true,
		);
	}
}
