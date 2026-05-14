<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Block Tree Ability.
 *
 * Displays blocks tree for a post.
 */
class BlockTree extends AbilitiesBase {

	/**
	 * Get ability configuration.
	 *
	 * @return array Configuration array.
	 */
	public function get_config(): array {
		return array();
	}

	/**
	 * Get ability name.
	 *
	 * @return string Ability name.
	 */
	public function get_name(): string {
		return 'Block tree';
	}

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	public function get_description(): string {
		return 'Read-only inspection: parses post_content into a Gutenberg block tree (top-level blocks with attrs + innerBlocks). Use to discover what is currently on a page before editing — then send the full updated markup via xfive-posts-post-update-content. Empty whitespace-only blocks are stripped.';
	}

	/**
	 * Get input schema.
	 *
	 * @return array Input schema definition.
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'Post ID to read.',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	/**
	 * Get output schema.
	 *
	 * @return array Output schema definition.
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'blocks' => array(
					'type'        => 'array',
					'description' => 'Ordered list of top-level blocks. Each block has blockName, attrs, innerBlocks, innerHTML.',
					'items'       => array(
						'type' => 'object',
					),
				),
				'hint'   => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array $args Ability arguments.
	 * @return array|\WP_Error Result array or error.
	 */
	public function execute_callback( array $args = array() ) {
		$post = get_post( $args['post_id'] ?? 0 );
		if ( ! $post ) {
			return array(
				'blocks' => array(),
				'hint'   => 'Post not found. Confirm post_id with xfive-posts-post-by-title (passing the matching post_type).',
			);
		}

		$blocks = parse_blocks( $post->post_content );
		$blocks = array_values(
			array_filter(
				$blocks,
				static function ( $block ) {
					return ! empty( $block['blockName'] );
				}
			)
		);

		if ( empty( $blocks ) ) {
			return array(
				'blocks' => array(),
				'hint'   => 'Post has no blocks yet. Use xfive-posts-post-update-content to insert Gutenberg markup (run xfive-blocks-block-schema first for each block).',
			);
		}

		return array(
			'blocks' => $blocks,
			'hint'   => 'To modify, write the full new markup with xfive-posts-post-update-content (no partial-block tools available).',
		);
	}
}
