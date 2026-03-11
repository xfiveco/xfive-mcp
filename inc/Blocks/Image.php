<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Image block (core/image).
 */
class Image extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/image';
	}

	/**
	 * Generate HTML for the image block.
	 *
	 * @param string $content     Block content.
	 * @param array  $attrs       Block attributes.
	 * @param array  $inner_blocks Inner blocks.
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$url     = $attrs['url'] ?? '';
		$alt     = $attrs['alt'] ?? '';
		$id      = $attrs['id'] ?? 0;
		$caption = $attrs['caption'] ?? '';

		if ( empty( $url ) ) {
			return '';
		}

		// Build image tag with optional attachment ID and size classes.
		$img_classes = array();
		if ( ! empty( $id ) ) {
			$img_classes[] = 'wp-image-' . $id;
		}

		$img_class_attr = ! empty( $img_classes ) ? ' class="' . esc_attr( implode( ' ', $img_classes ) ) . '"' : '';

		$img_extra = '';
		if ( ! empty( $attrs['width'] ) ) {
			$img_extra .= ' width="' . esc_attr( $attrs['width'] ) . '"';
		}
		if ( ! empty( $attrs['height'] ) ) {
			$img_extra .= ' height="' . esc_attr( $attrs['height'] ) . '"';
		}

		$img_tag = sprintf(
			'<img src="%s" alt="%s"%s%s/>',
			esc_url( $url ),
			esc_attr( $alt ),
			$img_class_attr,
			$img_extra
		);

		// Wrap in link if href is set.
		if ( ! empty( $attrs['href'] ) ) {
			$link_attrs = ' href="' . esc_url( $attrs['href'] ) . '"';
			if ( ! empty( $attrs['linkTarget'] ) ) {
				$link_attrs .= ' target="' . esc_attr( $attrs['linkTarget'] ) . '"';
			}
			if ( ! empty( $attrs['rel'] ) ) {
				$link_attrs .= ' rel="' . esc_attr( $attrs['rel'] ) . '"';
			}
			if ( ! empty( $attrs['linkClass'] ) ) {
				$link_attrs .= ' class="' . esc_attr( $attrs['linkClass'] ) . '"';
			}
			$img_tag = '<a' . $link_attrs . '>' . $img_tag . '</a>';
		}

		// Build figure classes. Image has no color/border serialization in save.
		$figure_classes = array_merge(
			array( 'wp-block-image' ),
			$this->build_classes( $attrs, array( 'textColor', 'backgroundColor', 'gradient', 'fontSize', 'fontFamily', 'borderColor' ) )
		);

		if ( ! empty( $attrs['sizeSlug'] ) ) {
			$figure_classes[] = 'size-' . $attrs['sizeSlug'];
		}

		$class_attr = $this->build_class_attr( $figure_classes );

		// Wrap in figure with optional caption.
		if ( ! empty( $caption ) ) {
			return sprintf(
				'<figure%s>%s<figcaption class="wp-element-caption">%s</figcaption></figure>',
				$class_attr,
				$img_tag,
				wp_kses_post( $caption )
			);
		}

		return sprintf(
			'<figure%s>%s</figure>',
			$class_attr,
			$img_tag
		);
	}
}
