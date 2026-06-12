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
		return 'Create a new post/page/CPT entry in one call. Returns the new post_id. Accepts every field wp_insert_post() understands (post_title, post_content, post_type, post_status, post_excerpt, post_name/slug, post_parent, menu_order, post_date, post_author, comment_status, ping_status, post_password) plus _thumbnail_id (featured image; validated + routed through set_post_thumbnail), meta_input (object of meta_key => value) and tax_input (object of taxonomy => term IDs). Use these to create a post fully in one round-trip instead of create + follow-up calls. If post_content contains blocks, call xfive-blocks-block-schema for each block FIRST. For pages the user should preview, set post_status to "publish" (default is "draft"). For ACF fields chain xfive-acf-acf-field-update after. Attachment/term IDs are site-local — remap when migrating.';
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
				'post_title'     => array(
					'type'        => 'string',
					'description' => 'The title of the post.',
				),
				'post_content'   => array(
					'type'        => 'string',
					'description' => 'The content of the post.',
				),
				'post_type'      => array(
					'type'        => 'string',
					'description' => 'The post type (e.g., post, page, industry). Defaults to post.',
					'default'     => 'post',
				),
				'post_status'    => array(
					'type'        => 'string',
					'description' => 'The post status (e.g., publish, draft, private). Defaults to draft.',
					'default'     => 'draft',
				),
				'post_excerpt'   => array(
					'type'        => 'string',
					'description' => 'The excerpt.',
				),
				'post_name'      => array(
					'type'        => 'string',
					'description' => 'The slug (post_name). Auto-derived from title when omitted.',
				),
				'post_parent'    => array(
					'type'        => 'integer',
					'description' => 'Parent post ID.',
				),
				'menu_order'     => array(
					'type'        => 'integer',
					'description' => 'Menu order.',
				),
				'post_date'      => array(
					'type'        => 'string',
					'description' => 'Post date (Y-m-d H:i:s, site local time).',
				),
				'post_author'    => array(
					'type'        => 'integer',
					'description' => 'Author user ID.',
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
					'description' => 'Featured image attachment ID (validated + routed through set_post_thumbnail).',
				),
				'meta_input'     => array(
					'type'        => 'object',
					'description' => 'Object of meta_key => value pairs to set on the new post.',
				),
				'tax_input'      => array(
					'type'        => 'object',
					'description' => 'Object of taxonomy => term IDs to assign, e.g. {"condition":[57,58,59]}. Term IDs are site-local.',
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
					'description' => 'The ID of the created post.',
				),
				'hint'    => array(
					'type' => 'string',
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
		$post_data = array_merge(
			array(
				'post_content' => '',
				'post_type'    => 'post',
				'post_status'  => 'draft',
			),
			// _thumbnail_id and tax_input are handled after insert.
			$this->forward_post_fields( $args ),
		);

		if ( isset( $args['meta_input'] ) && is_array( $args['meta_input'] ) ) {
			$post_data['meta_input'] = $args['meta_input'];
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$notes = array();

		// Featured image: validate + route through the thumbnail API.
		if ( isset( $args['_thumbnail_id'] ) ) {
			$note = $this->apply_featured_image( $post_id, $args['_thumbnail_id'], false );
			if ( null !== $note ) {
				$notes[] = $note;
			}
		}

		// Taxonomy terms: assign explicitly (capability-independent).
		if ( isset( $args['tax_input'] ) && is_array( $args['tax_input'] ) ) {
			$notes = array_merge( $notes, $this->apply_tax_input( $post_id, $args['tax_input'] ) );
		}

		$post_type = $post_data['post_type'];
		$hint      = sprintf( 'Post created (%1$s, status: %2$s).', $post_type, $post_data['post_status'] );

		if ( $post_data['post_status'] === 'draft' ) {
			$hint .= ' Set post_status to "publish" if the user should preview it.';
		}

		if ( $post_type === 'page' ) {
			$hint .= ' If this is the homepage, set show_on_front + page_on_front via xfive-options-options-update.';
		}

		if ( ! empty( $notes ) ) {
			$hint .= ' Warnings: ' . implode( ' ', $notes );
		}

		return array(
			'post_id' => $post_id,
			'hint'    => $hint,
		);
	}
}
