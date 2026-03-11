<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Spacer block (core/spacer).
 */
class Spacer extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/spacer';
	}

	/**
	 * Generate HTML for the spacer block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$height = $attrs['height'] ?? '100px';
		$width  = $attrs['width'] ?? '';

		$style_str = $this->build_styles( $attrs, array( 'border', 'typography', 'color' ) );

		if ( ! empty( $height ) ) {
			$style_str = 'height:' . $height . ( $style_str ? ';' . $style_str : '' );
		}
		if ( ! empty( $width ) ) {
			$style_str .= ( $style_str ? ';' : '' ) . 'width:' . $width;
		}

		$classes = array_merge(
			array( 'wp-block-spacer' ),
			$this->build_classes( $attrs, array( 'textColor', 'backgroundColor', 'gradient', 'fontSize', 'fontFamily', 'borderColor', 'align' ) )
		);

		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return sprintf(
			'<div%s aria-hidden="true"%s></div>',
			$style_attr,
			$class_attr
		);
	}
}
