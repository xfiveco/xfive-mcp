# Blocks - Replace

## Overview

Completely replace a block with a new one, discarding all existing attributes and content.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_block_replace`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post |
| `block_index` | integer | Yes | Index of the block to replace (0-based) |
| `block` | string | Yes | New block type (e.g., `core/heading`) |
| `attributes` | object | No | New block attributes |
| `innerBlocks` | array | No | New nested blocks |

## Response

```json
{
  "replaced": true,
  "old_block_name": "core/paragraph",
  "new_block_name": "core/heading"
}
```

## Examples

### Replace Paragraph with Heading

```json
{
  "post_id": 25,
  "block_index": 0,
  "block": "core/heading",
  "attributes": {
    "content": "This is now a heading",
    "level": 2
  }
}
```

### Replace Heading with Quote

```json
{
  "post_id": 25,
  "block_index": 1,
  "block": "core/quote",
  "attributes": {
    "content": "This is now a quote"
  }
}
```

### Replace Simple Block with Complex Block

```json
{
  "post_id": 25,
  "block_index": 2,
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

## BlockReplace vs BlockUpdate

### BlockUpdate (Merge Attributes)

- Keeps existing attributes
- Overrides only specified attributes
- Preserves block structure
- **Use when:** Making small changes to existing block

**Example:**

```json
{
  "post_id": 25,
  "block_index": 0,
  "attributes": {
    "content": "Updated text"
  }
}
```

Result: Only `content` changes, other attributes (like `level`, `textAlign`) remain.

### BlockReplace (Complete Replacement)

- Discards ALL existing attributes
- Uses only new attributes
- Completely new block
- **Use when:** Changing block type or starting fresh

**Example:**

```json
{
  "post_id": 25,
  "block_index": 0,
  "block": "core/heading",
  "attributes": {
    "content": "New heading"
  }
}
```

Result: Completely new block, all old attributes gone.

## When to Use Replace

1. **Changing block types** - Convert paragraph to heading, heading to quote, etc.
2. **Complete content change** - When you want to start fresh
3. **Simplifying complex blocks** - Replace columns with simple paragraph
4. **Complexifying simple blocks** - Replace paragraph with columns layout

## Error Handling

### Block Not Found

```json
{
  "error": "block_not_found",
  "message": "Block at index 5 not found. Post has 3 blocks."
}
```

### Invalid Block Type

```json
{
  "error": "invalid_block_type",
  "message": "Block type \"custom/invalid\" is not registered"
}
```

### Post Not Found

```json
{
  "error": "post_not_found",
  "message": "Post not found"
}
```

## Best Practices

1. **Use Update for minor changes** - If just changing content, use BlockUpdate
2. **Use Replace for type changes** - When changing block types, use BlockReplace
3. **Check structure first** - Use BlockTree to see current blocks
4. **Validate new block type** - Use BlocksSchema to verify block exists

## Related Tools

- **[Blocks - Update](BLOCKS_UPDATE.md)** - Modify attributes (merge mode)
- **[Blocks - Tree](BLOCKS_TREE.md)** - View current structure
- **[Blocks - Schema](BLOCKS_SCHEMA.md)** - Discover available blocks
- **[Blocks - Add](BLOCKS_ADD.md)** - Add new blocks

## See Also

- [Block Management Guide](BLOCK_MANAGEMENT.md) - Complete workflows
- [README](README.md) - Main documentation
