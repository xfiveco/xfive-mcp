# Post - Trash

## Overview

Move a specific post or page to the trash. This is preferred over permanent deletion.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_post_trash`

## Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `post_id` | integer | Yes | The ID of the post to move to trash |

## Response

```json
{
  "trashed": true
}
```

## Example

### Trash a Post

```json
{
  "post_id": 123
}
```

## Related Tools

- **[Post - Create](POST_CREATE.md)** - Create a new post
- **[Post - Update](POST_UPDATE.md)** - Modify an existing post
- **[Post - By Title](POST_BY_TITLE.md)** - Find a post by its title
