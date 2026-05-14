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
		return 'Look up a post ID by its exact title. Pass the matching post_type — defaults to "page". Returns post_id: null when nothing matches (NOT an error) so you can chain follow-up actions like xfive-posts-post-create.';
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
					'description' => 'Exact post title to match (case-sensitive).',
				),
				'post_type'  => array(
					'type'        => 'string',
					'description' => 'Post type to search within (e.g. "page", "post", "case-study", "team-member"). Defaults to "page".',
					'default'     => 'page',
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
					'type'        => array( 'integer', 'null' ),
					'description' => 'The ID of the matched post, or null when no match was found.',
				),
				'hint'    => array(
					'type' => 'string',
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
		$post_type = sanitize_text_field( $args['post_type'] ?? 'page' );
		$title     = sanitize_text_field( $args['post_title'] );

		$query = new \WP_Query(
			array(
				'fields'         => 'ids',
				'post_type'      => $post_type,
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'no_found_rows'  => true,
				'title'          => $title,
			)
		);

		if ( ! $query->have_posts() ) {
			return array(
				'post_id' => null,
				'hint'    => sprintf(
					'No %1$s with title "%2$s". Try another post_type (page/post/CPT slug like case-study, team-member) or create with xfive-posts-post-create.',
					$post_type,
					$title
				),
			);
		}

		return array( 'post_id' => (int) $query->posts[0] );
	}
}
