<?php

namespace XfiveMCP\Abilities;

use XfiveMCP\Blocks\BlockRegistry;
use XfiveMCP\Helpers\AcfHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * BlockReplace Ability
 *
 * Replaces an existing block with a completely new block.
 * Unlike BlockUpdate which merges attributes, this completely replaces the block.
 */
class BlockReplace extends AbilitiesBase {

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
		return 'Block - Replace';
	}

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	public function get_description(): string {
		return 'Replace a block with a completely new block';
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
					'description' => 'Index of the block to replace (0-based)',
				),
				'block'       => array(
					'type'        => 'string',
					'description' => 'New block name (e.g. core/paragraph)',
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
				'acf_fields'  => array(
					'type'                 => 'object',
					'description'          => 'ACF custom field values to save for the replacement block. Keys are ACF field names, values are the data to store. Supports all ACF field types (text, image, repeater, group, etc.).',
					'additionalProperties' => true,
				),
			),
			'required'   => array( 'post_id', 'block_index', 'block' ),
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
				'replaced'       => array(
					'type'        => 'boolean',
					'description' => 'Whether the block was replaced successfully',
				),
				'old_block_name' => array(
					'type'        => 'string',
					'description' => 'Name of the replaced block',
				),
				'new_block_name' => array(
					'type'        => 'string',
					'description' => 'Name of the new block',
				),
			),
		);
	}

	/**
	 * Execute the block replacement.
	 *
	 * @param array $args Arguments containing post_id, block_index, and new block data.
	 * @return array|object Result array or WP_Error.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id     = absint( $args['post_id'] );
		$block_index = absint( $args['block_index'] );
		$acf_fields  = $args['acf_fields'] ?? array();
		$post        = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', 'Post not found' );
		}

		if ( ! empty( $acf_fields ) && ! AcfHelper::is_acf_active() ) {
			return new \WP_Error( 'acf_not_active', 'Advanced Custom Fields plugin is not active.' );
		}

		$blocks = parse_blocks( $post->post_content );

		if ( ! isset( $blocks[ $block_index ] ) ) {
			return new \WP_Error(
				'block_not_found',
				sprintf( 'Block at index %d not found. Post has %d blocks.', $block_index, count( $blocks ) )
			);
		}

		// Get the old block name.
		$old_block_name = $blocks[ $block_index ]['blockName'] ?? 'unknown';

		// Build the new block data, generating a fresh ID for ACF association.
		$new_attrs = $args['attributes'] ?? array();

		if ( ! empty( $acf_fields ) && empty( $new_attrs['id'] ) ) {
			$new_attrs['id'] = AcfHelper::generate_block_id();
		}

		$new_block_data = array(
			'block'       => $args['block'],
			'attributes'  => $new_attrs,
			'innerBlocks' => $args['innerBlocks'] ?? array(),
		);

		$new_block = BlockRegistry::get_instance()->normalize_block( $new_block_data );

		if ( is_wp_error( $new_block ) ) {
			return $new_block;
		}

		// Replace the block at the specified index.
		$blocks[ $block_index ] = $new_block;

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_slash( serialize_blocks( $blocks ) ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$block_id = AcfHelper::extract_block_id( $new_block );

		if ( ! empty( $acf_fields ) && ! empty( $block_id ) ) {
			$acf_result = AcfHelper::update_block_fields( $block_id, $acf_fields );

			if ( is_wp_error( $acf_result ) ) {
				return $acf_result;
			}
		}

		$response = array(
			'replaced'       => true,
			'old_block_name' => $old_block_name,
			'new_block_name' => $args['block'],
		);

		if ( ! empty( $block_id ) ) {
			$response['block_id'] = $block_id;
		}

		return $response;
	}
}
