# Block System Documentation

## Overview

The xfive MCP plugin provides a flexible block system for handling WordPress block rendering and manipulation. All block implementations extend `BlockBase` and are registered in the `BlockRegistry`.

## Creating a New Block Handler

To add support for a new block type, create a class that extends `BlockBase`:

### Basic Structure

```php
<?php

namespace XfiveMCP\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Custom block handler (e.g., core/custom-block).
 */
class CustomBlock extends BlockBase {

	/**
	 * Get the block name.
	 *
	 * @return string Block name.
	 */
	public function get_name(): string {
		return 'core/custom-block';
	}

	/**
	 * Generate HTML for the block.
	 *
	 * @param string $content     Block content (text extracted from attributes).
	 * @param array  $attrs       Block attributes (filtered, without content/text).
	 * @param array  $inner_blocks Inner blocks (for container blocks).
	 * @return string Generated HTML.
	 */
	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		// Generate and return HTML
		return sprintf(
			'<div class="wp-block-custom">%s</div>',
			wp_kses_post( $content )
		);
	}
}
```

### Leaf Blocks (No Inner Blocks)

For simple blocks without inner blocks, implement only `generate_html()`:

```php
public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
	$align = $attrs['align'] ?? '';
	$classes = $align ? array( "has-text-align-{$align}" ) : array();
	$class_attr = ! empty( $classes ) ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';

	return sprintf(
		'<div%s>%s</div>',
		$class_attr,
		wp_kses_post( $content )
	);
}
```

### Container Blocks (With Inner Blocks)

For blocks that contain other blocks (e.g., columns, group), override `get_wrapper()`:

```php
/**
 * Get wrapper opening/closing tags for the container.
 *
 * @param array $attrs Block attributes.
 * @return array|null Wrapper array with 'opening' and 'closing' keys, or null.
 */
public function get_wrapper( array $attrs ): ?array {
	return array(
		'opening' => '<div class="wp-block-custom-container">',
		'closing' => '</div>',
	);
}

public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
	// For container blocks, return empty string
	// The wrapper handles structure; inner blocks are rendered separately
	return '';
}
```

## Registering Blocks in BlockRegistry

Blocks are registered in `BlockRegistry::initialize()` in a default class map:

```php
$default_classes = array(
	'core/paragraph'    => Paragraph::class,
	'core/heading'      => Heading::class,
	'custom/myblock'    => CustomBlock::class, // Add your block here
);
```

## Extending via Filter

For custom plugins or themes, use the `xfive_mcp_block_classes` filter to register additional block handlers:

### Basic Filter Usage

```php
add_filter( 'xfive_mcp_block_classes', function( $classes ) {
	// Add a custom block handler
	$classes['my-plugin/custom-block'] = 'MyPlugin\Blocks\CustomBlock';

	// Override an existing block
	$classes['core/paragraph'] = 'MyPlugin\Blocks\CustomParagraph';

	return $classes;
} );
```

### Complete Example: Custom Plugin

Create a custom block handler in your plugin:

```php
<?php
// my-plugin/blocks/class-testimonial-block.php

namespace MyPlugin\Blocks;

use XfiveMCP\Blocks\BlockBase;

class TestimonialBlock extends BlockBase {

	public function get_name(): string {
		return 'my-plugin/testimonial';
	}

	public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
		$author = isset( $attrs['author'] ) ? esc_html( $attrs['author'] ) : '';
		$rating = isset( $attrs['rating'] ) ? (int) $attrs['rating'] : 5;

		return sprintf(
			'<div class="testimonial"><blockquote>%s</blockquote><p class="author">%s <span class="rating">★%d</span></p></div>',
			wp_kses_post( $content ),
			$author,
			$rating
		);
	}
}
```

Register it in your plugin:

```php
<?php
// my-plugin/plugin.php or similar

add_action( 'wp_abilities_api_init', function() {
	add_filter( 'xfive_mcp_block_classes', function( $classes ) {
		$classes['my-plugin/testimonial'] = 'MyPlugin\Blocks\TestimonialBlock';
		return $classes;
	} );
} );
```

## Block Attributes

### Important Notes

