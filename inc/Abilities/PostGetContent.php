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
		return 'Get the raw post_content (Gutenberg block markup) of a post by ID. Use when you need the full markup string for editing — for a structured tree view of blocks use xfive-blocks-block-tree instead.';
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
					'description' => 'Raw post_content as Gutenberg block markup.',
				),
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The post ID the content belongs to (echoed back).',
				),
				'length'  => array(
					'type'        => 'integer',
					'description' => 'Character length of the raw content.',
				),
				'hint'    => array(
					'type' => 'string',
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

		$content = $post->post_content;
		$length  = mb_strlen( $content );

		return array(
			'content' => $content,
			'post_id' => $post_id,
			'length'  => $length,
			'hint'    => '' === $content
				? 'Post is empty. Insert blocks with xfive-posts-post-update-content (call xfive-blocks-block-schema for each block first).'
				: sprintf( 'Post %1$d, %2$d chars. To edit, modify the markup string and write it back via xfive-posts-post-update-content.', $post_id, $length ),
		);
	}
}
