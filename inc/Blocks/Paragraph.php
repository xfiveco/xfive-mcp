<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Paragraph block (core/paragraph).
 */
class Paragraph extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/paragraph';
	}

	/**
	 * Generate HTML for the paragraph block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		// Paragraph uses 'align' for text alignment, not block alignment.
		$classes = $this->build_classes( $attrs, array( 'align' ) );

		if ( isset( $attrs['align'] ) ) {
			array_unshift( $classes, 'has-text-align-' . $attrs['align'] );
		}

		if ( ! empty( $attrs['dropCap'] ) ) {
			$classes[] = 'has-drop-cap';
		}

		$style_str = $this->build_styles( $attrs );

		if ( ! empty( $attrs['direction'] ) ) {
			$style_str = 'direction:' . $attrs['direction'] . ( $style_str ? ';' . $style_str : '' );
		}

		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return sprintf(
			'<p%s%s>%s</p>',
			$class_attr,
			$style_attr,
			wp_kses_post( $content )
		);
	}
}
