<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostCreate extends AbilitiesBase {
	/**
	 * Get configuration for the post create ability.
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
		return 'Post - Create';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Create a new post of any type (defaults to post).';
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
				'post_title'   => array(
					'type'        => 'string',
					'description' => 'The title of the post',
				),
				'post_content' => array(
					'type'        => 'string',
					'description' => 'The content of the post',
				),
				'post_type'    => array(
					'type'        => 'string',
					'description' => 'The post type (e.g., post, page, etc.). Defaults to post.',
					'default'     => 'post',
				),
				'post_status'  => array(
					'type'        => 'string',
					'description' => 'The post status (e.g., publish, draft, private). Defaults to draft.',
					'default'     => 'draft',
				),
			),
			'required'   => array( 'post_title' ),
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the created post',
				),
			),
		);
	}

	/**
	 * Execute the post creation.
	 *
	 * @param array $args Arguments for creating a post.
	 * @return array|\WP_Error Array with post ID on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_data = array(
			'post_title'   => $args['post_title'],
			'post_content' => isset( $args['post_content'] ) ? $args['post_content'] : '',
			'post_type'    => isset( $args['post_type'] ) ? $args['post_type'] : 'post',
			'post_status'  => isset( $args['post_status'] ) ? $args['post_status'] : 'draft',
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return array(
			'post_id' => $post_id,
		);
	}
}