- **content/text attributes**: Automatically extracted and passed as `$content` parameter; removed from `$attrs`
- **rich-text attributes**: Stored in HTML only (e.g., `caption` in `<figcaption>`); automatically removed from serialized attrs to prevent validation errors
- **Other attributes**: Passed via `$attrs` array; use `$attrs['key'] ?? $default` to safely access

### Example with Multiple Attributes

```php
public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
	$url = $attrs['url'] ?? '';
	$alt = $attrs['alt'] ?? '';
	$title = $attrs['title'] ?? '';
	$width = isset( $attrs['width'] ) ? (int) $attrs['width'] : 300;
	$height = isset( $attrs['height'] ) ? (int) $attrs['height'] : 200;

	if ( empty( $url ) ) {
		return '';
	}

	return sprintf(
		'<img src="%s" alt="%s" title="%s" width="%d" height="%d" />',
		esc_url( $url ),
		esc_attr( $alt ),
		esc_attr( $title ),
		$width,
		$height
	);
}
```

## Block Utilities

### BlockRegistry Methods

- **`resolve( $block_name )`**: Get a block class instance by name
- **`has( $block_name )`**: Check if a block is registered
- **`find_block_by_index( $blocks, $index )`**: Find a block at specific index
- **`find_blocks_by_name( $blocks, $block_name, $recursive )`**: Find all blocks matching a name, optionally recursing into inner blocks

### Example Usage

```php
$registry = BlockRegistry::get_instance();

if ( $registry->has( 'core/image' ) ) {
	$block_class = $registry->resolve( 'core/image' );
	$html = $block_class->generate_html( '', $attrs );
}

$blocks = parse_blocks( $post_content );
$images = BlockRegistry::find_blocks_by_name( $blocks, 'core/image', true );
```

## Security Considerations

- Always use `wp_kses_post()` when rendering user content
- Always use `esc_html()`, `esc_url()`, `esc_attr()` for attribute values
- Validate and sanitize attribute values before use
- Use WordPress sanitization functions (e.g., `sanitize_text_field()`, `rest_sanitize_request_arg()`)

## Backward Compatibility

The `BlocksHelper` class has been removed. New code should use `BlockRegistry` directly.

```php
// Old way (no longer works)
use XfiveMCP\Helpers\BlocksHelper;
$html = BlocksHelper::generate_block_html( 'core/paragraph', 'Hello', array() );

// New way (preferred)
use XfiveMCP\Blocks\BlockRegistry;
$registry = BlockRegistry::get_instance();
$paragraph = $registry->resolve( 'core/paragraph' );
$html = $paragraph->generate_html( 'Hello', array() );
```

## Common Patterns

### Conditional HTML Output

```php
public function generate_html( string $content, array $attrs, array $inner_blocks = array() ): string {
	if ( empty( $content ) ) {
		return '';
	}

	return sprintf( '<p>%s</p>', wp_kses_post( $content ) );
}
```

### CSS Classes from Attributes

```php
$classes = array( 'wp-block-custom' );

if ( isset( $attrs['align'] ) && in_array( $attrs['align'], array( 'left', 'center', 'right' ), true ) ) {
	$classes[] = "has-text-align-{$attrs['align']}";
}

if ( isset( $attrs['customClass'] ) ) {
	$classes[] = $attrs['customClass'];
}

$class_attr = ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
```

### Processing Inner Blocks

Inner blocks are already rendered by the registry; access their rendered HTML through the `$inner_blocks` array structure if needed, or rely on the wrapper pattern for container blocks.

## Troubleshooting

### Block Not Appearing

1. Verify block name matches WordPress registration exactly (e.g., `core/image`, not `core-image`)
2. Check `BlockRegistry::has( $block_name )` returns true
3. Ensure `get_name()` returns the correct block name
4. Verify the block class is properly namespaced and autoloadable

### HTML Not Rendering Correctly

1. Check `generate_html()` receives correct `$content` and `$attrs`
2. Use `wp_kses_post()` for user content, `esc_*()` for attributes
3. For container blocks, ensure `get_wrapper()` is implemented and returns correct array structure
4. Debug with `error_log( wp_json_encode( $attrs ) )` to inspect attribute values

### REST Validation Errors

Rich-text attributes (with `type: "rich-text"`) are automatically removed from serialized attrs to prevent validation issues. If you see validation errors, ensure your `generate_html()` method is called before the block is serialized.
