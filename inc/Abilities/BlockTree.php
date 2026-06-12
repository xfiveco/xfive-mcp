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
		return 'Read-only inspection: parses post_content into a Gutenberg block tree (blocks with blockName, attrs, innerBlocks). Use to discover what is on a page before editing — then send the full updated markup via xfive-posts-post-update-content. Empty whitespace-only blocks are stripped. The payload can be large; pass attrs_only=true to drop innerHTML/innerContent (keep blockName + attrs + structure) and/or depth=N to limit nesting (N=1 = top-level only; deeper blocks are summarized with a childCount). Defaults return the full tree.';
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
				'post_id'    => array(
					'type'        => 'integer',
					'description' => 'Post ID to read.',
				),
				'attrs_only' => array(
					'type'        => 'boolean',
					'description' => 'When true, drop innerHTML/innerContent from each block (keep blockName, attrs and innerBlocks structure). Much smaller payload. Defaults to false.',
					'default'     => false,
				),
				'depth'      => array(
					'type'        => 'integer',
					'description' => 'Max nesting depth to return (1 = top-level only). Blocks deeper than this are replaced with a {blockName, childCount, truncated:true} summary. Omit/0 = full depth.',
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
					'description' => 'Ordered block tree. Each block has blockName, attrs and innerBlocks (recursive); innerHTML/innerContent are included unless attrs_only=true. Blocks past the depth limit are summarized as {blockName, attrs, childCount, truncated:true}.',
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

		$attrs_only = ! empty( $args['attrs_only'] );
		$depth      = isset( $args['depth'] ) ? (int) $args['depth'] : 0;

		$blocks = parse_blocks( $post->post_content );
		$blocks = $this->prepare_blocks( $blocks, $attrs_only, $depth, 1 );

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

	/**
	 * Filter out empty blocks and apply attrs_only / depth trimming recursively.
	 *
	 * @param array $blocks     Parsed blocks.
	 * @param bool  $attrs_only Drop innerHTML/innerContent when true.
	 * @param int   $max_depth  Max depth to descend (0 = unlimited).
	 * @param int   $level      Current depth (1-based).
	 * @return array Prepared blocks.
	 */
	private function prepare_blocks( array $blocks, bool $attrs_only, int $max_depth, int $level ): array {
		$out = array();

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$children = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] )
				? array_values( array_filter( $block['innerBlocks'], static fn( $b ) => ! empty( $b['blockName'] ) ) )
				: array();

			// Beyond the requested depth: summarize instead of descending.
			if ( $max_depth > 0 && $level >= $max_depth && ! empty( $children ) ) {
				$entry = array(
					'blockName'  => $block['blockName'],
					'attrs'      => $block['attrs'] ?? array(),
					'childCount' => count( $children ),
					'truncated'  => true,
				);
				$out[] = $entry;
				continue;
			}

			$entry = array(
				'blockName'   => $block['blockName'],
				'attrs'       => $block['attrs'] ?? array(),
				'innerBlocks' => $this->prepare_blocks( $children, $attrs_only, $max_depth, $level + 1 ),
			);

			if ( ! $attrs_only ) {
				$entry['innerHTML']    = $block['innerHTML'] ?? '';
				$entry['innerContent'] = $block['innerContent'] ?? array();
			}

			$out[] = $entry;
		}

		return $out;
	}
}
