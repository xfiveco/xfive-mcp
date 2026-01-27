# Blocks - Tree

## Overview

View the block structure of a WordPress post in a simplified, easy-to-read format.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_block_tree`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | No | The ID of the post (optional) |

## Response

```json
{
  "blocks": [
    {
      "id": "unique-id",
      "name": "core/heading",
      "attributes": {
        "content": "My Heading",
        "level": 2
      },
      "inner": []
    },
    {
      "id": "unique-id-2",
      "name": "core/paragraph",
      "attributes": {
        "content": "Some text"
      },
      "inner": []
    }
  ]
}
```

## Example Usage

### View Post Structure

```json
{
  "post_id": 25
}
```

**Response:**

```json
{
  "blocks": [
    {
      "id": "abc123",
      "name": "core/heading",
      "attributes": {
        "content": "Welcome",
        "level": 1
      },
      "inner": []
    },
    {
      "id": "def456",
      "name": "core/columns",
      "attributes": {},
      "inner": [
        {
          "id": "ghi789",
          "name": "core/column",
          "attributes": {},
          "inner": [
            {
              "id": "jkl012",
              "name": "core/paragraph",
              "attributes": {
                "content": "Left column text"
              },
              "inner": []
            }
          ]
        },
        {
          "id": "mno345",
          "name": "core/column",
          "attributes": {},
          "inner": [
            {
              "id": "pqr678",
              "name": "core/paragraph",
              "attributes": {
                "content": "Right column text"
              },
              "inner": []
            }
          ]
        }
      ]
    }
  ]
}
```

## Use Cases

### 1. Before Making Changes

Always check the structure before modifying blocks:

```json
{
  "post_id": 25
}
```

This shows you:
- How many blocks exist
- What type each block is
- The order of blocks (indices)
- Nested block structures

### 2. Finding Block Indices

The array index corresponds to the `block_index` parameter used in other tools:

```
blocks[0] → block_index: 0
blocks[1] → block_index: 1
blocks[2] → block_index: 2
```

### 3. Understanding Nested Structures

The `inner` array shows nested blocks (innerBlocks):

- Empty `inner: []` = leaf block (no children)
- Populated `inner: [...]` = container block (has children)

## Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier for the block |
| `name` | string | Block type (e.g., `core/paragraph`) |
| `attributes` | object | Block attributes and content |
| `inner` | array | Nested blocks (empty for leaf blocks) |

## Error Handling

### Post Not Found

```json
{
  "error": "post_not_found",
  "message": "Post not found"
}
```

## Best Practices

1. **Check before modifying** - Always view structure before using Remove, Move, or Replace
2. **Verify indices** - Use the array position to determine block_index values
3. **Understand nesting** - Check `inner` arrays to understand block relationships
4. **Plan changes** - Use tree view to plan complex modifications

## Related Tools

- **[Blocks - Add](BLOCKS_ADD.md)** - Add new blocks
- **[Blocks - Remove](BLOCKS_REMOVE.md)** - Delete blocks
- **[Blocks - Move](BLOCKS_MOVE.md)** - Reorder blocks
- **[Blocks - Update](BLOCKS_UPDATE.md)** - Modify blocks
- **[Blocks - Replace](BLOCKS_REPLACE.md)** - Replace blocks

## See Also

- [Block Management Guide](BLOCK_MANAGEMENT.md) - Complete workflows
- [README](README.md) - Main documentation
