<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Code block (core/code).
 */
class Code extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/code';
	}

	/**
	 * Generate HTML for the code block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$classes    = array_merge( array( 'wp-block-code' ), $this->build_classes( $attrs ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return sprintf(
			'<pre%s%s><code>%s</code></pre>',
			$class_attr,
			$style_attr,
			esc_html( $content )
		);
	}
}
