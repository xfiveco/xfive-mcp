<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gallery block (core/gallery).
 */
class Gallery extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/gallery';
	}

	/**
	 * Generate HTML for the gallery block.
	 *
	 * Container block — content comes from inner blocks (images).
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
	 * Get wrapper tags for the gallery container.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		// Gallery supports color.text: false, so skip textColor.
		// Gallery supports spacing.__experimentalSkipSerialization: ['blockGap'], so blockGap is handled server-side.
		$classes = array_merge(
			array( 'wp-block-gallery', 'has-nested-images' ),
			$this->build_classes( $attrs, array( 'textColor', 'fontSize', 'fontFamily' ) )
		);

		$columns   = $attrs['columns'] ?? null;
		$image_crop = $attrs['imageCrop'] ?? true;

		if ( null !== $columns ) {
			$classes[] = 'columns-' . (int) $columns;
		} else {
			$classes[] = 'columns-default';
		}

		if ( $image_crop ) {
			$classes[] = 'is-cropped';
		}

		$class_attr = $this->build_class_attr( $classes );

		// Skip blockGap from styles (experimentalSkipSerialization).
		$style_str  = $this->build_styles( $attrs );
		$style_attr = $this->build_style_attr( $style_str );

		$caption      = $attrs['caption'] ?? '';
		$caption_html = '';
		if ( ! empty( $caption ) ) {
			$caption_html = '<figcaption class="blocks-gallery-caption wp-element-caption">' . wp_kses_post( $caption ) . '</figcaption>';
		}

		return array(
			'opening' => '<figure' . $class_attr . $style_attr . '>',
			'closing' => $caption_html . '</figure>',
		);
	}
}
