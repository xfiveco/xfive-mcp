<?php

namespace XfiveMCP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper class for WordPress block operations.
 *
 * Provides static methods for normalizing, generating HTML, and manipulating blocks.
 */
class BlocksHelper {

	/**
	 * Normalize block data into the format expected by serialize_blocks().
	 *
	 * This method supports ALL registered WordPress blocks dynamically by:
	 * 1. Validating the block exists in the registry.
	 * 2. Generating proper HTML from attributes.
	 * 3. Recursively processing inner blocks.
	 *
	 * @param array $block Block data with 'block', 'attributes', and optionally 'innerBlocks'.
	 * @return array|\WP_Error Normalized block array or error.
	 */
	public static function normalize_block( array $block ): array|\WP_Error {
		// Normalize input: support both API format (block/attributes) and WP format (blockName/attrs).
		$block_name = $block['block'] ?? $block['blockName'] ?? '';
		$attrs      = $block['attributes'] ?? $block['attrs'] ?? array();

		if ( empty( $block_name ) ) {
			return new \WP_Error( 'missing_block_name', 'Block name is required' );
		}

		$registry = \WP_Block_Type_Registry::get_instance();
		if ( ! $registry->is_registered( $block_name ) ) {
			return new \WP_Error(
				'invalid_block_type',
				sprintf( 'Block type "%s" is not registered', $block_name )
			);
		}

		$inner_blocks_data = $block['innerBlocks'] ?? array();

		// Extract content/text for HTML generation (don't store as attributes).
		// 'content' is used for most text blocks (paragraph, heading, etc.).
		// 'text' is used for button blocks.
		$content = $attrs['content'] ?? $attrs['text'] ?? '';

		// Remove content/text from attributes as they're not valid block attributes.
		// Content is stored in innerHTML, not in attrs.
		$filtered_attrs = $attrs;
		unset( $filtered_attrs['content'] );
		unset( $filtered_attrs['text'] );

		// Process inner blocks recursively.
		$inner_blocks = array();
		foreach ( $inner_blocks_data as $inner_block_data ) {
			$normalized_inner = self::normalize_block( $inner_block_data );
			if ( is_wp_error( $normalized_inner ) ) {
				return $normalized_inner;
			}
			$inner_blocks[] = $normalized_inner;
		}

		// Generate HTML content based on block type.
		$inner_html = self::generate_block_html( $block_name, $content, $filtered_attrs, $inner_blocks );

		// Build the block structure.
		$normalized_block = array(
			'blockName'    => $block_name,
			'attrs'        => $filtered_attrs,
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		// For blocks with inner blocks, we need to handle the content structure differently.
		if ( ! empty( $inner_blocks ) ) {
			// Get wrapper HTML for container blocks.
			$wrapper = self::get_block_wrapper( $block_name, $filtered_attrs );

			if ( ! empty( $wrapper ) ) {
				// Container blocks: opening tag, inner blocks (as null), closing tag.
				$inner_content = array( $wrapper['opening'] );

				foreach ( $inner_blocks as $inner_block ) {
					$inner_content[] = null; // Placeholder for the inner block.
				}

				$inner_content[] = $wrapper['closing'];

				$normalized_block['innerHTML']    = $wrapper['opening'] . $wrapper['closing'];
				$normalized_block['innerContent'] = $inner_content;
			} else {
				// Non-container blocks with inner blocks (rare case).
				$inner_content = array();
				foreach ( $inner_blocks as $inner_block ) {
					$inner_content[] = null;
				}
				$normalized_block['innerHTML']    = $inner_html;
				$normalized_block['innerContent'] = $inner_content;
			}
		} else {
			// For leaf blocks (without inner blocks), set the rendered content.
			$normalized_block['innerHTML']    = $inner_html;
			$normalized_block['innerContent'] = array( $inner_html );
		}

		return $normalized_block;
	}

	/**
	 * Generate HTML content for a block based on its type and attributes.
	 *
	 * @param string $block_name Block name (e.g., core/paragraph).
	 * @param string $content Block content (text/HTML).
	 * @param array  $attrs Block attributes.
	 * @param array  $inner_blocks Inner blocks (for container blocks).
	 * @return string Generated HTML.
	 */
	public static function generate_block_html( string $block_name, string $content, array $attrs, array $inner_blocks = array() ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Generate HTML based on block type.
		switch ( $block_name ) {
			case 'core/paragraph':
				$classes = array();
				if ( isset( $attrs['align'] ) ) {
					$classes[] = 'has-text-align-' . $attrs['align'];
				}
				$class_attr = ! empty( $classes ) ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';
				return sprintf(
					'<p%s>%s</p>',
					$class_attr,
					wp_kses_post( $content )
				);

			case 'core/heading':
				$level   = $attrs['level'] ?? 2;
				$classes = array();
				if ( isset( $attrs['textAlign'] ) ) {
					$classes[] = 'has-text-align-' . $attrs['textAlign'];
				}
				$class_attr = ! empty( $classes ) ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';
				return sprintf(
					'<h%d%s>%s</h%d>',
					$level,
					$class_attr,
					wp_kses_post( $content ),
					$level
				);

			case 'core/list':
				$wrapper = self::get_block_wrapper( $block_name, $attrs );
				return $wrapper['opening'] . ( ! empty( $content ) ? $content : '' ) . $wrapper['closing'];

			case 'core/list-item':
				return '<li>' . wp_kses_post( $content ) . '</li>';

			case 'core/quote':
				return sprintf(
					'<blockquote class="wp-block-quote"><p>%s</p></blockquote>',
					wp_kses_post( $content )
				);

			case 'core/code':
				return sprintf(
					'<pre class="wp-block-code"><code>%s</code></pre>',
					esc_html( $content )
				);

			case 'core/preformatted':
				return sprintf(
					'<pre class="wp-block-preformatted">%s</pre>',
					esc_html( $content )
				);

			case 'core/pullquote':
				return sprintf(
					'<figure class="wp-block-pullquote"><blockquote><p>%s</p></blockquote></figure>',
					wp_kses_post( $content )
				);

			case 'core/verse':
				return sprintf(
					'<pre class="wp-block-verse">%s</pre>',
					wp_kses_post( $content )
				);

			case 'core/button':
				$url  = $attrs['url'] ?? '#';
				$text = $content;
				return sprintf(
					'<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
					esc_url( $url ),
					esc_html( $text )
				);

			case 'core/image':
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

				$class_attr = ! empty( $img_classes ) ? ' class="' . esc_attr( implode( ' ', $img_classes ) ) . '"' : '';

				$img_tag = sprintf(
					'<img src="%s" alt="%s"%s/>',
					esc_url( $url ),
					esc_attr( $alt ),
					$class_attr
				);

				// Wrap in figure with optional caption.
				if ( ! empty( $caption ) ) {
					return sprintf(
						'<figure class="wp-block-image">%s<figcaption class="wp-element-caption">%s</figcaption></figure>',
						$img_tag,
						wp_kses_post( $caption )
					);
				}

				return sprintf(
					'<figure class="wp-block-image">%s</figure>',
					$img_tag
				);

			case 'core/separator':
				return '<hr class="wp-block-separator has-alpha-channel-opacity"/>';

			case 'core/spacer':
				$height = $attrs['height'] ?? '100px';
				return sprintf(
					'<div style="height:%s" aria-hidden="true" class="wp-block-spacer"></div>',
					esc_attr( $height )
				);

			case 'core/media-text':
				$wrapper = self::get_block_wrapper( $block_name, $attrs );
				return $wrapper['opening'] . ( ! empty( $content ) ? $content : '' ) . $wrapper['closing'];

			case 'core/group':
			case 'core/column':
			case 'core/columns':
			case 'core/buttons':
				// Container blocks - return empty string, content comes from inner blocks.
				return '';

			default:
				// For unknown blocks, try to use content if provided.
				if ( ! empty( $content ) ) {
					return wp_kses_post( $content );
				}
				return '';
		}
	}

	/**
	 * Get wrapper HTML for container blocks.
	 *
	 * Container blocks need opening and closing tags with inner blocks in between.
	 *
	 * @param string $block_name Block name.
	 * @param array  $attrs Block attributes.
	 * @return array|null Array with 'opening' and 'closing' keys, or null for non-container blocks.
	 */
	public static function get_block_wrapper( string $block_name, array $attrs ): ?array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		switch ( $block_name ) {
			case 'core/group':
				return array(
					'opening' => '<div class="wp-block-group">',
					'closing' => '</div>',
				);

			case 'core/column':
				return array(
					'opening' => '<div class="wp-block-column">',
					'closing' => '</div>',
				);

			case 'core/columns':
				return array(
					'opening' => '<div class="wp-block-columns">',
					'closing' => '</div>',
				);

			case 'core/buttons':
				return array(
					'opening' => '<div class="wp-block-buttons">',
					'closing' => '</div>',
				);

			case 'core/media-text':
				$media_id   = $attrs['mediaId'] ?? 0;
				$media_type = $attrs['mediaType'] ?? 'image';
				$media_url  = $attrs['mediaUrl'] ?? '';
				$media_alt  = $attrs['mediaAlt'] ?? '';

				$classes = array( 'wp-block-media-text' );
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
					$classes[] = 'is-image-fill';
				}

				$class_attr = ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';

				$media_html = '<figure class="wp-block-media-text__media">';
				if ( ! empty( $media_url ) ) {
					if ( 'image' === $media_type ) {
						$img_classes = array();
						if ( ! empty( $media_id ) ) {
							$img_classes[] = 'wp-image-' . $media_id;
						}
						$img_class_attr = ! empty( $img_classes ) ? ' class="' . esc_attr( implode( ' ', $img_classes ) ) . '"' : '';
						$media_html    .= sprintf( '<img src="%s" alt="%s"%s/>', esc_url( $media_url ), esc_attr( $media_alt ), $img_class_attr );
					} elseif ( 'video' === $media_type ) {
						$media_html .= sprintf( '<video src="%s"></video>', esc_url( $media_url ) );
					}
				}
				$media_html .= '</figure>';

				return array(
					'opening' => '<div' . $class_attr . '>' . $media_html . '<div class="wp-block-media-text__content">',
					'closing' => '</div></div>',
				);

			case 'core/list':
				$ordered = isset( $attrs['ordered'] ) && $attrs['ordered'];
				$tag     = $ordered ? 'ol' : 'ul';
				return array(
					'opening' => '<' . $tag . '>',
					'closing' => '</' . $tag . '>',
				);

			case 'core/list-item':
				return array(
					'opening' => '<li>',
					'closing' => '</li>',
				);

			default:
				return null;
		}
	}

	/**
	 * Find a block by index in a flat array of blocks.
	 *
	 * @param array $blocks Array of blocks.
	 * @param int   $index Block index (0-based).
	 * @return array|null Block data or null if not found.
	 */
	public static function find_block_by_index( array $blocks, int $index ): ?array {
		return $blocks[ $index ] ?? null;
	}

	/**
	 * Find blocks by name in an array of blocks.
	 *
	 * @param array  $blocks Array of blocks.
	 * @param string $block_name Block name to search for.
	 * @param bool   $recursive Whether to search recursively in inner blocks.
	 * @return array Array of matching blocks with their indices.
	 */
	public static function find_blocks_by_name( array $blocks, string $block_name, bool $recursive = false ): array {
		$found = array();

		foreach ( $blocks as $index => $block ) {
			if ( ( $block['blockName'] ?? '' ) === $block_name ) {
				$found[] = array(
					'index' => $index,
					'block' => $block,
				);
			}

			if ( $recursive && ! empty( $block['innerBlocks'] ) ) {
				$inner_found = self::find_blocks_by_name( $block['innerBlocks'], $block_name, true );
				foreach ( $inner_found as $inner_block ) {
					$found[] = $inner_block;
				}
			}
		}

		return $found;
	}

	/**
	 * Auto-wrap standalone button blocks in a buttons container.
	 *
	 * @param array $block Normalized block.
	 * @param array $args Original block arguments.
	 * @return array Block, potentially wrapped.
	 */
	public static function auto_wrap_button( array $block, array $args ): array {
		$block_name = $args['block'] ?? $args['blockName'] ?? '';

		if ( 'core/button' === $block_name ) {
			return array(
				'blockName'    => 'core/buttons',
				'attrs'        => array(),
				'innerBlocks'  => array( $block ),
				'innerHTML'    => '<div class="wp-block-buttons"></div>',
				'innerContent' => array(
					'<div class="wp-block-buttons">',
					null,
					'</div>',
				),
			);
		}

		return $block;
	}
}
