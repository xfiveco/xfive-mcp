<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * BlockMove Ability
 *
 * Moves a block from one position to another within a post.
 */
class BlockMove extends AbilitiesBase {

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
		return 'Block - Move';
	}

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	public function get_description(): string {
		return 'Move a block to a different position in a post';
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
				'post_id'    => array(
					'type'        => 'integer',
					'description' => 'Target post ID',
				),
				'from_index' => array(
					'type'        => 'integer',
					'description' => 'Current index of the block to move (0-based)',
				),
				'to_index'   => array(
					'type'        => 'integer',
					'description' => 'New index where the block should be moved (0-based)',
				),
			),
			'required'   => array( 'post_id', 'from_index', 'to_index' ),
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
				'moved'      => array(
					'type'        => 'boolean',
					'description' => 'Whether the block was moved successfully',
				),
				'block_name' => array(
					'type'        => 'string',
					'description' => 'Name of the moved block',
				),
				'from_index' => array(
					'type'        => 'integer',
					'description' => 'Original index',
				),
				'to_index'   => array(
					'type'        => 'integer',
					'description' => 'New index',
				),
			),
		);
	}

	/**
	 * Execute the block move operation.
	 *
	 * @param array $args Arguments containing post_id, from_index, and to_index.
	 * @return array|object Result array or WP_Error.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id    = absint( $args['post_id'] );
		$from_index = absint( $args['from_index'] );
		$to_index   = absint( $args['to_index'] );
		$post       = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'post_not_found', 'Post not found' );
		}

		$blocks = parse_blocks( $post->post_content );

		if ( ! isset( $blocks[ $from_index ] ) ) {
			return new \WP_Error(
				'block_not_found',
				sprintf( 'Block at index %d not found. Post has %d blocks.', $from_index, count( $blocks ) )
			);
		}

		if ( $to_index < 0 || $to_index >= count( $blocks ) ) {
			return new \WP_Error(
				'invalid_to_index',
				sprintf( 'Invalid to_index %d. Must be between 0 and %d.', $to_index, count( $blocks ) - 1 )
			);
		}

		if ( $from_index === $to_index ) {
			return array(
				'moved'      => false,
				'block_name' => $blocks[ $from_index ]['blockName'] ?? 'unknown',
				'from_index' => $from_index,
				'to_index'   => $to_index,
			);
		}

		$block_to_move = $blocks[ $from_index ];
		$block_name    = $block_to_move['blockName'] ?? 'unknown';

		// Remove the block from its current position.
		array_splice( $blocks, $from_index, 1 );

		// Insert the block at the new position.
		array_splice( $blocks, $to_index, 0, array( $block_to_move ) );

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
			'moved'      => true,
			'block_name' => $block_name,
			'from_index' => $from_index,
			'to_index'   => $to_index,
		);
	}
}
