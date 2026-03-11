<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract base class for all block types.
 *
 * Each block class handles its own HTML generation and wrapper logic.
 */
abstract class BlockBase {

	/**
	 * Get the block name (e.g., 'core/paragraph').
	 *
	 * @return string Block name.
	 */
	abstract public function get_name(): string;

	/**
	 * Generate innerHTML for this block.
	 *
	 * @param string $content     Text content extracted from attributes.
	 * @param array  $attrs       Filtered block attributes (without content/text).
	 * @param array  $inner_blocks Normalized inner blocks array.
	 * @return string Generated HTML.
	 */
	abstract public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string;

	/**
	 * Get wrapper opening/closing tags for container blocks.
	 *
	 * Override in container blocks. Returns null for leaf blocks.
	 *
	 * @param array $attrs Block attributes.
	 * @return array|null Array with 'opening' and 'closing' keys, or null.
	 */
	public function get_wrapper( array $attrs ): ?array {
		return null;
	}

	/**
	 * Build common CSS classes from block attributes.
	 *
	 * Handles textColor, backgroundColor, fontSize, className, align, gradient,
	 * fontFamily, borderColor, and style-derived classes (has-border-color).
	 *
	 * @param array $attrs         Block attributes.
	 * @param array $skip_features Features to skip (e.g., ['textColor'] for blocks with text:false).
	 * @return array CSS class names.
	 */
	protected function build_classes( array $attrs, array $skip_features = array() ): array {
		$classes = array();

		if ( ! in_array( 'textColor', $skip_features, true ) && ! empty( $attrs['textColor'] ) ) {
			$classes[] = 'has-' . $attrs['textColor'] . '-color';
			$classes[] = 'has-text-color';
		}

		if ( ! in_array( 'backgroundColor', $skip_features, true ) && ! empty( $attrs['backgroundColor'] ) ) {
			$classes[] = 'has-' . $attrs['backgroundColor'] . '-background-color';
			$classes[] = 'has-background';
		}

		if ( ! in_array( 'gradient', $skip_features, true ) && ! empty( $attrs['gradient'] ) ) {
			$classes[] = 'has-' . $attrs['gradient'] . '-gradient-background';
			$classes[] = 'has-background';
		}

		if ( ! in_array( 'fontSize', $skip_features, true ) && ! empty( $attrs['fontSize'] ) ) {
			$classes[] = 'has-' . $attrs['fontSize'] . '-font-size';
		}

		if ( ! in_array( 'fontFamily', $skip_features, true ) && ! empty( $attrs['fontFamily'] ) ) {
			$classes[] = 'has-' . $attrs['fontFamily'] . '-font-family';
		}

		if ( ! in_array( 'align', $skip_features, true ) && ! empty( $attrs['align'] ) ) {
			$classes[] = 'align' . $attrs['align'];
		}

		if ( ! in_array( 'borderColor', $skip_features, true ) ) {
			if ( ! empty( $attrs['borderColor'] ) ) {
				$classes[] = 'has-border-color';
				$classes[] = 'has-' . $attrs['borderColor'] . '-border-color';
			} elseif ( ! empty( $attrs['style']['border']['color'] ) ) {
				$classes[] = 'has-border-color';
			}
		}

		if ( ! empty( $attrs['className'] ) ) {
			$classes[] = $attrs['className'];
		}

		return $classes;
	}

