# Post - Get Content

## Overview

Retrieve the raw content of a WordPress post for review, spell-checking, grammar correction, or content analysis.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_post_get_content`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_id` | integer | Yes | The ID of the post to retrieve |

## Response

```json
{
  "content": "<!-- wp:heading -->\n<h2>My Heading</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>My paragraph text.</p>\n<!-- /wp:paragraph -->"
}
```

## Example Usage

### Get Post Content

```json
{
  "post_id": 25
}
```

**Response:**

```json
{
  "content": "<!-- wp:paragraph -->\n<p>This is the post content.</p>\n<!-- /wp:paragraph -->"
}
```

## Primary Use Case: Content Review & Correction

This tool is specifically designed for reviewing and correcting post content:

### Workflow: Spell-Check and Grammar Review

**Step 1: Get content for review**

```json
{
  "post_id": 25
}
```

**Step 2: AI reviews the content**

The AI assistant analyzes the content for:
- Spelling mistakes
- Grammar errors
- Punctuation issues
- Style improvements

**Step 3: Save corrected content**

Use **Post - Update Content** to save the corrections.

## Content Format

The content is returned in WordPress block format:

```
<!-- wp:blockname -->
<html>content</html>
<!-- /wp:blockname -->
```

**Important:** When updating content, maintain this exact format!

## Error Handling

### Post Not Found

```json
{
  "error": "not_found",
  "message": "Post not found"
}
```

## Best Practices

1. **Preserve format** - Keep the WordPress block comment format intact
2. **Review carefully** - Check the entire content before making changes
3. **Use with Update** - Always pair with Post - Update Content to save changes
4. **Find by title first** - Use Post - By Title if you don't know the post_id

## Common Workflows

### Complete Content Review Workflow

**Step 1: Find the post**

```json
{
  "post_title": "My Article"
}
```

**Step 2: Get content**

```json
{
  "post_id": 42
}
```

**Step 3: Review and correct**

AI reviews the content and identifies corrections needed.

**Step 4: Save corrections**

```json
{
  "post_id": 42,
  "content": "<!-- wp:paragraph -->\n<p>Corrected content here.</p>\n<!-- /wp:paragraph -->"
}
```

## Related Tools

- **[Post - Update Content](POST_UPDATE_CONTENT.md)** - Save corrected content
- **[Post - By Title](POST_BY_TITLE.md)** - Find post by title
- **[Blocks - Tree](BLOCKS_TREE.md)** - View block structure

## See Also

- [README](README.md) - Main documentation
