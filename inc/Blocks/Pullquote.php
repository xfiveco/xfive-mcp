<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Pullquote block (core/pullquote).
 */
class Pullquote extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/pullquote';
	}

	/**
	 * Generate HTML for the pullquote block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$classes    = array_merge( array( 'wp-block-pullquote' ), $this->build_classes( $attrs ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		$citation_html = '';
		if ( ! empty( $attrs['citation'] ) ) {
			$citation_html = '<cite>' . wp_kses_post( $attrs['citation'] ) . '</cite>';
		}

		return sprintf(
			'<figure%s%s><blockquote><p>%s</p>%s</blockquote></figure>',
			$class_attr,
			$style_attr,
			wp_kses_post( $content ),
			$citation_html
		);
	}
}