	/**
	 * Build inline style string from the style attribute.
	 *
	 * Handles border, spacing (margin/padding), and typography styles.
	 *
	 * @param array $attrs         Block attributes.
	 * @param array $skip_features Features to skip (e.g., ['border'] for blocks with skipSerialization).
	 * @return string Inline style string (without the style="" wrapper).
	 */
	protected function build_styles( array $attrs, array $skip_features = array() ): string {
		$styles = array();
		$style  = $attrs['style'] ?? array();

		// Border styles.
		if ( ! in_array( 'border', $skip_features, true ) && ! empty( $style['border'] ) ) {
			$border = $style['border'];
			if ( ! empty( $border['color'] ) ) {
				$styles[] = 'border-color:' . $border['color'];
			}
			if ( ! empty( $border['style'] ) ) {
				$styles[] = 'border-style:' . $border['style'];
			}
			if ( ! empty( $border['width'] ) ) {
				$styles[] = 'border-width:' . $border['width'];
			}
			if ( ! empty( $border['radius'] ) ) {
				if ( is_string( $border['radius'] ) ) {
					$styles[] = 'border-radius:' . $border['radius'];
				}
			}
		}

		// Spacing styles.
		if ( ! in_array( 'spacing', $skip_features, true ) && ! empty( $style['spacing'] ) ) {
			$spacing = $style['spacing'];
			if ( ! empty( $spacing['padding'] ) ) {
				$this->add_box_styles( $styles, 'padding', $spacing['padding'] );
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$this->add_box_styles( $styles, 'margin', $spacing['margin'] );
			}
			if ( ! empty( $spacing['blockGap'] ) ) {
				$gap = is_string( $spacing['blockGap'] ) ? $spacing['blockGap'] : '';
				if ( $gap ) {
					$styles[] = 'gap:' . $gap;
				}
			}
		}

		// Typography styles.
		if ( ! in_array( 'typography', $skip_features, true ) && ! empty( $style['typography'] ) ) {
			$typography = $style['typography'];
			$type_map   = array(
				'fontSize'       => 'font-size',
				'fontStyle'      => 'font-style',
				'fontWeight'     => 'font-weight',
				'letterSpacing'  => 'letter-spacing',
				'lineHeight'     => 'line-height',
				'textDecoration' => 'text-decoration',
				'textTransform'  => 'text-transform',
				'fontFamily'     => 'font-family',
				'writingMode'    => 'writing-mode',
			);
			foreach ( $type_map as $key => $prop ) {
				if ( ! empty( $typography[ $key ] ) ) {
					$styles[] = $prop . ':' . $typography[ $key ];
				}
			}
		}

		// Color styles (inline custom colors, not presets).
		if ( ! in_array( 'color', $skip_features, true ) && ! empty( $style['color'] ) ) {
			$color = $style['color'];
			if ( ! empty( $color['text'] ) ) {
				$styles[] = 'color:' . $color['text'];
			}
			if ( ! empty( $color['background'] ) ) {
				$styles[] = 'background-color:' . $color['background'];
			}
			if ( ! empty( $color['gradient'] ) ) {
				$styles[] = 'background:' . $color['gradient'];
			}
		}

		return implode( ';', $styles );
	}

	/**
	 * Add box model styles (padding/margin) for top/right/bottom/left.
	 *
	 * @param array  $styles Array of style declarations (passed by reference).
	 * @param string $prop   CSS property name (padding or margin).
	 * @param mixed  $values Box values (string or array with top/right/bottom/left).
	 */
	private function add_box_styles( array &$styles, string $prop, $values ): void {
		if ( is_string( $values ) ) {
			$styles[] = $prop . ':' . $values;
			return;
		}

		$sides = array( 'top', 'right', 'bottom', 'left' );
		foreach ( $sides as $side ) {
			if ( ! empty( $values[ $side ] ) ) {
				$styles[] = $prop . '-' . $side . ':' . $values[ $side ];
			}
		}
	}

	/**
	 * Build the class attribute string.
	 *
	 * @param array $classes CSS class names.
	 * @return string Class attribute (e.g., ' class="foo bar"') or empty string.
	 */
	protected function build_class_attr( array $classes ): string {
		return ! empty( $classes ) ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';
	}

	/**
	 * Build the style attribute string.
	 *
	 * @param string $style_string Inline style string.
	 * @return string Style attribute (e.g., ' style="..."') or empty string.
	 */
	protected function build_style_attr( string $style_string ): string {
		return ! empty( $style_string ) ? ' style="' . esc_attr( $style_string ) . '"' : '';
	}
}
