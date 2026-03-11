<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Quote block (core/quote).
 */
class Quote extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/quote';
	}

	/**
	 * Generate HTML for the quote block.
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
	 * Get wrapper tags for the quote block.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$classes    = array_merge( array( 'wp-block-quote' ), $this->build_classes( $attrs ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		$citation_html = '';
		if ( ! empty( $attrs['citation'] ) ) {
			$citation_html = '<cite>' . wp_kses_post( $attrs['citation'] ) . '</cite>';
		}

		return array(
			'opening' => '<blockquote' . $class_attr . $style_attr . '>',
			'closing' => $citation_html . '</blockquote>',
		);
	}
}
