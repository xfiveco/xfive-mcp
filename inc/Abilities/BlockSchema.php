<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class BlockSchema extends AbilitiesBase {

	/**
	 * Get configuration for the blocks schema ability.
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
		return 'Block - Schema';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Retrieve the schema and configuration for a specific block.';
	}

	/**
	 * Get the input schema for the ability.
	 *
	 * @return array Empty array as no input parameters are required.
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'block_name' => array(
					'type'        => 'string',
					'description' => 'Block name (e.g., core/paragraph)',
				),
			),
		);
	}

	/**
	 * Get the output schema for the ability.
	 *
	 * @return array Schema defining the structure of returned blocks data.
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'block' => array(
					'type'        => 'object',
					'description' => 'Block schema',
					'properties'  => array(
						'name'        => array(
							'type'        => 'string',
							'description' => 'Block name (e.g., core/paragraph)',
						),
						'title'       => array(
							'type'        => 'string',
							'description' => 'Block display title',
						),
						'category'    => array(
							'type'        => 'string',
							'description' => 'Block category',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Block description',
						),
						'attributes'  => array(
							'type'        => 'object',
							'description' => 'Block attributes schema',
						),
						'supports'    => array(
							'type'        => 'object',
							'description' => 'Block editor supports',
						),
					),
				),
			),
		);
	}

	/**
	 * Execute the blocks schema retrieval.
	 *
	 * Returns all registered WordPress blocks with their metadata including:
	 * - Block name and title.
	 * - Category and description.
	 * - Attributes schema.
	 * - Editor supports.
	 *
	 * @param array $args Arguments (none required).
	 * @return array Array containing blocks data and count.
	 */
	public function execute_callback( array $args = array() ): array {
		if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
			return array();
		}

		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_name = $args['block_name'] ?? '';
		$block_type = $registry->get_registered( $block_name );

		if ( ! $block_type ) {
			return array();
		}

		$block_data = array(
			'name'        => $block_name,
			'title'       => $block_type->title ?? '',
			'category'    => $block_type->category ?? '',
			'description' => $block_type->description ?? '',
			'attributes'  => array(),
			'supports'    => array(),
		);

		if ( isset( $block_type->attributes ) && is_array( $block_type->attributes ) ) {
			foreach ( $block_type->attributes as $attr_name => $attr_schema ) {
				$block_data['attributes'][ $attr_name ] = array(
					'type'    => $attr_schema['type'] ?? 'string',
					'default' => $attr_schema['default'] ?? null,
				);

				if ( isset( $attr_schema['enum'] ) ) {
					$block_data['attributes'][ $attr_name ]['enum'] = $attr_schema['enum'];
				}
			}
		}

		if ( isset( $block_type->supports ) && is_array( $block_type->supports ) ) {
			$block_data['supports'] = $block_type->supports;
		}

		return $block_data;
	}
}
