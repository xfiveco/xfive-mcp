<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostUpdateContent extends AbilitiesBase {
	/**
	 * Get configuration for the post update content ability.
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
		return 'Post - Update Content';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Replace the entire post_content of an existing post with new Gutenberg block markup. Use this for ALL block-content edits (creating sections, reordering, removing, fixing typos in seeded data) — write the full new markup and send it once. Always call xfive-blocks-block-schema for every block first to validate attribute shape. For blank/empty content pass an empty string.';
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
					'description' => 'Post ID to update.',
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Full Gutenberg block markup to set as the new post_content. Will completely replace existing content. Pass "" to clear.',
				),
			),
			'required'   => array( 'post_id', 'content' ),
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
					'description' => 'Whether the post was updated successfully.',
				),
				'hint'    => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the post content update.
	 *
	 * Updates the content of an existing post with corrected text after
	 * spell-checking, grammar review, or content editing.
	 *
	 * @param array $args {
	 *     Arguments for updating post content.
	 *
	 *     @type int    $post_id The ID of the post to update.
	 *     @type string $content The corrected content for the post.
	 * }
	 * @return array|\WP_Error Array with update status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		$result = wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $args['content'],
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'updated' => true,
			'hint'    => 'Verify with xfive-blocks-block-tree (post_id) or load the post URL in a browser. If a block did not render, run xfive-blocks-block-schema and check that all attributes match the schema.',
		);
	}
}
