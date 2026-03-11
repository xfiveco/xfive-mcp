<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cover block (core/cover).
 */
class Cover extends BlockBase {

	/**
	 * Position class names matching WordPress JS save function.
	 */
	private const POSITION_CLASSNAMES = array(
		'top left'      => 'is-position-top-left',
		'top center'    => 'is-position-top-center',
		'top right'     => 'is-position-top-right',
		'center left'   => 'is-position-center-left',
		'center center' => 'is-position-center-center',
		'center'        => 'is-position-center-center',
		'center right'  => 'is-position-center-right',
		'bottom left'   => 'is-position-bottom-left',
		'bottom center' => 'is-position-bottom-center',
		'bottom right'  => 'is-position-bottom-right',
	);

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/cover';
	}

	/**
	 * Generate HTML for the cover block.
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
	 * Get wrapper tags for the cover container.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Wrapper array.
	 */
	public function get_wrapper( array $attrs ): ?array {
		$tag              = $attrs['tagName'] ?? 'div';
		$background_type  = $attrs['backgroundType'] ?? 'image';
		$url              = $attrs['url'] ?? '';
		$alt              = $attrs['alt'] ?? '';
		$id               = $attrs['id'] ?? 0;
		$has_parallax     = ! empty( $attrs['hasParallax'] );
		$is_repeated      = ! empty( $attrs['isRepeated'] );
		$dim_ratio        = $attrs['dimRatio'] ?? 100;
		$overlay_color    = $attrs['overlayColor'] ?? '';
		$custom_overlay   = $attrs['customOverlayColor'] ?? '';
		$gradient         = $attrs['gradient'] ?? '';
		$custom_gradient  = $attrs['customGradient'] ?? '';
		$focal_point      = $attrs['focalPoint'] ?? null;
		$min_height       = $attrs['minHeight'] ?? null;
		$min_height_unit  = $attrs['minHeightUnit'] ?? null;
		$is_dark          = $attrs['isDark'] ?? true;
		$content_position = $attrs['contentPosition'] ?? null;
		$size_slug        = $attrs['sizeSlug'] ?? '';
		$use_featured     = ! empty( $attrs['useFeaturedImage'] );
		$poster           = $attrs['poster'] ?? '';

		$is_img_element = ! ( $has_parallax || $is_repeated );

		// Cover supports color.background: false, so skip backgroundColor.
		// Cover supports color.__experimentalSkipSerialization: ['gradients'], so skip gradient classes.
		$classes = array_merge(
			array( 'wp-block-cover' ),
			$this->build_classes( $attrs, array( 'backgroundColor', 'gradient' ) )
		);

		if ( ! $is_dark ) {
			$classes[] = 'is-light';
		}
		if ( $has_parallax ) {
			$classes[] = 'has-parallax';
		}
		if ( $is_repeated ) {
			$classes[] = 'is-repeated';
		}
		if ( ! $this->is_content_position_center( $content_position ) ) {
			$classes[] = 'has-custom-content-position';
			$position_class = $this->get_position_class_name( $content_position );
			if ( $position_class ) {
				$classes[] = $position_class;
			}
		}

		$class_attr = $this->build_class_attr( $classes );

		// Build wrapper style.
		$style_str = $this->build_styles( $attrs );
		if ( $min_height ) {
			$min_h = $min_height_unit ? $min_height . $min_height_unit : $min_height;
			$style_str = 'min-height:' . $min_h . ( $style_str ? ';' . $style_str : '' );
		}
		$style_attr = $this->build_style_attr( $style_str );

		// Build media HTML.
		$media_html = '';
		if ( ! $use_featured && 'image' === $background_type && ! empty( $url ) ) {
			$media_html = $this->build_image_html( $url, $alt, $id, $size_slug, $has_parallax, $is_repeated, $focal_point, $is_img_element );
		} elseif ( 'video' === $background_type && ! empty( $url ) ) {
			$media_html = $this->build_video_html( $url, $poster, $focal_point, $is_img_element );
		}

		// Build overlay span.
		$overlay_html = $this->build_overlay_html( $overlay_color, $custom_overlay, $gradient, $custom_gradient, $dim_ratio, $url );

		$opening = '<' . $tag . $class_attr . $style_attr . '>'
			. $media_html
			. $overlay_html
			. '<div class="wp-block-cover__inner-container">';

		return array(
			'opening' => $opening,
			'closing' => '</div></' . $tag . '>',
		);
	}

	/**
	 * Build image background HTML.
	 *
	 * @param string     $url          Image URL.
	 * @param string     $alt          Alt text.
	 * @param int        $id           Attachment ID.
	 * @param string     $size_slug    Size slug.
	 * @param bool       $has_parallax Whether parallax is enabled.
	 * @param bool       $is_repeated  Whether image is repeated.
	 * @param array|null $focal_point  Focal point coordinates.
	 * @param bool       $is_img       Whether to render as img element.
	 * @return string Image HTML.
	 */
	private function build_image_html( string $url, string $alt, int $id, string $size_slug, bool $has_parallax, bool $is_repeated, ?array $focal_point, bool $is_img ): string {
		$img_classes = array( 'wp-block-cover__image-background' );
		if ( ! empty( $id ) ) {
			$img_classes[] = 'wp-image-' . $id;
		}
		if ( ! empty( $size_slug ) ) {
			$img_classes[] = 'size-' . $size_slug;
		}
		if ( $has_parallax ) {
			$img_classes[] = 'has-parallax';
		}
		if ( $is_repeated ) {
			$img_classes[] = 'is-repeated';
		}

		$img_class_str = esc_attr( implode( ' ', $img_classes ) );

		if ( $is_img ) {
			$style_attr = '';
			$data_attrs = ' data-object-fit="cover"';

			if ( ! empty( $focal_point ) ) {
				$object_position = $this->media_position( $focal_point );
				$style_attr      = ' style="' . esc_attr( 'object-position:' . $object_position ) . '"';
				$data_attrs     .= ' data-object-position="' . esc_attr( $object_position ) . '"';
			}

			return sprintf(
				'<img class="%s" alt="%s" src="%s"%s%s/>',
				$img_class_str,
				esc_attr( $alt ),
				esc_url( $url ),
				$style_attr,
				$data_attrs
			);
		}

		// Div-based background (parallax or repeated).
		$bg_position = $this->media_position( $focal_point );
		$div_style   = 'background-position:' . ( $bg_position ? $bg_position : '50% 50%' );
		$div_style  .= ';background-image:url(' . esc_url( $url ) . ')';
		$role_attr   = ! empty( $alt ) ? ' role="img" aria-label="' . esc_attr( $alt ) . '"' : '';

		return sprintf(
			'<div%s class="%s" style="%s"></div>',
			$role_attr,
			$img_class_str,
			esc_attr( $div_style )
		);
	}

	/**
	 * Build video background HTML.
	 *
	 * @param string     $url         Video URL.
	 * @param string     $poster      Poster image URL.
	 * @param array|null $focal_point Focal point coordinates.
	 * @param bool       $is_img      Whether using img element mode.
	 * @return string Video HTML.
	 */
	private function build_video_html( string $url, string $poster, ?array $focal_point, bool $is_img ): string {
		$object_position = $this->media_position( $focal_point );
		$style_str       = $object_position ? 'object-position:' . $object_position : '';
		$style_attr      = $style_str ? ' style="' . esc_attr( $style_str ) . '"' : '';
		$data_attrs      = ' data-object-fit="cover"';
		if ( $object_position ) {
			$data_attrs .= ' data-object-position="' . esc_attr( $object_position ) . '"';
		}
		$poster_attr = ! empty( $poster ) ? ' poster="' . esc_url( $poster ) . '"' : '';

		return sprintf(
			'<video class="wp-block-cover__video-background intrinsic-ignore" autoplay muted loop playsinline src="%s"%s%s%s></video>',
			esc_url( $url ),
			$poster_attr,
			$style_attr,
			$data_attrs
		);
	}

	/**
	 * Build overlay span HTML.
	 *
	 * @param string $overlay_color   Preset overlay color slug.
	 * @param string $custom_overlay  Custom overlay color value.
	 * @param string $gradient        Preset gradient slug.
	 * @param string $custom_gradient Custom gradient value.
	 * @param int    $dim_ratio       Dim ratio (0-100).
	 * @param string $url             Background media URL.
	 * @return string Overlay span HTML.
	 */
	private function build_overlay_html( string $overlay_color, string $custom_overlay, string $gradient, string $custom_gradient, int $dim_ratio, string $url ): string {
		$span_classes = array( 'wp-block-cover__background' );

		if ( ! empty( $overlay_color ) ) {
			$span_classes[] = 'has-' . $overlay_color . '-background-color';
		}

		// dimRatioToClass: ratio === 50 || ratio === undefined → null, else 'has-background-dim-' + 10 * round(ratio / 10).
		$dim_class = $this->dim_ratio_to_class( $dim_ratio );
		if ( $dim_class ) {
			$span_classes[] = $dim_class;
		}

		if ( null !== $dim_ratio ) {
			$span_classes[] = 'has-background-dim';
		}

		// Gradient background class for backwards compat.
		$gradient_value = $gradient || $custom_gradient;
		if ( ! empty( $url ) && $gradient_value && 0 !== $dim_ratio ) {
			$span_classes[] = 'wp-block-cover__gradient-background';
		}

		if ( $gradient_value ) {
			$span_classes[] = 'has-background-gradient';
		}

		if ( ! empty( $gradient ) ) {
			$span_classes[] = 'has-' . $gradient . '-gradient-background';
		}

		$class_attr = ' class="' . esc_attr( implode( ' ', $span_classes ) ) . '"';

		// Build inline style for overlay.
		$span_styles = array();
		if ( ! empty( $custom_overlay ) && empty( $overlay_color ) ) {
			$span_styles[] = 'background-color:' . $custom_overlay;
		}
		if ( ! empty( $custom_gradient ) ) {
			$span_styles[] = 'background:' . $custom_gradient;
		}
		$style_attr = ! empty( $span_styles ) ? ' style="' . esc_attr( implode( ';', $span_styles ) ) . '"' : '';

		return '<span aria-hidden="true"' . $class_attr . $style_attr . '></span>';
	}

	/**
	 * Convert focal point to CSS position string.
	 *
	 * @param array|null $focal_point Focal point with x and y (0-1).
	 * @return string CSS position (e.g., "50% 50%").
	 */
	private function media_position( ?array $focal_point ): string {
		if ( empty( $focal_point ) ) {
			return '50% 50%';
		}
		$x = isset( $focal_point['x'] ) ? round( (float) $focal_point['x'] * 100 ) . '%' : '50%';
		$y = isset( $focal_point['y'] ) ? round( (float) $focal_point['y'] * 100 ) . '%' : '50%';
		return $x . ' ' . $y;
	}

	/**
	 * Convert dim ratio to CSS class.
	 *
	 * @param int|null $ratio Dim ratio (0-100).
	 * @return string|null CSS class or null.
	 */
	private function dim_ratio_to_class( ?int $ratio ): ?string {
		if ( 50 === $ratio || null === $ratio ) {
			return null;
		}
		return 'has-background-dim-' . ( 10 * (int) round( $ratio / 10 ) );
	}

	/**
	 * Check if content position is center (default).
	 *
	 * @param string|null $content_position Content position value.
	 * @return bool True if center or not set.
	 */
	private function is_content_position_center( ?string $content_position ): bool {
		return empty( $content_position )
			|| 'center center' === $content_position
			|| 'center' === $content_position;
	}

	/**
	 * Get CSS class name for content position.
	 *
	 * @param string|null $content_position Content position value.
	 * @return string CSS class name.
	 */
	private function get_position_class_name( ?string $content_position ): string {
		if ( $this->is_content_position_center( $content_position ) ) {
			return '';
		}
		return self::POSITION_CLASSNAMES[ $content_position ] ?? '';
	}
}
