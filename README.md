# xfive-mcp — WordPress MCP server

Exposes a focused set of WordPress content-management tools over the Model Context Protocol (MCP) so AI assistants can read and write posts, pages, ACF fields, options, menus, and media.

The canonical reference for each tool is its PHP class in `inc/Abilities/` — read the `get_description()`, `get_input_schema()`, and `get_output_schema()` methods. Each tool also returns a `hint` field in its output payload that points to likely next actions or common failure causes.

## Design principles

- **Few, sharp tools.** No overlapping primitives. Block-level mutation tools were removed in favor of one path: read full content, modify the markup string, write full content back.
- **Soft-fail with hints.** Where useful, tools return `null`/empty results plus a `hint` string instead of erroring — so the agent can chain follow-up actions without a round-trip through error handling.
- **Schema-first.** Always call `block-schema` for each Gutenberg block before composing markup. Wrong attributes are silently dropped.

## Available tools

All tools register under the `xfive-mcp` server with REST namespace `xfive-mcp/v1` and route `mcp`. Each is grouped by category.

### Blocks (read-only)

| Tool | Purpose |
|---|---|
| `block-schema` | Get the registration schema (attributes + supports) for a block. Call BEFORE writing block markup. |
| `block-tree` | Parse a post's `post_content` into a Gutenberg block tree (top-level blocks + their attrs/innerBlocks). |

### Posts

| Tool | Purpose |
|---|---|
| `post-by-title` | Look up a post ID by exact title + post_type. Returns `post_id: null` (not an error) when nothing matches. |
| `post-create` | Create a new post / page / CPT entry. Returns the new `post_id`. |
| `post-update` | Update post-level fields (title, status, optionally content). |
| `post-update-content` | Replace `post_content` with new Gutenberg markup. The primary tool for ALL block edits. |
| `post-get-content` | Read the raw `post_content` markup string. |
| `post-trash` | Move a post to the trash (denied by default in `.claude/settings.json`). |

### Media

| Tool | Purpose |
|---|---|
| `image-upload` | Upload an image to the media library from a remote URL or a local filesystem path. Returns attachment `id` + `url`. |

### Menus

| Tool | Purpose |
|---|---|
| `nav-menu-create` | Create a nav menu, optionally assign to a theme location, optionally seed items (custom links, posts, taxonomies, nested). |

### ACF

| Tool | Purpose |
|---|---|
| `acf-field-update` | Update one or more ACF fields on a post or the ACF Options page (`post_id: "option"`). |

### Options & theme mods

| Tool | Purpose |
|---|---|
| `options-update` | Bulk-update `wp_options` rows (`type: "option"`) or theme mods (`type: "theme_mod"`). |

### Widgets

| Tool | Purpose |
|---|---|
| `widgets-list` | List all sidebars and the widgets assigned to each. |
| `widget-add` | Add a widget to a sidebar. |
| `widget-update` | Update a widget's settings. |
| `widget-remove` | Remove a widget from a sidebar. |

## Editing block content — the only flow

There are no partial-block mutation tools (block-add / update / replace / move / remove were removed; index-based mutation was fragile). To edit any block content:

1. Optional: `block-tree` (inspect current structure) or `post-get-content` (raw markup string).
2. `block-schema` for every block type you're about to write (validates attribute shape).
3. `post-update-content` with the full new Gutenberg markup.

Round-trip cost is small in practice and produces predictable, debuggable diffs.

## Authentication

The MCP REST endpoint uses HTTP Basic Auth with WordPress application passwords. For local development, define `MCP_OPEN` truthy in `wp-config.php` to bypass auth and run as the first administrator user (already configured in this site).

```php
define( 'MCP_OPEN', true );
```

## Client configuration

The server is reached over HTTP via the [`@automattic/mcp-wordpress-remote`](https://www.npmjs.com/package/@automattic/mcp-wordpress-remote) proxy, which bridges stdio MCP clients to the WordPress REST endpoint.

### Claude Code (`.mcp.json`)

Drop a `.mcp.json` at the project root:

```json
{
  "mcpServers": {
    "xfive-mcp-chisel": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote"],
      "env": {
        "WP_API_URL": "http://your-site.test/wp-json/xfive-mcp/mcp"
      }
    }
  }
}
```

`WP_API_URL` points at this plugin's route (`xfive-mcp/v1` namespace → `mcp` route).

### Authentication options

- **`MCP_OPEN` mode (local dev).** When `define( 'MCP_OPEN', true )` is set in `wp-config.php`, the endpoint accepts unauthenticated requests and runs as the first admin. Omit `WP_API_USERNAME` / `WP_API_PASSWORD` — they're not needed.
- **Application passwords (staging / shared).** Generate one at *Users → Profile → Application Passwords* and add:

  ```json
  "env": {
    "WP_API_URL": "http://your-site.test/wp-json/xfive-mcp/mcp",
    "WP_API_USERNAME": "your-username",
    "WP_API_PASSWORD": "your-application-password"
  }
  ```

### Other coding tools

Other MCP-capable clients (Codex, Cursor, Windsurf, Cline, Zed, etc.) use the same proxy `command` / `args` / `env` block, but each reads it from its own config file and may wrap it in a different top-level schema. Check the tool's MCP docs for the exact filename and structure — the inner server definition is portable.

## Architecture

```php
inc/
├── Abilities/             # One class per MCP tool, all extend AbilitiesBase
│   ├── AbilitiesBase.php
│   ├── BlockSchema.php
│   ├── BlockTree.php
│   ├── PostByTitle.php
│   ├── PostCreate.php
│   ├── PostUpdate.php
│   ├── PostUpdateContent.php
│   ├── PostGetContent.php
│   ├── PostTrash.php
│   ├── ImageUpload.php
│   ├── NavMenuCreate.php
│   ├── AcfFieldUpdate.php
│   ├── OptionsUpdate.php
│   └── Widget{sList,Add,Update,Remove}.php
├── WP/
│   ├── Plugin.php         # Bootstrap (singleton)
│   ├── Abilities.php      # Registers each ability with wp_register_ability()
│   └── MCP.php            # Creates the MCP server via mcp_adapter_init
└── Trait/
    ├── Singleton.php
    └── Config.php         # Inventory of which tools live in which category
```

Tool registration is data-driven from `Trait/Config.php`: add an entry there, create the matching class in `inc/Abilities/`, and `Abilities::register_abilities()` picks it up on `wp_abilities_api_init`.
