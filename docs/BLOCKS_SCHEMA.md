# BlocksSchema Tool - Documentation

## Overview

The `BlocksSchema` tool provides a comprehensive listing of all registered WordPress blocks in your installation, including their metadata, attributes, and editor support features.

## Purpose

This tool is essential for:
- **Discovering available blocks** - See what blocks are registered in your WordPress installation
- **Understanding block attributes** - Learn what attributes each block accepts
- **Block development** - Reference for creating or modifying blocks
- **Integration with BlockAdd** - Know which blocks you can create using the BlockAdd tool

## What Was Fixed

### Issues in Original Implementation

1. **❌ Debug code** - Had `error_log()` statements
2. **❌ Fake data** - Returned hardcoded fake block data
3. **❌ Commented code** - Real implementation was commented out
4. **❌ Wrong output schema** - Schema didn't match actual output
5. **❌ Not useful** - Didn't provide actionable information

### Current Implementation

1. **✅ Real data** - Returns actual registered blocks from WordPress
2. **✅ Comprehensive info** - Includes name, title, category, description, attributes, and supports
3. **✅ Proper schema** - Output schema matches actual data structure
4. **✅ Sorted output** - Blocks are alphabetically sorted for easy browsing
5. **✅ Error handling** - Gracefully handles missing registry
6. **✅ PHPCS compliant** - Follows WordPress coding standards

## Output Structure

The tool returns an object with two properties:

```json
{
  "blocks": [
    {
      "name": "core/paragraph",
      "title": "Paragraph",
      "category": "text",
      "description": "Start with the building block of all narrative.",
      "attributes": {
        "content": {
          "type": "string",
          "default": ""
        },
        "align": {
          "type": "string",
          "default": null
        }
      },
      "supports": {
        "anchor": true,
        "className": true,
        "color": {
          "gradients": true,
          "link": true
        }
      }
    }
  ],
  "count": 85
}
```

### Properties Explained

#### Block Object

- **`name`** (string) - The block identifier (e.g., `core/paragraph`, `core/heading`)
- **`title`** (string) - Human-readable block name shown in the editor
- **`category`** (string) - Block category (text, media, design, widgets, theme, embed)
- **`description`** (string) - Brief description of the block's purpose
- **`attributes`** (object) - Schema of all block attributes
  - Each attribute includes:
    - `type` - Data type (string, number, boolean, object, array)
    - `default` - Default value if any
    - `enum` - Allowed values (if applicable)
- **`supports`** (object) - Editor features the block supports
  - Examples: `anchor`, `align`, `color`, `spacing`, `typography`

#### Response Object

- **`blocks`** (array) - Array of all registered blocks
- **`count`** (integer) - Total number of blocks

## Usage Examples

### Via MCP Tool

```javascript
// Get all registered blocks
const result = await mcp_xfive_mcp_xfive_mcp_blocks_schema();

console.log(`Found ${result.count} blocks`);

// Find a specific block
const paragraphBlock = result.blocks.find(b => b.name === 'core/paragraph');
console.log('Paragraph attributes:', paragraphBlock.attributes);

// List all block names
const blockNames = result.blocks.map(b => b.name);
console.log('Available blocks:', blockNames);

// Filter by category
const textBlocks = result.blocks.filter(b => b.category === 'text');
console.log(`Text blocks: ${textBlocks.length}`);
```

### Common Use Cases

#### 1. Discover Available Blocks

```javascript
const { blocks } = await mcp_xfive_mcp_xfive_mcp_blocks_schema();

// Group by category
const byCategory = blocks.reduce((acc, block) => {
  const cat = block.category || 'uncategorized';
  if (!acc[cat]) acc[cat] = [];
  acc[cat].push(block.name);
  return acc;
}, {});

console.log(byCategory);
// {
//   text: ['core/paragraph', 'core/heading', 'core/list', ...],
//   media: ['core/image', 'core/gallery', 'core/video', ...],
//   ...
// }
```

#### 2. Check Block Attributes Before Creating

```javascript
const { blocks } = await mcp_xfive_mcp_xfive_mcp_blocks_schema();

// Find heading block
const heading = blocks.find(b => b.name === 'core/heading');

console.log('Heading attributes:', heading.attributes);
// {
//   content: { type: 'string', default: '' },
//   level: { type: 'number', default: 2 },
//   textAlign: { type: 'string', default: null },
//   ...
// }

// Now create a heading with correct attributes
await mcp_xfive_mcp_xfive_mcp_block_add({
  post_id: 1,
  block: 'core/heading',
  attributes: {
    content: 'My Heading',
    level: 2,
    textAlign: 'center'
  }
});
```

#### 3. Find Blocks with Specific Capabilities

