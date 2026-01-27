# BlockUpdate Ability - Documentation

## Overview

The `BlockUpdate` ability allows you to update existing blocks in a WordPress post. You can modify block attributes, change block types, or replace inner blocks.

## Features

- ✅ Update block attributes
- ✅ Change block type (e.g., paragraph to heading)
- ✅ Replace inner blocks
- ✅ Merge attributes (keeps existing, overrides with new)
- ✅ Block validation
- ✅ Uses shared BlocksHelper for consistency

## Usage

### Basic Syntax

```json
{
  "post_id": 25,
  "block_index": 0,
  "attributes": {
    "content": "Updated content"
  }
}
```

### Required Parameters

- **`post_id`** (integer) - ID of the post containing the block
- **`block_index`** (integer) - Index of the block to update (0-based)

### Optional Parameters

- **`block`** (string) - New block type (to change block type)
- **`attributes`** (object) - New/updated attributes
- **`innerBlocks`** (array) - New inner blocks (replaces existing)

## Examples

### Example 1: Update Paragraph Content

**Scenario:** Change the text of the first paragraph in a post

```json
{
  "post_id": 25,
  "block_index": 0,
  "attributes": {
    "content": "This is the updated paragraph text."
  }
}
```

**Result:** The paragraph at index 0 will have its content updated while keeping other attributes (like alignment) intact.

### Example 2: Change Paragraph to Heading

**Scenario:** Convert a paragraph into a heading

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

**Result:** The block at index 0 changes from `core/paragraph` to `core/heading`.

### Example 3: Update Heading Level

**Scenario:** Change an H3 to an H2

```json
{
  "post_id": 25,
  "block_index": 1,
  "attributes": {
    "level": 2
  }
}
```

**Result:** The heading level changes from 3 to 2, content stays the same.

### Example 4: Update Button URL

**Scenario:** Change where a button links to

```json
{
  "post_id": 25,
  "block_index": 3,
  "attributes": {
    "url": "https://newsite.com/page"
  }
}
```

**Result:** Button URL updated, text stays the same.

### Example 5: Update Image

**Scenario:** Replace an image with a different one

```json
{
  "post_id": 25,
  "block_index": 2,
  "attributes": {
    "url": "https://example.com/new-image.jpg",
    "alt": "New image description",
    "id": 456
  }
}
```

**Result:** Image URL, alt text, and attachment ID updated.

### Example 6: Update Column Inner Blocks

**Scenario:** Replace the content inside a column

```json
{
  "post_id": 25,
  "block_index": 4,
  "innerBlocks": [
    {
      "block": "core/heading",
      "attributes": {
        "content": "New Heading",
        "level": 3
      }
    },
    {
      "block": "core/paragraph",
      "attributes": {
        "content": "New paragraph content."
      }
    }
  ]
}
```

**Result:** All inner blocks in the column are replaced with the new ones.

## How Block Indices Work

Blocks are indexed starting from 0 (zero-based indexing):

```
Post Content:
├── Block 0: Heading "Welcome"
├── Block 1: Paragraph "Introduction text"
├── Block 2: Image
├── Block 3: Columns
│   ├── Column 1 (inner block)
│   └── Column 2 (inner block)
└── Block 4: Button
```

**Important:** Block indices refer to top-level blocks only. To update inner blocks (like individual columns), you need to update the parent block's `innerBlocks`.

## Finding Block Index

### Method 1: Use BlockTree Tool

```json
{
  "post_id": 25
}
```

This returns the block structure with indices.

### Method 2: Count from Top

Manually count blocks from the top of the post:
- First block = index 0
- Second block = index 1
- Third block = index 2
- etc.

## Attribute Merging

BlockUpdate **merges** attributes by default:

**Existing Block:**
```json
{
  "blockName": "core/heading",
  "attrs": {
    "content": "Old Heading",
    "level": 3,
    "textAlign": "center"
  }
}
```

**Update Request:**
```json
{
  "block_index": 0,
  "attributes": {
    "content": "New Heading"
  }
}
```

**Result:**
```json
{
  "blockName": "core/heading",
  "attrs": {
    "content": "New Heading",      // ✅ Updated
    "level": 3,                   // ✅ Kept
    "textAlign": "center"         // ✅ Kept
  }
}
```

## Changing Block Types

You can change a block from one type to another:

