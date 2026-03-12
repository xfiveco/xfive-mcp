<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * BlockRemove Ability
 *
 * Removes a block from a post by its index.
 */
class BlockRemove extends AbilitiesBase {

	/**
	 * Get configuration.
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
		return 'Block - Remove';
	}

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	public function get_description(): string {
		return 'Remove a block from a post';
	}

	/**
	 * Get input schema.
	 *
	 * @return array Input schema.
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'     => array(
					'type'        => 'integer',
					'description' => 'Target post ID',
				),
				'block_index' => array(
					'type'        => 'integer',
					'description' => 'Index of the block to remove (0-based)',
				),
			),
			'required'   => array( 'post_id', 'block_index' ),
		);
	}

	/**
	 * Get output schema.
	 *
	 * @return array Output schema.
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'removed'    => array(
					'type'        => 'boolean',
					'description' => 'Whether the block was removed successfully',
				),
				'block_name' => array(
					'type'        => 'string',
					'description' => 'Name of the removed block',
				),
			),
		);
	}

	/**
	 * Execute the block removal.
	 *
	 * @param array $args Arguments containing post_id and block_index.
	 * @return array|object Result array or WP_Error.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id     = absint( $args['post_id'] );
		$block_index = absint( $args['block_index'] );
		$post        = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', 'Post not found' );
		}

		$blocks = parse_blocks( $post->post_content );

		if ( ! isset( $blocks[ $block_index ] ) ) {
			return new \WP_Error(
				'block_not_found',
				sprintf( 'Block at index %d not found. Post has %d blocks.', $block_index, count( $blocks ) )
			);
		}

		// Get the block name before removing.
		$removed_block_name = $blocks[ $block_index ]['blockName'] ?? 'unknown';

		// Remove the block.
		array_splice( $blocks, $block_index, 1 );

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_slash( serialize_blocks( $blocks ) ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'removed'    => true,
			'block_name' => $removed_block_name,
		);
	}
}
