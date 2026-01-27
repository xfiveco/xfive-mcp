# Post - Update Content

## Overview

Update post content with corrected text after spell-checking, grammar review, or content editing.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_post_update_content`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post to update |
| `content` | string | Yes | The corrected post content (in WordPress block format) |

## Response

```json
{
  "updated": true
}
```

## Example Usage

### Update Post Content

```json
{
  "post_id": 25,
  "content": "<!-- wp:paragraph -->\n<p>This is the corrected content.</p>\n<!-- /wp:paragraph -->"
}
```

**Response:**

```json
{
  "updated": true
}
```

## Primary Use Case: Saving Corrections

This tool is specifically designed for saving content after review and correction:

### Complete Correction Workflow

**Step 1: Get content**

```json
{
  "post_id": 25
}
```

**Step 2: AI reviews and corrects**

The AI identifies and fixes:
- Spelling errors
- Grammar mistakes
- Punctuation issues
- Style improvements

**Step 3: Save corrected content**

```json
{
  "post_id": 25,
  "content": "<!-- wp:paragraph -->\n<p>Corrected text with proper spelling and grammar.</p>\n<!-- /wp:paragraph -->"
}
```

## Content Format Requirements

**IMPORTANT:** The content must be in WordPress block format:

```
<!-- wp:blockname {"attributes":"values"} -->
<html>content</html>
<!-- /wp:blockname -->
```

### Correct Format Example

```
<!-- wp:heading {"level":2} -->
<h2>My Heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>My paragraph text.</p>
<!-- /wp:paragraph -->
```

### Incorrect Format (Will Break)

```
<h2>My Heading</h2>
<p>My paragraph text.</p>
```

## Error Handling

### Post Not Found

```json
{
  "error": "not_found",
  "message": "Post not found"
}
```

### Update Failed

```json
{
  "error": "update_failed",
  "message": "Failed to update post content"
}
```

## Best Practices

1. **Preserve block format** - Always maintain WordPress block comment syntax
2. **Get before update** - Use Post - Get Content first to get the current format
3. **Validate changes** - Ensure corrections don't break the block structure
4. **Test with simple changes** - Start with small edits to verify format is correct

## Common Workflows

### Spell-Check Workflow

**Step 1: Find post**

```json
{
  "post_title": "Blog Post"
}
```

**Step 2: Get content**

```json
{
  "post_id": 42
}
```

**Step 3: AI identifies errors**

Original: "Ths is a tst post with speling erors."
Corrected: "This is a test post with spelling errors."

**Step 4: Save corrections**

```json
{
  "post_id": 42,
  "content": "<!-- wp:paragraph -->\n<p>This is a test post with spelling errors.</p>\n<!-- /wp:paragraph -->"
}
```

### Grammar Review Workflow

**Step 1: Get content**

```json
{
  "post_id": 25
}
```

**Step 2: AI fixes grammar**

Original: "Me and him goes to the store."
Corrected: "He and I go to the store."

**Step 3: Save corrections**

```json
{
  "post_id": 25,
  "content": "<!-- wp:paragraph -->\n<p>He and I go to the store.</p>\n<!-- /wp:paragraph -->"
}
```

## Related Tools

- **[Post - Get Content](POST_GET_CONTENT.md)** - Retrieve content for review
- **[Post - By Title](POST_BY_TITLE.md)** - Find post by title
- **[Blocks - Tree](BLOCKS_TREE.md)** - View block structure

## See Also

- [README](README.md) - Main documentation
