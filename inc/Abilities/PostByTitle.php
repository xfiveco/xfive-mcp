<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostByTitle extends AbilitiesBase {
	/**
	 * Get configuration for the post by title ability.
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
		return 'Post - By Title';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Retrieve a post ID by searching for its title.';
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
				'post_title' => array(
					'type'        => 'string',
					'description' => 'Post title',
				),
			),
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
					'description' => 'The ID of the found post',
				),
			),
		);
	}

	/**
	 * Execute the post search by title.
	 *
	 * Searches for a post with the exact title match and returns its ID.
	 *
	 * @param array $args {
	 *     Arguments for the search.
	 *
	 *     @type string $post_title The title of the post to search for.
	 * }
	 * @return array|\WP_Error Array with post_id on success, WP_Error if not found.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$query_args = array(
			'fields'         => 'ids',
			'post_type'      => 'any',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'no_found_rows'  => true,
			'title'          => sanitize_text_field( $args['post_title'] ),
		);

		$query = new \WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
				return new \WP_Error( 'not_found', 'Post not found' );
		}

		return array( 'post_id' => $query->posts[0] );
	}
}
