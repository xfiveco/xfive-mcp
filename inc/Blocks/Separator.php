<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Separator block (core/separator).
 */
class Separator extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/separator';
	}

	/**
	 * Generate HTML for the separator block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		// Separator has __experimentalSkipSerialization for color, so skip color/border classes.
		$classes = array_merge(
			array( 'wp-block-separator' ),
			$this->build_classes( $attrs, array( 'textColor', 'backgroundColor', 'gradient', 'fontSize', 'fontFamily', 'borderColor' ) )
		);

		$opacity   = $attrs['opacity'] ?? 'alpha-channel';
		$classes[] = 'has-' . $opacity . '-opacity';

		$class_attr = $this->build_class_attr( $classes );

		return sprintf(
			'<hr%s/>',
			$class_attr
		);
	}
}
