# xfive-mcp
MCP server with Wordpress Abilities API

## Connection

The plugin uses Application Passwords to authenticate the llm as a wp user with admin privileges. 

To enable application passwords locally set environment type to local in your wp-config.php `define( 'WP_ENVIRONMENT_TYPE', 'local' );`

The below configuration is for Google Antigravity. Some AI tools can require "mcpServers" property to be "servers"

```
{
  "mcpServers": {
    "xfive-mcp": {
      "command": "npx",
      "args": [
        "-y",
        "@automattic/mcp-wordpress-remote"
      ],
      "env": {
        "WP_API_URL": "http://your-wp-site.com/wp-json/xfive-mcp/mcp",
        "WP_API_USERNAME": "YOUR USERNAME, e.g MCP Adapter",
        "WP_API_PASSWORD": "YOUR APPLICATIONS PASSWORD"
      },
      "disabledTools": [],
      "disabled": false
    },
  }
}
```

In local development you can bypass the authorization by defining `define( 'MCP_OPEN', true );` in your wp-config.php.

## Available tools:

**xfive-blocks-block-tree** - Displays blocks tree for a post

**xfive-blocks-block-add** - Add blocks to a post. Check block-schema for blocks structure.

**xfive-blocks-block-update** - Modify attributes and content of an existing block within a post. Use when asked for block editing. Check block-schema for blocks structure.

**xfive-blocks-block-remove** - Remove a block from a post

**xfive-blocks-block-move** - Move a block to a different position in a post

**xfive-blocks-block-replace** - Replace a block with a completely new block

**xfive-blocks-block-schema** - Retrieve the schema and configuration for a specific block.

**xfive-posts-post-by-title** - Retrieve a post ID by searching for its title.

**xfive-posts-post-get-content** - Retrieve the raw content of a post for review, spell-checking, grammar correction, or content analysis. Use only when asked for spelling or grammar check.

**xfive-posts-post-update-content** - Update post content with corrected text after spell-checking, grammar review, or content editing. Use only when asked for spelling or grammar check.

**xfive-posts-post-create** - Create a new post of any type (defaults to post).

**xfive-posts-post-update** - Update an existing post (title, content, status, etc.).

**xfive-posts-post-trash** - Move a post to the trash.

**xfive-images-image-upload** - Upload an image to the media library. Provide either image_url (remote URL) or local_path (absolute path to a local file).
