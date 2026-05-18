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
		return 'Retrieve the registration schema (attributes + supports) for a single block by its full name. ALWAYS call this before inserting block markup so attribute keys match exactly. Chisel ACF blocks use prefix "chisel/" not "acf/" (e.g. chisel/team-members, NOT acf/team-members).';
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
					'description' => 'Full block name with namespace, e.g. "core/paragraph", "chisel/team-members". Chisel ACF blocks use "chisel/", NEVER "acf/".',
				),
			),
			'required'   => array( 'block_name' ),
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
				'name'        => array(
					'type'        => 'string',
					'description' => 'Full block name (e.g. core/paragraph).',
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'Block display title.',
				),
				'category'    => array(
					'type'        => 'string',
					'description' => 'Block category.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Block description.',
				),
				'attributes'  => array(
					'type'        => 'object',
					'description' => 'Block attributes schema (name => { type, default, enum? }).',
				),
				'supports'    => array(
					'type'        => 'object',
					'description' => 'Block editor supports.',
				),
				'renderMode'  => array(
					'type'        => 'string',
					'description' => '"dynamic" (server render_callback, e.g. ACF blocks or core/latest-posts) or "static" (client save() returns JSX, e.g. core/paragraph, custom WP blocks).',
				),
				'seedAs'      => array(
					'type'        => 'string',
					'description' => 'Required block-comment shape when writing markup. Static blocks MUST be seeded as opening+closing tags with the rendered inner HTML between them. Only dynamic blocks may be self-closing.',
				),
				'hint'        => array(
					'type' => 'string',
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
		$block_name = $args['block_name'] ?? '';

		if ( ! class_exists( '\WP_Block_Type_Registry' ) || empty( $block_name ) ) {
			return array(
				'hint' => 'block_name is required (e.g. "core/paragraph", "chisel/values").',
			);
		}

		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $block_name );

		if ( ! $block_type ) {
			$hint = sprintf( 'Block "%s" is not registered. ', $block_name );

			if ( strpos( $block_name, 'acf/' ) === 0 ) {
				$alt   = 'chisel/' . substr( $block_name, 4 );
				$hint .= sprintf( 'Chisel ACF blocks use the "chisel/" prefix — try "%s".', $alt );
			} elseif ( strpos( $block_name, 'chisel/' ) === 0 ) {
				$hint .= 'If the block was just created, ask the user to run `npm run build-scripts` so block.json is copied to build/ — then retry.';
			} else {
				$hint .= 'Check the namespace prefix (core/, chisel/, etc.) and confirm the slug matches block.json "name".';
			}

			return array( 'hint' => $hint );
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

		$is_dynamic               = ! empty( $block_type->render_callback );
		$block_data['renderMode'] = $is_dynamic ? 'dynamic' : 'static';
		$block_data['seedAs']     = $is_dynamic
			? 'Self-closing OK: <!-- wp:' . $block_name . ' {ATTRS} /-->'
			: 'MUST be paired with rendered inner HTML: <!-- wp:' . $block_name . ' {ATTRS} -->INNER_HTML<!-- /wp:' . $block_name . ' -->. Self-closing form will trigger "Block validation failed" in the editor.';

		return $block_data;
	}
}
