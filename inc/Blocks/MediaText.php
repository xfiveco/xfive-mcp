<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Media & Text block (core/media-text).
 */
class MediaText extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/media-text';
	}

	/**
	 * Generate HTML for the media-text block.
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
	 * Get wrapper tags for the media-text block.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$media_id   = $attrs['mediaId'] ?? 0;
		$media_type = $attrs['mediaType'] ?? 'image';
		$media_url  = $attrs['mediaUrl'] ?? '';
		$media_alt  = $attrs['mediaAlt'] ?? '';

		$classes = array_merge( array( 'wp-block-media-text' ), $this->build_classes( $attrs ) );

		if ( ( $attrs['isStackedOnMobile'] ?? true ) !== false ) {
			$classes[] = 'is-stacked-on-mobile';
		}
		if ( isset( $attrs['mediaPosition'] ) && 'right' === $attrs['mediaPosition'] ) {
			$classes[] = 'has-media-on-the-right';
		}
		if ( isset( $attrs['verticalAlignment'] ) ) {
			$classes[] = 'is-vertically-aligned-' . $attrs['verticalAlignment'];
		}
		if ( isset( $attrs['imageFill'] ) && $attrs['imageFill'] ) {
			$classes[] = 'is-image-fill-element';
			$classes[] = 'is-image-fill';
		}

		$class_attr = $this->build_class_attr( $classes );

		$style_str   = $this->build_styles( $attrs );
		$extra_style = '';
		$media_width = $attrs['mediaWidth'] ?? 50;
		if ( 50 !== $media_width ) {
			$extra_style .= 'grid-template-columns:' . $media_width . '% auto';
		}
		if ( isset( $attrs['imageFill'] ) && $attrs['imageFill'] && ! empty( $attrs['focalPoint'] ) ) {
			$focal        = $attrs['focalPoint'];
			$x            = isset( $focal['x'] ) ? round( (float) $focal['x'] * 100 ) . '%' : '50%';
			$y            = isset( $focal['y'] ) ? round( (float) $focal['y'] * 100 ) . '%' : '50%';
			$extra_style .= ( $extra_style ? ';' : '' ) . '--wp--media-text--object-position:' . $x . ' ' . $y;
		}
		if ( $extra_style && $style_str ) {
			$style_str = $extra_style . ';' . $style_str;
		} elseif ( $extra_style ) {
			$style_str = $extra_style;
		}
		$style_attr = $this->build_style_attr( $style_str );

		$media_html = '<figure class="wp-block-media-text__media">';
		if ( ! empty( $media_url ) ) {
			if ( 'image' === $media_type ) {
				$img_classes = array();
				if ( ! empty( $media_id ) ) {
					$img_classes[] = 'wp-image-' . $media_id;
				}
				if ( ! empty( $attrs['mediaSizeSlug'] ) ) {
					$img_classes[] = 'size-' . $attrs['mediaSizeSlug'];
				} else {
					$img_classes[] = 'size-full';
				}
				$img_class_attr = ! empty( $img_classes ) ? ' class="' . esc_attr( implode( ' ', $img_classes ) ) . '"' : '';

				$img_style = '';
				if ( isset( $attrs['imageFill'] ) && $attrs['imageFill'] ) {
					$focal = $attrs['focalPoint'] ?? array();
					$x     = isset( $focal['x'] ) ? round( (float) $focal['x'] * 100 ) . '%' : '50%';
					$y     = isset( $focal['y'] ) ? round( (float) $focal['y'] * 100 ) . '%' : '50%';
					$img_style = ' style="object-position:' . $x . ' ' . $y . '"';
				}

				$media_html    .= sprintf( '<img src="%s" alt="%s"%s%s/>', esc_url( $media_url ), esc_attr( $media_alt ), $img_class_attr, $img_style );
			} elseif ( 'video' === $media_type ) {
				$media_html .= sprintf( '<video src="%s"></video>', esc_url( $media_url ) );
			}
		}
		$media_html .= '</figure>';

		return array(
			'opening' => '<div' . $class_attr . $style_attr . '>' . $media_html . '<div class="wp-block-media-text__content">',
			'closing' => '</div></div>',
		);
	}
}
