<?php

namespace XfiveMCP\Abilities;

use XfiveMCP\Blocks\BlockRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class BlockUpdate extends AbilitiesBase {

	/**
	 * Get configuration for the block update ability.
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
		return 'Blocks - Update';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Modify attributes and content of an existing block within a post. Use when asked for block editing. Check block-schema for blocks structure.';
	}

	/**
	 * Get the input schema for the ability.
	 *
	 * @return array Schema defining required and optional input parameters.
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
					'description' => 'Index of the block to update (0-based)',
				),
				'block'       => array(
					'type'        => 'string',
					'description' => 'Block name (e.g. core/paragraph)',
				),
				'attributes'  => array(
					'type'                 => 'object',
					'description'          => 'New block attributes',
					'additionalProperties' => true,
				),
				'innerBlocks' => array(
					'type'        => 'array',
					'description' => 'New nested blocks',
					'items'       => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
			),
			'required'   => array( 'post_id', 'block_index' ),
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
				'updated'    => array(
					'type'        => 'boolean',
					'description' => 'Whether the block was updated successfully',
				),
				'block_name' => array(
					'type'        => 'string',
					'description' => 'Name of the updated block',
				),
			),
		);
	}

	/**
	 * Execute the block update operation.
	 *
	 * Updates an existing block in a post by merging new attributes with existing ones
	 * and optionally changing the block type or inner blocks.
	 *
	 * @param array $args {
	 *     Arguments for updating the block.
	 *
	 *     @type int    $post_id     Target post ID.
	 *     @type int    $block_index Index of the block to update (0-based).
	 *     @type string $block       Optional. New block name (e.g., 'core/paragraph').
	 *     @type array  $attributes  Optional. New block attributes to merge.
	 *     @type array  $innerBlocks Optional. New nested blocks to replace existing ones.
	 * }
	 * @return array|\WP_Error Array with update status on success, WP_Error on failure.
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

		// Get the existing block.
		$existing_block = $blocks[ $block_index ];

		// If block name is provided, validate it matches or allow changing block type.
		if ( ! empty( $args['block'] ) ) {
			$new_block_name = $args['block'];

			// Validate that the new block type exists.
			$registry = \WP_Block_Type_Registry::get_instance();
			if ( ! $registry->is_registered( $new_block_name ) ) {
				return new \WP_Error(
					'invalid_block_type',
					sprintf( 'Block type "%s" is not registered', $new_block_name )
				);
			}
		} else {
			// Keep the existing block type.
			$new_block_name = $existing_block['blockName'];
		}

		// Merge attributes: keep existing, override with new.
		$new_attrs = array_merge(
			$existing_block['attrs'] ?? array(),
			$args['attributes'] ?? array()
		);

		// Handle inner blocks: if provided, replace; otherwise keep existing.
		$new_inner_blocks_data = $args['innerBlocks'] ?? $existing_block['innerBlocks'] ?? array();

		$updated_block_data = array(
			'block'       => $new_block_name,
			'attributes'  => $new_attrs,
			'innerBlocks' => $new_inner_blocks_data,
		);

		$updated_block = BlockRegistry::get_instance()->normalize_block( $updated_block_data );

		if ( is_wp_error( $updated_block ) ) {
			return $updated_block;
		}

		// Replace the block at the specified index.
		$blocks[ $block_index ] = $updated_block;

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
			'updated'    => true,
			'block_name' => $new_block_name,
		);
	}
}
