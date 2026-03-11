<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Columns block (core/columns).
 */
class Columns extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/columns';
	}

	/**
	 * Generate HTML for the columns block.
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
	 * Get wrapper tags for the columns container.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$classes = array_merge( array( 'wp-block-columns' ), $this->build_classes( $attrs ) );

		if ( isset( $attrs['isStackedOnMobile'] ) && false === $attrs['isStackedOnMobile'] ) {
			$classes[] = 'is-not-stacked-on-mobile';
		}

		if ( ! empty( $attrs['verticalAlignment'] ) ) {
			$classes[] = 'are-vertically-aligned-' . $attrs['verticalAlignment'];
		}

		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		return array(
			'opening' => '<div' . $class_attr . $style_attr . '>',
			'closing' => '</div>',
		);
	}
}
