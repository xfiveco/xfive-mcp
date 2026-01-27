<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostGetContent extends AbilitiesBase {
	/**
	 * Get configuration for the post get content ability.
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
		return 'Post - Get Content';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Retrieve the raw content of a post for review, spell-checking, grammar correction, or content analysis. Use only when asked for spelling or grammar check.';
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
					'description' => 'Post ID',
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
				'content' => array(
					'type'        => 'string',
					'description' => 'The raw post content for review, spell-checking, or editing',
				),
			),
		);
	}

	/**
	 * Execute the post content retrieval.
	 *
	 * Retrieves the raw content of a post by its ID for review, spell-checking,
	 * grammar correction, or content analysis.
	 *
	 * @param array $args {
	 *     Arguments for retrieving post content.
	 *
	 *     @type int $post_id The ID of the post to retrieve.
	 * }
	 * @return array|\WP_Error Array with content on success, WP_Error if post not found.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		return array(
			'content' => $post->post_content,
		);
	}
}
