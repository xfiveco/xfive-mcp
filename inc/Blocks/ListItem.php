<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * List item block (core/list-item).
 */
class ListItem extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/list-item';
	}

	/**
	 * Generate HTML for the list item block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		// List item has supports.className: false, so no auto class.
		$classes    = $this->build_classes( $attrs, array( 'align' ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return '<li' . $class_attr . $style_attr . '>' . wp_kses_post( $content ) . '</li>';
	}

	/**
	 * Get wrapper tags for the list item block.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$classes    = $this->build_classes( $attrs, array( 'align' ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return array(
			'opening' => '<li' . $class_attr . $style_attr . '>',
			'closing' => '</li>',
		);
	}
}
