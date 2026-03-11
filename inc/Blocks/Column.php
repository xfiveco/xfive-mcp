<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Column block (core/column).
 */
class Column extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/column';
	}

	/**
	 * Generate HTML for the column block.
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
	 * Get wrapper tags for the column container.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$classes = array_merge( array( 'wp-block-column' ), $this->build_classes( $attrs ) );

		if ( ! empty( $attrs['verticalAlignment'] ) ) {
			$classes[] = 'is-vertically-aligned-' . $attrs['verticalAlignment'];
		}

		$style_str = $this->build_styles( $attrs );

		if ( ! empty( $attrs['width'] ) ) {
			$style_str = 'flex-basis:' . $attrs['width'] . ( $style_str ? ';' . $style_str : '' );
		}

		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return array(
			'opening' => '<div' . $class_attr . $style_attr . '>',
			'closing' => '</div>',
		);
	}
}
