# Post - Create

## Overview

Create a new post, page, or custom post type. By default, it creates a post in 'draft' status.

## Tool Name

`mcp_xfive_mcp_xfive_mcp_post_create`

## Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `post_title` | string | Yes | The title of the post |
| `post_content` | string | No | The initial content of the post (HTML or block markup) |
| `post_type` | string | No | The post type (e.g., `post`, `page`). Defaults to `post` |
| `post_status` | string | No | The post status (e.g., `publish`, `draft`, `private`). Defaults to `draft` |

## Response

```json
{
  "post_id": 123
}
```

## Examples

### Create a Draft Post

```json
{
  "post_title": "My New Blog Post",
  "post_content": "This is the content of my post."
}
```

### Create a Published Page

```json
{
  "post_title": "About Us",
  "post_content": "We are a great company!",
  "post_type": "page",
  "post_status": "publish"
}
```

### Create a Post with Block Markup

```json
{
  "post_title": "Post with Blocks",
  "post_content": "<!-- wp:paragraph -->\n<p>Hello world!</p>\n<!-- /wp:paragraph -->",
  "post_status": "publish"
}
```

## Related Tools

- **[Post - Update](POST_UPDATE.md)** - Modify an existing post
- **[Post - Trash](POST_TRASH.md)** - Move a post to the trash
- **[Post - Get Content](POST_GET_CONTENT.md)** - Retrieve post content
- **[Post - By Title](POST_BY_TITLE.md)** - Find a post by its title
