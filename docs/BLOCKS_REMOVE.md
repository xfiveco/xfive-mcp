# Blocks - Remove

## Overview

Delete blocks from WordPress posts by their index position.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_block_remove`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post |
| `block_index` | integer | Yes | Index of the block to remove (0-based) |

## Response

```json
{
  "removed": true,
  "block_name": "core/paragraph"
}
```

## Examples

### Remove First Block

```json
{
  "post_id": 25,
  "block_index": 0
}
```

### Remove Third Block

```json
{
  "post_id": 25,
  "block_index": 2
}
```

### Remove Last Block

First, check how many blocks exist using `Blocks - Tree`, then remove the last one:

```json
{
  "post_id": 25,
  "block_index": 4
}
```

## Important: Index Shifting

**⚠️ Indices shift after each removal!**

### ❌ Wrong Approach

**Don't do this:**

**Step 1:**

```json
{
  "post_id": 25,
  "block_index": 1
}
```

**Step 2:**

```json
{
  "post_id": 25,
  "block_index": 2
}
```

**Problem:** After removing block 1, all blocks shift up. What was at index 3 is now at index 2!

### ✅ Correct Approach

**Option 1: Remove from end to beginning**

**Step 1: Remove block 3**

```json
{
  "post_id": 25,
  "block_index": 3
}
```

**Step 2: Remove block 2**

```json
{
  "post_id": 25,
  "block_index": 2
}
```

**Step 3: Remove block 1**

```json
{
  "post_id": 25,
  "block_index": 1
}
```

**Option 2: Always remove the same index**

To remove blocks 1, 2, and 3, always remove index 1:

**Step 1:**

```json
{
  "post_id": 25,
  "block_index": 1
}
```

**Step 2:**

```json
{
  "post_id": 25,
  "block_index": 1
}
```

**Step 3:**

```json
{
  "post_id": 25,
  "block_index": 1
}
```

## Workflows

### Clear All Blocks

**Step 1: Get block count**

```json
{
  "post_id": 25
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_block_tree`

**Step 2: Remove blocks from end to beginning**

If there are 5 blocks (indices 0-4):

```json
{"post_id": 25, "block_index": 4}
{"post_id": 25, "block_index": 3}
{"post_id": 25, "block_index": 2}
{"post_id": 25, "block_index": 1}
{"post_id": 25, "block_index": 0}
```

### Remove and Replace

**❌ Don't do this:**

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
  "block": "core/heading",
  "attributes": {
    "content": "New Block",
    "level": 2
  }
}
```

**Problem:** New block goes to the END, not index 2!

**✅ Better approach - Use Blocks - Replace:**

```json
{
  "post_id": 25,
  "block_index": 2,
  "block": "core/heading",
  "attributes": {
    "content": "New Block",
    "level": 2
  }
}
```

Tool: `mcp_xfive_mcp_xfive_mcp_block_replace`

## Error Handling

### Block Not Found

```json
{
  "error": "block_not_found",
  "message": "Block at index 5 not found. Post has 3 blocks."
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

1. **Check structure first** - Use `Blocks - Tree` to see current blocks
2. **Remove from end** - When removing multiple blocks, start from the highest index
3. **Consider alternatives** - Use `Blocks - Replace` instead of remove + add
4. **Verify indices** - Double-check block indices before removing

## Related Tools

- **[Blocks - Tree](BLOCKS_TREE.md)** - View block structure before removing
- **[Blocks - Replace](BLOCKS_REPLACE.md)** - Replace instead of remove + add
- **[Blocks - Move](BLOCKS_MOVE.md)** - Reorder blocks
- **[Blocks - Add](BLOCKS_ADD.md)** - Add new blocks

## See Also

- [README](README.md) - Main documentation
