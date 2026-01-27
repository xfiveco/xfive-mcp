# Blocks - Move

## Overview

Reorder blocks within a WordPress post by moving them from one position to another.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_block_move`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post |
| `from_index` | integer | Yes | Current position of the block (0-based) |
| `to_index` | integer | Yes | New position for the block (0-based) |

## Response

```json
{
  "moved": true,
  "block_name": "core/heading",
  "from_index": 2,
  "to_index": 0
}
```

## Examples

### Move Block to Top

```json
{
  "post_id": 25,
  "from_index": 3,
  "to_index": 0
}
```

### Move Block Down

```json
{
  "post_id": 25,
  "from_index": 1,
  "to_index": 3
}
```

### Move Block Up One Position

```json
{
  "post_id": 25,
  "from_index": 2,
  "to_index": 1
}
```

## How Indices Work

### Before Move

```
0: Heading
1: Paragraph
2: Image
3: Button
```

### After Moving Index 2 to 0

```
0: Image      ← Moved here
1: Heading    ← Shifted down
2: Paragraph  ← Shifted down
3: Button     ← Unchanged
```

### After Moving Index 0 to 3

```
0: Paragraph  ← Shifted up
1: Image      ← Shifted up
2: Button     ← Shifted up
3: Heading    ← Moved here
```

## Workflows

### Move Heading to Top

**Step 1: View structure**

```json
{
  "post_id": 25
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_block_tree`

**Step 2: Move heading to position 0**

```json
{
  "post_id": 25,
  "from_index": 3,
  "to_index": 0
}
```

### Reorganize Content

**Move intro paragraph to top:**

```json
{
  "post_id": 25,
  "from_index": 5,
  "to_index": 0
}
```

**Move call-to-action to bottom:**

First, check how many blocks exist, then move to last position:

```json
{
  "post_id": 25,
  "from_index": 2,
  "to_index": 7
}
```

## Why Use Move Instead of Remove + Add?

### ❌ Wrong Approach: Remove + Add

```json
{
  "post_id": 25,
  "block_index": 2
}
```

Then:

```json
{
  "post_id": 25,
  "block": "core/paragraph",
  "attributes": {
    "content": "Text"
  }
}
```

**Problem:** The new block goes to the END, not where you want it!

### ✅ Correct Approach: Use Move

```json
{
  "post_id": 25,
  "from_index": 2,
  "to_index": 0
}
```

**Benefits:**
- ✅ Atomic operation (no intermediate state)
- ✅ Preserves all block data
- ✅ No index shifting issues
- ✅ Simpler and more reliable

## Error Handling

### Invalid from_index

```json
{
  "error": "block_not_found",
  "message": "Block at index 5 not found. Post has 4 blocks."
}
```

### Invalid to_index

```json
{
  "error": "invalid_to_index",
  "message": "Invalid to_index 10. Must be between 0 and 3."
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

1. **Check structure first** - Use `Blocks - Tree` to see current positions
2. **Use move, not remove+add** - Move is atomic and preserves data
3. **Validate indices** - Ensure both indices are within range
4. **Update after move** - If doing multiple moves, get fresh structure between moves

## Related Tools

- **[Blocks - Tree](BLOCKS_TREE.md)** - View current block positions
- **[Blocks - Remove](BLOCKS_REMOVE.md)** - Delete blocks
- **[Blocks - Add](BLOCKS_ADD.md)** - Add new blocks
- **[Blocks - Replace](BLOCKS_REPLACE.md)** - Replace blocks

## See Also

- [README](README.md) - Main documentation