```javascript
const { blocks } = await mcp_xfive_mcp_xfive_mcp_blocks_schema();

// Find blocks that support color
const colorBlocks = blocks.filter(b => b.supports?.color);

console.log('Blocks with color support:', colorBlocks.map(b => b.name));
```

#### 4. Validate Block Existence

```javascript
const { blocks } = await mcp_xfive_mcp_xfive_mcp_blocks_schema();

function isBlockRegistered(blockName) {
  return blocks.some(b => b.name === blockName);
}

console.log(isBlockRegistered('core/paragraph')); // true
console.log(isBlockRegistered('custom/my-block')); // depends on your installation
```

## Block Categories

WordPress organizes blocks into categories:

- **text** - Paragraph, heading, list, quote, code, etc.
- **media** - Image, gallery, audio, video, file, cover
- **design** - Buttons, columns, group, separator, spacer
- **widgets** - Archives, calendar, categories, latest posts, search
- **theme** - Theme-specific blocks (varies by theme)
- **embed** - YouTube, Twitter, Instagram, and other embeds

## Integration with BlockAdd

Use `BlocksSchema` to discover what blocks you can create with `BlockAdd`:

```javascript
// Step 1: Get available blocks
const { blocks } = await mcp_xfive_mcp_xfive_mcp_blocks_schema();

// Step 2: Find the block you want
const imageBlock = blocks.find(b => b.name === 'core/image');

// Step 3: Check its attributes
console.log(imageBlock.attributes);
// { url: {...}, alt: {...}, caption: {...}, ... }

// Step 4: Create the block with correct attributes
await mcp_xfive_mcp_xfive_mcp_block_add({
  post_id: 1,
  block: 'core/image',
  attributes: {
    url: 'https://example.com/image.jpg',
    alt: 'Description',
    caption: 'Image caption'
  }
});
```

## Performance

- **Fast** - Uses WordPress's native block registry (O(1) lookup)
- **Cached** - Block registry is cached by WordPress
- **Sorted** - Results are sorted alphabetically for easy browsing
- **Efficient** - Only includes essential metadata

## Return Value Details

### Attributes Schema

Each attribute in the `attributes` object includes:

```json
{
  "attributeName": {
    "type": "string|number|boolean|object|array",
    "default": "default value or null",
    "enum": ["value1", "value2"]  // Only if applicable
  }
}
```

### Supports Object

Common support properties:

```json
{
  "anchor": true,
  "align": true,
  "alignWide": true,
  "className": true,
  "color": {
    "background": true,
    "text": true,
    "gradients": true,
    "link": true
  },
  "spacing": {
    "margin": true,
    "padding": true
  },
  "typography": {
    "fontSize": true,
    "lineHeight": true
  }
}
```

## Error Handling

The tool gracefully handles edge cases:

```javascript
// If WP_Block_Type_Registry doesn't exist (shouldn't happen in normal WP)
{
  "blocks": [],
  "count": 0
}
```

## Custom Blocks

The tool returns **ALL** registered blocks, including:

- WordPress core blocks (`core/*`)
- Third-party plugin blocks (e.g., `woocommerce/*`, `jetpack/*`)
- Custom theme blocks
- Your own custom blocks

## Example Output (Abbreviated)

```json
{
  "blocks": [
    {
      "name": "core/archives",
      "title": "Archives",
      "category": "widgets",
      "description": "Display a monthly archive of your posts.",
      "attributes": {
        "displayAsDropdown": { "type": "boolean", "default": false },
        "showPostCounts": { "type": "boolean", "default": false }
      },
      "supports": {
        "align": true,
        "html": false
      }
    },
    {
      "name": "core/button",
      "title": "Button",
      "category": "design",
      "description": "Prompt visitors to take action with a button-style link.",
      "attributes": {
        "text": { "type": "string", "default": "" },
        "url": { "type": "string", "default": "" },
        "linkTarget": { "type": "string", "default": null }
      },
      "supports": {
        "anchor": true,
        "color": { "background": true, "text": true }
      }
    }
  ],
  "count": 85
}
```

## Best Practices

1. **Cache the results** - Block schema doesn't change often, cache it in your application
2. **Check before creating** - Always verify a block exists before trying to create it
3. **Validate attributes** - Use the schema to validate attribute types and values
4. **Reference documentation** - Use this as a reference when working with blocks

## Summary

The `BlocksSchema` tool is now a **production-ready, comprehensive block discovery tool** that:

- ✅ Returns real block data from WordPress
- ✅ Includes all metadata, attributes, and supports
- ✅ Properly formatted and sorted output
- ✅ Integrates perfectly with BlockAdd
- ✅ Follows WordPress coding standards
- ✅ Handles edge cases gracefully

Use it to discover, understand, and work with any block in your WordPress installation!
