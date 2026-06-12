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
		return 'Update post-level fields on an existing post. Accepts any field wp_update_post() understands (post_title, post_content, post_type, post_status, post_excerpt, post_name/slug, post_parent, menu_order, post_date, comment_status, ping_status, post_author, post_password) plus _thumbnail_id (featured image; routed through set_post_thumbnail, pass 0 to remove), meta_input (object of meta_key => value) and tax_input (object of taxonomy => term IDs). For block content updates, prefer xfive-posts-post-update-content. For arbitrary core meta prefer xfive-posts-post-meta-update; for ACF prefer xfive-acf-acf-field-update. Pass only the fields you want to change; post_id is required.';
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
				'post_id'        => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to update.',
				),
				'post_title'     => array(
					'type'        => 'string',
					'description' => 'The new title of the post.',
				),
				'post_content'   => array(
					'type'        => 'string',
					'description' => 'The new content of the post.',
				),
				'post_type'      => array(
					'type'        => 'string',
					'description' => 'Change the post type (e.g., "post", "page", "industry"). Use with care — the type must be registered.',
				),
				'post_status'    => array(
					'type'        => 'string',
					'description' => 'The new post status (e.g., publish, draft, private).',
				),
				'post_excerpt'   => array(
					'type'        => 'string',
					'description' => 'The new excerpt.',
				),
				'post_name'      => array(
					'type'        => 'string',
					'description' => 'The new slug (post_name).',
				),
				'post_parent'    => array(
					'type'        => 'integer',
					'description' => 'The new parent post ID.',
				),
				'menu_order'     => array(
					'type'        => 'integer',
					'description' => 'The new menu order.',
				),
				'post_date'      => array(
					'type'        => 'string',
					'description' => 'The new post date (Y-m-d H:i:s, site local time).',
				),
				'post_author'    => array(
					'type'        => 'integer',
					'description' => 'The new author user ID.',
				),
				'comment_status' => array(
					'type'        => 'string',
					'description' => 'open or closed.',
				),
				'ping_status'    => array(
					'type'        => 'string',
					'description' => 'open or closed.',
				),
				'post_password'  => array(
					'type'        => 'string',
					'description' => 'Post password.',
				),
				'_thumbnail_id'  => array(
					'type'        => 'integer',
					'description' => 'Featured image attachment ID (routed through set_post_thumbnail). Pass 0 to remove.',
				),
				'meta_input'     => array(
					'type'        => 'object',
					'description' => 'Object of meta_key => value pairs to set alongside the update.',
				),
				'tax_input'      => array(
					'type'        => 'object',
					'description' => 'Object of taxonomy => term IDs to assign, e.g. {"condition":[57,58,59]}. Term IDs are site-local; remap when migrating. Falls back to wp_set_object_terms if the current user lacks the taxonomy assign cap.',
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
					'description' => 'Whether the post was updated successfully.',
				),
				'hint'    => array(
					'type' => 'string',
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

		// Forward only the whitelisted wp_update_post() fields, so unknown/unsafe
		// keys (like raw "ID") can't be smuggled in. _thumbnail_id and
		// meta_input are handled separately below.
		$post_data = array_merge(
			array( 'ID' => $post->ID ),
			$this->forward_post_fields( $args ),
		);

		if ( isset( $args['meta_input'] ) && is_array( $args['meta_input'] ) ) {
			$post_data['meta_input'] = $args['meta_input'];
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$notes = array();

		// Featured image: route through the thumbnail API so it is validated
		// and stored correctly (wp_update_post does not handle _thumbnail_id).
		// The post is already saved, so a bad id is a warning, not a hard error.
		if ( isset( $args['_thumbnail_id'] ) ) {
			$note = $this->apply_featured_image( $post_id, $args['_thumbnail_id'], true );
			if ( null !== $note ) {
				$notes[] = $note;
			}
		}

		// Taxonomy terms: assign explicitly so it works regardless of the MCP
		// user's per-taxonomy assign capability (wp_update_post's tax_input is
		// capability-gated and silently skips when the cap is missing).
		if ( isset( $args['tax_input'] ) && is_array( $args['tax_input'] ) ) {
			$notes = array_merge( $notes, $this->apply_tax_input( $post_id, $args['tax_input'] ) );
		}

		$hint = 'Updated.';
		if ( isset( $args['post_content'] ) ) {
			$hint .= ' For block content edits prefer xfive-posts-post-update-content next time — single-purpose tool, same effect.';
		}
		if ( ! empty( $notes ) ) {
			$hint .= ' Warnings: ' . implode( ' ', $notes );
		}

		return array(
			'updated' => true,
			'hint'    => $hint,
		);
	}
}