```json
{
  "block_index": 0,
  "block": "core/quote",
  "attributes": {
    "content": "This is now a quote instead of a paragraph"
  }
}
```

**Note:** When changing block types, make sure the attributes are compatible with the new block type.

## Error Handling

### Post Not Found

```json
{
  "error": "post_not_found",
  "message": "Post not found"
}
```

### Block Index Out of Range

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
  "message": "Block type \"core/invalid\" is not registered"
}
```

## Complete Workflow Example

### Scenario: Update a Call-to-Action Section

**Step 1: Get current blocks**
```json
{
  "tool": "block_tree",
  "post_id": 25
}
```

**Step 2: Identify the block to update**
- Heading is at index 0
- Paragraph is at index 1
- Button is at index 2

**Step 3: Update the heading**
```json
{
  "post_id": 25,
  "block_index": 0,
  "attributes": {
    "content": "Limited Time Offer!"
  }
}
```

**Step 4: Update the paragraph**
```json
{
  "post_id": 25,
  "block_index": 1,
  "attributes": {
    "content": "Get 50% off today only!"
  }
}
```

**Step 5: Update the button**
```json
{
  "post_id": 25,
  "block_index": 2,
  "attributes": {
    "text": "Claim Offer Now",
    "url": "/special-offer"
  }
}
```

## Best Practices

### 1. Always Verify Block Index

Use BlockTree to confirm the index before updating:

```json
{
  "tool": "block_tree",
  "post_id": 25
}
```

### 2. Update Only What's Needed

Don't include attributes that haven't changed - they'll be preserved automatically.

**Good:**
```json
{
  "attributes": {
    "content": "New text"
  }
}
```

**Unnecessary:**
```json
{
  "attributes": {
    "content": "New text",
    "level": 2,              // Already 2
    "textAlign": "left"      // Already left
  }
}
```

### 3. Test Block Type Changes

When changing block types, ensure attributes are compatible:

```json
// Paragraph to Heading - OK
{
  "block": "core/heading",
  "attributes": {
    "content": "Text",
    "level": 2
  }
}

// Paragraph to Image - Need different attributes
{
  "block": "core/image",
  "attributes": {
    "url": "...",
    "alt": "..."
  }
}
```

### 4. Batch Updates

If updating multiple blocks, make separate calls for each:

```javascript
// Update block 0
await blockUpdate({ post_id: 25, block_index: 0, attributes: {...} });

// Update block 1
await blockUpdate({ post_id: 25, block_index: 1, attributes: {...} });

// Update block 2
await blockUpdate({ post_id: 25, block_index: 2, attributes: {...} });
```

## Integration with Other Tools

### BlockAdd + BlockUpdate

```javascript
// Add a new block
const { added } = await blockAdd({
  post_id: 25,
  block: "core/paragraph",
  attributes: { content: "Initial text" }
});

// Later, update it (assuming it's the last block)
await blockUpdate({
  post_id: 25,
  block_index: lastBlockIndex,
  attributes: { content: "Updated text" }
});
```

### BlockTree + BlockUpdate

```javascript
// Get block structure
const tree = await blockTree({ post_id: 25 });

// Find the block you want to update
const headingIndex = tree.blocks.findIndex(b => b.blockName === 'core/heading');

// Update it
await blockUpdate({
  post_id: 25,
  block_index: headingIndex,
  attributes: { content: "New heading" }
});
```

## Limitations

1. **Top-level blocks only** - Can only update blocks at the root level, not nested inner blocks directly
2. **Full inner block replacement** - When updating `innerBlocks`, all existing inner blocks are replaced
3. **No partial inner block updates** - Can't update just one inner block within a container

## Future Enhancements

Potential improvements:

1. **Path-based updates** - Update nested blocks using a path (e.g., `blocks[3].innerBlocks[1]`)
2. **Partial inner block updates** - Update specific inner blocks without replacing all
3. **Bulk updates** - Update multiple blocks in one call
4. **Find and replace** - Update all blocks matching criteria

## Summary

✅ **Flexible** - Update any block attribute  
✅ **Safe** - Validates block types and indices  
✅ **Smart** - Merges attributes automatically  
✅ **Powerful** - Can change block types  
✅ **Consistent** - Uses shared BlocksHelper  

The BlockUpdate ability gives you full control over existing blocks in your WordPress posts! 🎨
