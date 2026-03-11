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
		return 'Displays blocks tree for a post';
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
					'description' => 'Post ID',
				),
			),
		);
	}

	/**
	 * Get output schema.
	 *
	 * @return array Output schema definition.
	 */
	public function get_output_schema(): array {
		return array(
			'type'  => 'object',
			'items' => array(
				'type' => 'array',
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
		$post = get_post( $args['post_id'] );
		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		$blocks = parse_blocks( $post->post_content );

		if ( empty( $blocks ) ) {
			return array();
		}

		$blocks = array_filter(
			$blocks,
			function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		);

		if ( empty( $blocks ) ) {
			return array();
		}

		return $blocks;
	}
}
