<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Block registry and normalizer.
 *
 * Maps block names to their BlockBase class instances and provides
 * block normalization for the WordPress block format.
 *
 * Extensible via the 'xfive_mcp_block_classes' filter.
 */
class BlockRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var BlockRegistry|null
	 */
	private static ?BlockRegistry $instance = null;

	/**
	 * Map of block name => BlockBase instance.
	 *
	 * @var array<string, BlockBase>
	 */
	private array $blocks = array();

	/**
	 * Whether the registry has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the registry with default and filtered block classes.
	 *
	 * Called lazily on first resolve() or normalize_block() call.
	 */
	private function initialize(): void {
		if ( $this->initialized ) {
			return;
		}

		// Default block class map: block name => fully qualified class name.
		$default_classes = array(
			'core/paragraph'    => Paragraph::class,
			'core/heading'      => Heading::class,
			'core/list'         => ListBlock::class,
			'core/list-item'    => ListItem::class,
			'core/quote'        => Quote::class,
			'core/code'         => Code::class,
			'core/preformatted' => Preformatted::class,
			'core/pullquote'    => Pullquote::class,
			'core/verse'        => Verse::class,
			'core/button'       => Button::class,
			'core/buttons'      => Buttons::class,
			'core/image'        => Image::class,
			'core/separator'    => Separator::class,
			'core/spacer'       => Spacer::class,
			'core/media-text'   => MediaText::class,
			'core/group'        => Group::class,
			'core/column'       => Column::class,
			'core/columns'      => Columns::class,
			'core/table'        => Table::class,
			'core/gallery'      => Gallery::class,
			'core/cover'        => Cover::class,
		);

		/**
		 * Filter the block class map.
		 *
		 * Allows external plugins to register custom block classes or
		 * override built-in ones. Each class must extend BlockBase.
		 *
		 * @param array $classes Map of block name => class name (must extend BlockBase).
		 */
		$classes = apply_filters( 'xfive_mcp_block_classes', $default_classes );

		foreach ( $classes as $block_name => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$block_instance = new $class_name();
				if ( $block_instance instanceof BlockBase ) {
					$this->blocks[ $block_name ] = $block_instance;
				}
			}
		}

		$this->initialized = true;
	}

	/**
	 * Resolve a block name to its BlockBase instance.
	 *
	 * @param string $block_name Block name (e.g., 'core/paragraph').
	 * @return BlockBase|null Block instance or null if not registered.
	 */
	public function resolve( string $block_name ): ?BlockBase {
		$this->initialize();

		return $this->blocks[ $block_name ] ?? null;
	}

	/**
	 * Check if a block class is registered.
	 *
	 * @param string $block_name Block name.
	 * @return bool Whether the block is registered.
	 */
	public function has( string $block_name ): bool {
		$this->initialize();

		return isset( $this->blocks[ $block_name ] );
	}

	/**
	 * Find a block by index in a flat array of blocks.
	 *
	 * @param array $blocks Array of blocks.
	 * @param int   $index  Block index (0-based).
	 * @return array|null Block data or null if not found.
	 */
	public static function find_block_by_index( array $blocks, int $index ): ?array {
		return $blocks[ $index ] ?? null;
	}

	/**
	 * Find blocks by name in an array of blocks.
	 *
	 * @param array  $blocks     Array of blocks.
	 * @param string $block_name Block name to search for.
	 * @param bool   $recursive  Whether to search recursively in inner blocks.
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
	 * Normalize block data into the format expected by serialize_blocks().
	 *
	 * Validates the block against the WordPress block registry, delegates
	 * HTML generation to the appropriate block class, and recursively
	 * processes inner blocks.
	 *
	 * @param array  $block       Block data with 'block', 'attributes', and optionally 'innerBlocks'.
	 * @param string $parent_name Parent block name, used to suppress auto-wrap for nested buttons.
	 * @return array|\WP_Error Normalized block array or error.
	 */
	public function normalize_block( array $block, string $parent_name = '' ): array|\WP_Error {
		// Normalize input: support both API format (block/attributes) and WP format (blockName/attrs).
		$block_name = $block['block'] ?? $block['blockName'] ?? '';
		$attrs      = $block['attributes'] ?? $block['attrs'] ?? array();

		if ( empty( $block_name ) ) {
			return new \WP_Error( 'missing_block_name', 'Block name is required' );
		}

		$wp_registry = \WP_Block_Type_Registry::get_instance();
		if ( ! $wp_registry->is_registered( $block_name ) ) {
			return new \WP_Error(
				'invalid_block_type',
				sprintf( 'Block type "%s" is not registered', $block_name )
			);
		}

		$inner_blocks_data = $block['innerBlocks'] ?? array();

		// Extract content/text/value for HTML generation (don't store as attributes).
		$content = $attrs['content'] ?? $attrs['text'] ?? $attrs['value'] ?? '';

		// Remove content/text/value from attributes as they're not valid block attributes.
		$filtered_attrs = $attrs;
		unset( $filtered_attrs['content'] );
		unset( $filtered_attrs['text'] );
		unset( $filtered_attrs['value'] );

		// Process inner blocks recursively.
		$inner_blocks = array();
		foreach ( $inner_blocks_data as $inner_block_data ) {
			$normalized_inner = $this->normalize_block( $inner_block_data, $block_name );
			if ( is_wp_error( $normalized_inner ) ) {
				return $normalized_inner;
			}
			$inner_blocks[] = $normalized_inner;
		}

		// Resolve the block class from our registry.
		$block_class = $this->resolve( $block_name );

		// Generate HTML — delegate to block class or fallback.
		if ( $block_class ) {
			$inner_html = $block_class->generate_html( $content, $filtered_attrs, $inner_blocks );
		} else { // phpcs:ignore
			// Fallback for blocks without a registered class.
			if ( ! empty( $content ) ) {
				$inner_html = wp_kses_post( $content );
			} else {
				$inner_html = '';
			}
		}

		// Preserve attrs for HTML generation (get_wrapper needs rich-text attrs like citation).
		$html_attrs = $filtered_attrs;

		// Remove rich-text attributes (e.g. caption, citation) from serialized attrs.
		// WordPress stores these in the HTML (e.g. <figcaption>), not in the block comment attrs.
		// Keeping them in attrs triggers rest_validate_value_from_schema notices because
		// "rich-text" is not a valid REST schema type.
		$block_type = $wp_registry->get_registered( $block_name );
		if ( $block_type && isset( $block_type->attributes ) && is_array( $block_type->attributes ) ) {
			foreach ( $block_type->attributes as $attr_name => $attr_schema ) {
				if ( isset( $attr_schema['type'] ) && 'rich-text' === $attr_schema['type'] ) {
					unset( $filtered_attrs[ $attr_name ] );
				}
			}
		}

		// Build the block structure.
		$normalized_block = array(
			'blockName'    => $block_name,
			'attrs'        => $filtered_attrs,
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		// For blocks with inner blocks, handle the content structure differently.
		if ( ! empty( $inner_blocks ) ) {
			// Get wrapper HTML for container blocks.
			$wrapper = $block_class ? $block_class->get_wrapper( $html_attrs ) : null;

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

		// Auto-wrap standalone button blocks in a buttons container (skip if already inside core/buttons).
		if ( $block_class instanceof Button && 'core/buttons' !== $parent_name ) {
			$normalized_block = $block_class->auto_wrap( $normalized_block );
		}

		return $normalized_block;
	}
}
