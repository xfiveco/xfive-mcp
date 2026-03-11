<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Buttons container block (core/buttons).
 */
class Buttons extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/buttons';
	}

	/**
	 * Generate HTML for the buttons container block.
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
	 * Get wrapper tags for the buttons container.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		// Buttons supports color.text: false, so skip textColor.
		$classes    = array_merge( array( 'wp-block-buttons' ), $this->build_classes( $attrs, array( 'textColor' ) ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return array(
			'opening' => '<div' . $class_attr . $style_attr . '>',
			'closing' => '</div>',
		);
	}
}
