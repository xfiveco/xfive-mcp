<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Group block (core/group).
 */
class Group extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/group';
	}

	/**
	 * Generate HTML for the group block.
	 *
	 * Container block — content comes from inner blocks.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		return '';
	}

	/**
	 * Get wrapper tags for the group container.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$tag = $attrs['tagName'] ?? 'div';

		$classes    = array_merge( array( 'wp-block-group' ), $this->build_classes( $attrs ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return array(
			'opening' => '<' . $tag . $class_attr . $style_attr . '>',
			'closing' => '</' . $tag . '>',
		);
	}
}
