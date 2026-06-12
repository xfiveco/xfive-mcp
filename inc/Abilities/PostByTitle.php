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
		return 'Look up a post by its exact title. Pass the matching post_type — defaults to "page". Optionally narrow by post_status (defaults to "any"). Returns post_id plus the matched post\'s title, slug, status and type so a single lookup confirms the match; post_id is null when nothing matches (NOT an error) so you can chain follow-up actions like xfive-posts-post-create.';
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
				'post_status' => array(
					'type'        => 'string',
					'description' => 'Status filter: a single status, "any", or comma-separated list. Defaults to "any".',
					'default'     => 'any',
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
				'title'   => array(
					'type'        => 'string',
					'description' => 'Matched post title (present only on a match).',
				),
				'slug'    => array(
					'type'        => 'string',
					'description' => 'Matched post slug (present only on a match).',
				),
				'status'  => array(
					'type'        => 'string',
					'description' => 'Matched post status (present only on a match).',
				),
				'type'    => array(
					'type'        => 'string',
					'description' => 'Matched post type (present only on a match).',
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

		$status = $this->parse_status_arg( $args['post_status'] ?? 'any' );

		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => 1,
				'post_status'    => $status,
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

		$post = $query->posts[0];

		return array(
			'post_id' => (int) $post->ID,
			'title'   => $post->post_title,
			'slug'    => $post->post_name,
			'status'  => $post->post_status,
			'type'    => $post->post_type,
			'hint'    => sprintf( 'Matched %1$s %2$d ("%3$s", %4$s).', $post->post_type, (int) $post->ID, $post->post_name, $post->post_status ),
		);
	}
}
