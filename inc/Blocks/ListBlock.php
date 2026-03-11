<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * List block (core/list).
 *
 * Named ListBlock to avoid conflict with PHP reserved word 'list'.
 */
class ListBlock extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/list';
	}

	/**
	 * Generate HTML for the list block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$wrapper = $this->get_wrapper( $attrs );

		return $wrapper['opening'] . ( ! empty( $content ) ? $content : '' ) . $wrapper['closing'];
	}

	/**
	 * Get wrapper tags for the list block.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$ordered = isset( $attrs['ordered'] ) && $attrs['ordered'];
		$tag     = $ordered ? 'ol' : 'ul';

		$classes    = array_merge( array( 'wp-block-list' ), $this->build_classes( $attrs ) );
		$style_str  = $this->build_styles( $attrs );
		$class_attr = $this->build_class_attr( $classes );
		$style_attr = $this->build_style_attr( $style_str );

		$extra_attrs = '';
		if ( $ordered && ! empty( $attrs['start'] ) ) {
			$extra_attrs .= ' start="' . (int) $attrs['start'] . '"';
		}
		if ( $ordered && ! empty( $attrs['reversed'] ) ) {
			$extra_attrs .= ' reversed';
		}
		if ( ! empty( $attrs['type'] ) ) {
			$extra_attrs .= ' type="' . esc_attr( $attrs['type'] ) . '"';
		}

		return array(
			'opening' => '<' . $tag . $class_attr . $style_attr . $extra_attrs . '>',
			'closing' => '</' . $tag . '>',
		);
	}
}
