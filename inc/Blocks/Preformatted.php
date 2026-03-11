<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Preformatted block (core/preformatted).
 */
class Preformatted extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/preformatted';
	}

	/**
	 * Generate HTML for the preformatted block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$classes    = array_merge( array( 'wp-block-preformatted' ), $this->build_classes( $attrs ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return sprintf(
			'<pre%s%s>%s</pre>',
			$class_attr,
			$style_attr,
			wp_kses_post( $content )
		);
	}
}
