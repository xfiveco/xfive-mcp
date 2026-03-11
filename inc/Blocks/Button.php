<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Button block (core/button).
 */
class Button extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/button';
	}

	/**
	 * Generate HTML for the button block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$url  = $attrs['url'] ?? '#';
		$text = $content;

		// Color/font/border classes go on the <a> element, not the wrapper.
		$link_classes   = array_merge( array( 'wp-block-button__link' ), $this->build_classes( $attrs, array( 'align' ) ) );
		// Remove className from link classes — it goes on the wrapper div.
		$link_classes   = array_diff( $link_classes, array( $attrs['className'] ?? '' ) );
		$link_classes[] = 'wp-element-button';

		$style_str  = $this->build_styles( $attrs );
		$style_attr = $this->build_style_attr( $style_str );

		$link_extra = '';
		if ( ! empty( $attrs['linkTarget'] ) ) {
			$link_extra .= ' target="' . esc_attr( $attrs['linkTarget'] ) . '"';
		}
		if ( ! empty( $attrs['rel'] ) ) {
			$link_extra .= ' rel="' . esc_attr( $attrs['rel'] ) . '"';
		}

		$wrapper_classes = array( 'wp-block-button' );
		if ( ! empty( $attrs['className'] ) ) {
			$wrapper_classes[] = $attrs['className'];
		}

		return sprintf(
			'<div class="%s"><a class="%s" href="%s"%s%s>%s</a></div>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( implode( ' ', array_values( $link_classes ) ) ),
			esc_url( $url ),
			$style_attr,
			$link_extra,
			esc_html( $text )
		);
	}

	/**
	 * Auto-wrap a standalone button block in a buttons container.
	 *
	 * @param array $normalized_block The already-normalized button block.
	 * @return array The block wrapped in core/buttons.
	 */
	public function auto_wrap( array $normalized_block ): array {
		return array(
			'blockName'    => 'core/buttons',
			'attrs'        => array(),
			'innerBlocks'  => array( $normalized_block ),
			'innerHTML'    => '<div class="wp-block-buttons"></div>',
			'innerContent' => array(
				'<div class="wp-block-buttons">',
				null,
				'</div>',
			),
		);
	}
}
