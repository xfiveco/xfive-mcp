# WordPress MCP Abilities - Documentation

## Overview

This plugin exposes WordPress content management capabilities through MCP (Model Context Protocol) tools for AI assistants. Each "ability" is a structured tool that AI assistants can use to interact with WordPress.

## Available Abilities

### Block Management

- **[Blocks - Schema](BLOCKS_SCHEMA.md)** - Discover all available WordPress blocks and their attributes
- **[Blocks - Add](BLOCKS_ADD.md)** - Create new blocks in posts
- **[Blocks - Update](BLOCK_UPDATE.md)** - Modify existing block attributes
- **[Blocks - Replace](BLOCKS_REPLACE.md)** - Completely replace a block with a new one
- **[Blocks - Remove](BLOCKS_REMOVE.md)** - Delete blocks from posts
- **[Blocks - Move](BLOCKS_MOVE.md)** - Reorder blocks within a post
- **[Blocks - Tree](BLOCKS_TREE.md)** - View the block structure of a post

### Post Management

- **[Post - By Title](POST_BY_TITLE.md)** - Find a post ID by searching for its title
- **[Post - Get Content](POST_GET_CONTENT.md)** - Retrieve post content for review, spell-checking, or grammar correction
- **[Post - Create](POST_CREATE.md)** - Create a new post, page, or custom post type
- **[Post - Update](POST_UPDATE.md)** - Update core post fields (title, status, etc.)
- **[Post - Trash](POST_TRASH.md)** - Move a post to the trash
- **[Post - Update Content](POST_UPDATE_CONTENT.md)** - Save corrected content after spell-checking or editing

## Quick Start

### 1. Discover Available Blocks

```json
{}
```

Tool: `mcp_xfive_mcp_xfive_mcp_blocks_schema`

### 2. View Post Structure

```json
{
  "post_id": 25
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_block_tree`

### 3. Add a Block

```json
{
  "post_id": 25,
  "block": "core/paragraph",
  "attributes": {
    "content": "Hello, World!"
  }
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_block_add`

### 4. Check Spelling & Fix Content

**Get content:**

```json
{
  "post_id": 25
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_post_get_content`

**Save corrections:**

```json
{
  "post_id": 25,
  "content": "<!-- wp:paragraph -->\n<p>Corrected content.</p>\n<!-- /wp:paragraph -->"
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_post_update_content`

## Common Workflows

### Workflow 1: Create a New Post Layout

#### Step 1: Add a heading

```json
{
  "post_id": 25,
  "block": "core/heading",
  "attributes": {
    "content": "Welcome",
    "level": 2
  }
}
```

#### Step 2: Add a paragraph

```json
{
  "post_id": 25,
  "block": "core/paragraph",
  "attributes": {
    "content": "This is the introduction."
  }
}
```

#### Step 3: Add an image

```json
{
  "post_id": 25,
  "block": "core/image",
  "attributes": {
    "url": "https://example.com/image.jpg",
    "alt": "Description"
  }
}
```

### Workflow 2: Reorganize Content

#### Step 1: View current structure

```json
{
  "post_id": 25
}
```

#### Step 2: Move a block to the top

```json
{
  "post_id": 25,
  "from_index": 3,
  "to_index": 0
}
```

#### Step 3: Remove an unwanted block

```json
{
  "post_id": 25,
  "block_index": 2
}
```

### Workflow 3: Content Review & Correction

#### Step 1: Find the post

```json
{
  "post_title": "My Article"
}
```

#### Step 2: Get content for review

```json
{
  "post_id": 42
}
```

#### Step 3: AI reviews content for spelling/grammar errors

#### Step 4: Save corrections

```json
{
  "post_id": 42,
  "content": "<!-- wp:paragraph -->\n<p>Corrected content...</p>\n<!-- /wp:paragraph -->"
}
```

## Block Examples

### Simple Blocks

#### Paragraph

```json
{
  "block": "core/paragraph",
  "attributes": {
    "content": "Your text here"
  }
}
```

#### Heading

```json
{
  "block": "core/heading",
  "attributes": {
    "content": "Your heading",
    "level": 2
  }
}
```

#### Image

```json
{
  "block": "core/image",
  "attributes": {
    "url": "https://example.com/image.jpg",
    "alt": "Image description",
    "caption": "Optional caption"
  }
}
```

### Container Blocks

#### Columns Layout

```json
{
  "block": "core/columns",
  "innerBlocks": [
    {
      "block": "core/column",
      "innerBlocks": [
        {
          "block": "core/paragraph",
          "attributes": {
            "content": "Left column"
          }
        }
      ]
    },
    {
      "block": "core/column",
      "innerBlocks": [
        {
          "block": "core/paragraph",
          "attributes": {
            "content": "Right column"
          }
        }
      ]
    }
  ]
}
```

## Best Practices

### 1. Always Check Structure First

Use `Blocks - Tree` before making changes to see current block positions and structure.

### 2. Use the Right Tool

- **Blocks - Update** - For minor attribute changes (keeps existing attributes)
- **Blocks - Replace** - For complete block changes (discards existing attributes)
- **Blocks - Move** - For reordering (don't use Remove + Add)

### 3. Handle Indices Carefully

Indices shift after Remove operations. Remove from end to beginning when removing multiple blocks.

### 4. Validate Block Types

Use `Blocks - Schema` to check if a block type exists before creating it.

## Error Handling

All abilities return consistent error responses:

```json
{
  "error": "error_code",
  "message": "Human-readable error message"
}
```

Common errors:

- `post_not_found` - Post ID doesn't exist
- `block_not_found` - Block index out of range
- `invalid_block_type` - Block type not registered
- `not_found` - Resource not found

## Documentation Index

### Block Abilities

- [Blocks - Schema](BLOCKS_SCHEMA.md)
- [Blocks - Add](BLOCKS_ADD.md)
- [Blocks - Update](BLOCK_UPDATE.md)
- [Blocks - Replace](BLOCKS_REPLACE.md)
- [Blocks - Remove](BLOCKS_REMOVE.md)
- [Blocks - Move](BLOCKS_MOVE.md)
- [Blocks - Tree](BLOCKS_TREE.md)

### Post Abilities

- [Post - By Title](POST_BY_TITLE.md)
- [Post - Get Content](POST_GET_CONTENT.md)
- [Post - Create](POST_CREATE.md)
- [Post - Update](POST_UPDATE.md)
- [Post - Trash](POST_TRASH.md)
- [Post - Update Content](POST_UPDATE_CONTENT.md)

## Support

For issues or questions, please refer to the individual ability documentation files listed above.
