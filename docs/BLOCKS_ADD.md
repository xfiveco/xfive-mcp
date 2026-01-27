# Blocks - Add

## Overview

Create new blocks and add them to WordPress posts. Supports all registered WordPress blocks including simple blocks (paragraphs, headings) and complex nested structures (columns, groups).

## Tool Name

`mcp_xfive_mcp_xfive_mcp_block_add`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post to add the block to |
| `block` | string | Yes | Block name (e.g., `core/paragraph`, `core/heading`) |
| `attributes` | object | No | Block attributes (content, styling, etc.) |
| `innerBlocks` | array | No | Nested blocks for container blocks |

## Response

```json
{
  "added": true,
  "block_name": "core/paragraph"
}
```

## Simple Block Examples

### Add a Paragraph

```json
{
  "post_id": 25,
  "block": "core/paragraph",
  "attributes": {
    "content": "This is a paragraph of text."
  }
}
```

### Add a Heading

```json
{
  "post_id": 25,
  "block": "core/heading",
  "attributes": {
    "content": "My Heading",
    "level": 2
  }
}
```

**Heading Levels:**
- `level: 1` - H1 (main title)
- `level: 2` - H2 (section heading)
- `level: 3` - H3 (subsection)
- `level: 4` - H4 (minor heading)
- `level: 5` - H5
- `level: 6` - H6

### Add an Image

```json
{
  "post_id": 25,
  "block": "core/image",
  "attributes": {
    "url": "https://example.com/image.jpg",
    "alt": "Description of the image",
    "caption": "Optional image caption"
  }
}
```

**With WordPress Media Library:**
```json
{
  "post_id": 25,
  "block": "core/image",
  "attributes": {
    "id": 123,
    "url": "https://yoursite.com/wp-content/uploads/2024/01/image.jpg",
    "alt": "Description"
  }
}
```

### Add a Button

```json
{
  "post_id": 25,
  "block": "core/button",
  "attributes": {
    "text": "Click Me",
    "url": "https://example.com"
  }
}
```

**Note:** Buttons are automatically wrapped in a `core/buttons` container.

### Add a Quote

```json
{
  "post_id": 25,
  "block": "core/quote",
  "attributes": {
    "content": "This is a quote."
  }
}
```

### Add a Separator

```json
{
  "post_id": 25,
  "block": "core/separator"
}
```

### Add Media & Text

```json
{
  "post_id": 25,
  "block": "core/media-text",
  "attributes": {
    "mediaId": 0,
    "mediaType": "image",
    "mediaUrl": "https://example.com/image.jpg",
    "mediaAlt": "Description",
    "isStackedOnMobile": true
  },
  "innerBlocks": [
    {
      "block": "core/heading",
      "attributes": {
        "content": "Welcome to our beautiful world",
        "level": 2
      }
    },
    {
      "block": "core/paragraph",
      "attributes": {
        "content": "This is a media and text block showing how seamlessly content can be integrated into your WordPress site."
      }
    }
  ]
}
```

## Container Block Examples

### Add a Two-Column Layout

```json
{
  "post_id": 25,
  "block": "core/columns",
  "innerBlocks": [
    {
      "block": "core/column",
      "innerBlocks": [
        {
          "block": "core/heading",
          "attributes": {
            "content": "Left Column",
            "level": 3
          }
        },
        {
          "block": "core/paragraph",
          "attributes": {
            "content": "Content for the left column."
          }
        }
      ]
    },
    {
      "block": "core/column",
      "innerBlocks": [
        {
          "block": "core/heading",
          "attributes": {
            "content": "Right Column",
            "level": 3
          }
        },
        {
          "block": "core/paragraph",
          "attributes": {
            "content": "Content for the right column."
          }
        }
      ]
    }
  ]
}
```

### Add a Group Block

```json
{
  "post_id": 25,
  "block": "core/group",
  "innerBlocks": [
    {
      "block": "core/heading",
      "attributes": {
        "content": "Section Title",
        "level": 2
      }
    },
    {
      "block": "core/paragraph",
      "attributes": {
        "content": "Section content goes here."
      }
    }
  ]
}
```

### Add Multiple Buttons

