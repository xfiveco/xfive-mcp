# Post - By Title

## Overview

Find a WordPress post ID by searching for its title.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_post_by_title`

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `post_title` | string | Yes | The title of the post to search for |

## Response

```json
{
  "post_id": 25
}
```

## Example Usage

### Find Post by Exact Title

```json
{
  "post_title": "My Blog Post"
}
```

**Response:**

```json
{
  "post_id": 42
}
```

### Use in Workflow

**Step 1: Find the post**

```json
{
  "post_title": "Welcome to My Site"
}
```

**Step 2: Use the post_id in other tools**

```json
{
  "post_id": 42,
  "block": "core/paragraph",
  "attributes": {
    "content": "New content"
  }
}
```

## How It Works

- Searches for posts with the exact title match
- Searches across all post types (`post`, `page`, etc.)
- Searches across all post statuses (`publish`, `draft`, `private`, etc.)
- Returns the first matching post found

## Error Handling

### Post Not Found

```json
{
  "error": "not_found",
  "message": "Post not found"
}
```

## Best Practices

1. **Use exact titles** - The search looks for exact matches
2. **Check for duplicates** - If multiple posts have the same title, only the first is returned
3. **Combine with other tools** - Use the returned `post_id` with block management tools

## Common Workflows

### Edit Content by Title

**Step 1: Find post**

```json
{
  "post_title": "About Us"
}
```

**Step 2: Get content**

```json
{
  "post_id": 15
}
```

**Step 3: Update content**

```json
{
  "post_id": 15,
  "content": "Updated content..."
}
```

### Add Blocks to Named Post

**Step 1: Find post**

```json
{
  "post_title": "Services"
}
```

**Step 2: Add block**

```json
{
  "post_id": 23,
  "block": "core/heading",
  "attributes": {
    "content": "Our Services",
    "level": 2
  }
}
```

## Related Tools

- **[Post - Get Content](POST_GET_CONTENT.md)** - Retrieve post content
- **[Post - Update Content](POST_UPDATE_CONTENT.md)** - Save post content
- **[Blocks - Tree](BLOCKS_TREE.md)** - View post structure
- **[Blocks - Add](BLOCKS_ADD.md)** - Add blocks to post

## See Also

- [README](README.md) - Main documentation
