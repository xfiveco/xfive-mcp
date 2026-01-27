# Post - Update

## Overview

Update core fields of an existing post, such as title, status, and overall content.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_post_update`

## Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `post_id` | integer | Yes | The ID of the post to update |
| `post_title` | string | No | The new title of the post |
| `post_content` | string | No | The new content of the post |
| `post_status` | string | No | The new post status (e.g., `publish`, `draft`, `private`) |

## Response

```json
{
  "updated": true
}
```

## Examples

### Change Post Title and Status

```json
{
  "post_id": 123,
  "post_title": "Updated Title",
  "post_status": "publish"
}
```

### Update Post Content

```json
{
  "post_id": 123,
  "post_content": "New content for the post."
}
```

## Related Tools

- **[Post - Create](POST_CREATE.md)** - Create a new post
- **[Post - Trash](POST_TRASH.md)** - Move a post to the trash
- **[Post - Get Content](POST_GET_CONTENT.md)** - Retrieve post content
- **[Post - Update Content](POST_UPDATE_CONTENT.md)** - Update only the content field
