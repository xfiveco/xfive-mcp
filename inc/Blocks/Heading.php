<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Heading block (core/heading).
 */
class Heading extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/heading';
	}

	/**
	 * Generate HTML for the heading block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$level   = $attrs['level'] ?? 2;
		$classes = $this->build_classes( $attrs );

		if ( isset( $attrs['textAlign'] ) ) {
			array_unshift( $classes, 'has-text-align-' . $attrs['textAlign'] );
		}

		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return sprintf(
			'<h%d%s%s>%s</h%d>',
			$level,
			$class_attr,
			$style_attr,
			wp_kses_post( $content ),
			$level
		);
	}
}
