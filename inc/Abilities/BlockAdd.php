<?php

namespace XfiveMCP\Abilities;

use XfiveMCP\Blocks\BlockRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Block Add Ability.
 *
 * Handles adding blocks to WordPress posts.
 */
class BlockAdd extends AbilitiesBase {

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
		return 'Block - Add';
	}

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	public function get_description(): string {
		return 'Add blocks to a post. Check block-schema for blocks structure.';
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
				'post_id'     => array(
					'type'        => 'integer',
					'description' => 'Target post ID',
				),
				'block'       => array(
					'type'        => 'string',
					'description' => 'Block name (e.g. core/paragraph)',
				),
				'attributes'  => array(
					'type'                 => 'object',
					'description'          => 'Block attributes (not REST-validated)',
					'additionalProperties' => true,
				),
				'innerBlocks' => array(
					'type'        => 'array',
					'description' => 'Nested blocks',
					'items'       => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
			),
			'required'   => array( 'post_id', 'block' ),
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
				'added' => array(
					'type'        => 'boolean',
					'description' => 'Whether the block was added successfully',
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
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', 'Post not found' );
		}

		$blocks    = parse_blocks( $post->post_content );
		$new_block = BlockRegistry::get_instance()->normalize_block( $args );

		if ( is_wp_error( $new_block ) ) {
			return $new_block;
		}

		$blocks[] = $new_block;

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
			'added'      => true,
			'block_name' => $args['block'] ?? $args['blockName'] ?? '',
		);
	}
}