```json
{
  "post_id": 25,
  "block": "core/buttons",
  "innerBlocks": [
    {
      "block": "core/button",
      "attributes": {
        "text": "Primary Action",
        "url": "/primary"
      }
    },
    {
      "block": "core/button",
      "attributes": {
        "text": "Secondary Action",
        "url": "/secondary"
      }
    }
  ]
}
```

## Common Workflows

### Create a Blog Post Structure

**Step 1: Add title**
```json
{
  "post_id": 25,
  "block": "core/heading",
  "attributes": {
    "content": "Blog Post Title",
    "level": 1
  }
}
```

**Step 2: Add intro paragraph**
```json
{
  "post_id": 25,
  "block": "core/paragraph",
  "attributes": {
    "content": "Introduction paragraph..."
  }
}
```

**Step 3: Add featured image**
```json
{
  "post_id": 25,
  "block": "core/image",
  "attributes": {
    "url": "https://example.com/featured.jpg",
    "alt": "Featured image"
  }
}
```

**Step 4: Add main content**
```json
{
  "post_id": 25,
  "block": "core/paragraph",
  "attributes": {
    "content": "Main content..."
  }
}
```

### Create a Landing Page Section

```json
{
  "post_id": 25,
  "block": "core/group",
  "innerBlocks": [
    {
      "block": "core/heading",
      "attributes": {
        "content": "Features",
        "level": 2
      }
    },
    {
      "block": "core/columns",
      "innerBlocks": [
        {
          "block": "core/column",
          "innerBlocks": [
            {
              "block": "core/heading",
              "attributes": {
                "content": "Feature 1",
                "level": 3
              }
            },
            {
              "block": "core/paragraph",
              "attributes": {
                "content": "Description..."
              }
            }
          ]
        },
        {
          "block": "core/column",
          "innerBlocks": [
            {
              "block": "core/heading",
              "attributes": {
                "content": "Feature 2",
                "level": 3
              }
            },
            {
              "block": "core/paragraph",
              "attributes": {
                "content": "Description..."
              }
            }
          ]
        }
      ]
    }
  ]
}
```

## Discovering Available Blocks

Use the **Blocks - Schema** tool to see all available blocks and their attributes. This helps you know what blocks are available and what attributes they accept.

## Common Block Types

### Text Blocks
- `core/paragraph` - Basic text
- `core/heading` - Headings (H1-H6)
- `core/list` - Ordered/unordered lists
- `core/quote` - Blockquote
- `core/code` - Code block
- `core/preformatted` - Preformatted text

### Media Blocks
- `core/image` - Images
- `core/gallery` - Image gallery
- `core/video` - Video
- `core/audio` - Audio

### Design Blocks
- `core/button` - Button (auto-wrapped in buttons)
- `core/buttons` - Button container
- `core/separator` - Horizontal rule
- `core/spacer` - Vertical spacing

### Layout Blocks
- `core/columns` - Multi-column layout
- `core/column` - Single column (used inside columns)
- `core/group` - Group container

## Error Handling

### Post Not Found
```json
{
  "error": "post_not_found",
  "message": "Post not found"
}
```

### Invalid Block Type
```json
{
  "error": "invalid_block_type",
  "message": "Block type \"custom/invalid\" is not registered"
}
```

## Best Practices

1. **Check block exists** - Use `blocksSchema` to verify block types before adding
2. **Provide alt text** - Always include `alt` attribute for images
3. **Use proper heading levels** - Follow semantic heading hierarchy (H1 → H2 → H3)
4. **Container blocks** - Use `innerBlocks` for nested structures
5. **Validate attributes** - Check block schema for required/optional attributes

## Related Tools

- **[Blocks - Schema](BLOCKS_SCHEMA.md)** - Discover available blocks and their attributes
- **[Blocks - Tree](BLOCKS_TREE.md)** - View current block structure
- **[Blocks - Update](BLOCKS_UPDATE.md)** - Modify existing blocks
- **[Blocks - Remove](BLOCKS_REMOVE.md)** - Delete blocks

## See Also

- [Block Management Guide](BLOCK_MANAGEMENT.md) - Complete block management workflows
- [README](README.md) - Main documentation
