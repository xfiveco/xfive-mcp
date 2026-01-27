<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostUpdate extends AbilitiesBase {
	/**
	 * Get configuration for the post update ability.
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
		return 'Post - Update';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Update an existing post (title, content, status, etc.).';
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
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to update',
				),
				'post_title'   => array(
					'type'        => 'string',
					'description' => 'The new title of the post',
				),
				'post_content' => array(
					'type'        => 'string',
					'description' => 'The new content of the post',
				),
				'post_status'  => array(
					'type'        => 'string',
					'description' => 'The new post status (e.g., publish, draft, private)',
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
				'updated' => array(
					'type'        => 'boolean',
					'description' => 'Whether the post was updated successfully',
				),
			),
		);
	}

	/**
	 * Execute the post update.
	 *
	 * @param array $args Arguments for updating a post.
	 * @return array|\WP_Error Array with status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		$post_data = array(
			'ID' => $post->ID,
		);

		if ( isset( $args['post_title'] ) ) {
			$post_data['post_title'] = $args['post_title'];
		}

		if ( isset( $args['post_content'] ) ) {
			$post_data['post_content'] = $args['post_content'];
		}

		if ( isset( $args['post_status'] ) ) {
			$post_data['post_status'] = $args['post_status'];
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'updated' => true,
		);
	}
}
