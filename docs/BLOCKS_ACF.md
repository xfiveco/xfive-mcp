# ACF Block Fields Support

## Overview

The `BlockAdd`, `BlockUpdate`, and `BlockReplace` abilities support an optional `acf_fields` parameter that allows you to populate or update Advanced Custom Fields (ACF) field values attached to an ACF-registered Gutenberg block.

## Prerequisites

- The [Advanced Custom Fields](https://www.advancedcustomfields.com/) plugin (free or PRO) must be installed and active.
- The target block must be registered via `acf_register_block_type()`.
- You must know the ACF field names assigned to the block's field group.

## How It Works

ACF stores block field values in the database using a synthetic post ID of the form `block_{block_id}`, where `block_id` is a unique identifier stored in the block's `id` attribute (e.g. `block_682a1c3f4b2e8`).

When you provide `acf_fields`:

1. A unique block `id` is generated automatically (if the block does not already have one) and saved as a block attribute.
2. The block is written to the post content.
3. `update_field()` is called for each key/value pair in `acf_fields`, targeting the block's `id`.

On subsequent **updates**, the existing `id` is read from the block attrs and reused so that previously saved field values are preserved and overwritten correctly.

## Parameter

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `acf_fields` | object | No | Key/value map of ACF field names to their values |

## Error Codes

| Code | Description |
|------|-------------|
| `acf_not_active` | ACF plugin is not installed or active |
| `acf_update_failed` | `update_field()` returned `false` for a specific field |

---

## Examples by Field Type

### Text / Textarea / Number / Email / URL / Password / Range

Pass the raw scalar value.

```json
{
  "post_id": 42,
  "block_index": 0,
  "acf_fields": {
    "heading": "Welcome to our site",
    "intro_text": "This is a longer description.",
    "rating": 5,
    "website": "https://example.com"
  }
}
```

---

### True / False (Checkbox toggle)

Pass a boolean.

```json
{
  "acf_fields": {
    "is_featured": true,
    "show_button": false
  }
}
```

---

### Select / Radio Button / Button Group

Pass the choice key (string) as defined in the field settings.

```json
{
  "acf_fields": {
    "layout_style": "wide",
    "color_scheme": "dark"
  }
}
```

For **multi-select** (Select with `multiple` enabled), pass an array of keys:

```json
{
  "acf_fields": {
    "tags": ["news", "featured", "homepage"]
  }
}
```

---

### Checkbox

Pass an array of selected choice keys.

```json
{
  "acf_fields": {
    "features": ["wifi", "parking", "pool"]
  }
}
```

---

### Image / File

Pass the WordPress attachment ID (integer).

```json
{
  "acf_fields": {
    "hero_image": 123,
    "brochure_pdf": 456
  }
}
```

---

### Gallery

Pass an array of attachment IDs.

```json
{
  "acf_fields": {
    "photo_gallery": [101, 102, 103, 104]
  }
}
```

---

### Link

Pass an object with `url`, `title`, and `target` keys.

```json
{
  "acf_fields": {
    "cta_link": {
      "url": "https://example.com/contact",
      "title": "Contact Us",
      "target": "_blank"
    }
  }
}
```

---

### Post Object / Relationship

Pass a single post ID (integer) for Post Object, or an array of post IDs for Relationship.

```json
{
  "acf_fields": {
    "related_post": 99,
    "related_articles": [10, 20, 30]
  }
}
```

---

### Taxonomy

Pass a single term ID (integer) for single-select, or an array for multi-select.

```json
{
  "acf_fields": {
    "primary_category": 7,
    "tags": [7, 12, 15]
  }
}
```

---

### User

Pass a single user ID (integer) for single-select, or an array for multi-select.

```json
{
  "acf_fields": {
    "author": 3,
    "team_members": [3, 8, 14]
  }
}
```

---

### Date Picker / Date Time Picker / Time Picker

Pass a string in the format configured in the field settings (default: `Y-m-d` for date, `Y-m-d H:i:s` for date-time, `H:i:s` for time).

```json
{
  "acf_fields": {
    "event_date": "2025-06-15",
    "event_start_time": "09:00:00",
    "published_at": "2025-06-15 09:00:00"
  }
}
```

---

### Color Picker

Pass a hex color string.

```json
{
  "acf_fields": {
    "background_color": "#ff6600",
    "text_color": "#ffffff"
  }
}
```

---

### Google Map

Pass an object with `address`, `lat`, and `lng` keys.

```json
{
  "acf_fields": {
    "office_location": {
      "address": "1 Infinite Loop, Cupertino, CA 95014",
      "lat": 37.3318,
      "lng": -122.0312
    }
  }
}
```

---

### oEmbed

Pass the raw URL to embed.

```json
{
  "acf_fields": {
    "promo_video": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
  }
}
```

---

### Group

Pass a nested object matching the group's sub-field names.

```json
{
  "acf_fields": {
    "hero_content": {
      "title": "Big Headline",
      "subtitle": "Supporting copy",
      "image": 77
    }
  }
}
```

---

### Repeater

Pass an array of row objects, where each object contains the repeater's sub-field names.

```json
{
  "acf_fields": {
    "team_members": [
      { "name": "Alice", "role": "Lead Developer", "photo": 201 },
      { "name": "Bob",   "role": "Designer",        "photo": 202 }
    ]
  }
}
```

---

### Flexible Content

Pass an array of layout objects. Each object must have an `acf_fc_layout` key matching the layout name, plus the layout's sub-field values.

```json
{
  "acf_fields": {
    "page_sections": [
      {
        "acf_fc_layout": "hero",
        "heading": "Welcome",
        "image": 50
      },
      {
        "acf_fc_layout": "text_block",
        "content": "This is a paragraph of body copy."
      },
      {
        "acf_fc_layout": "cta_banner",
        "button_label": "Learn More",
        "button_url": "https://example.com"
      }
    ]
  }
}
```

---

## Complete Workflow Example

### Add an ACF hero block and populate its fields

**Step 1 – Find or create the post**

```json
{
  "post_title": "My Landing Page"
}
```

Tool: `xfive-posts/post-by-title`

**Step 2 – Add the ACF block with fields**

```json
{
  "post_id": 42,
  "block": "acf/hero",
  "acf_fields": {
    "heading": "Transform Your Business",
    "subheading": "We help companies grow faster.",
    "background_image": 123,
    "cta_link": {
      "url": "/contact",
      "title": "Get Started"
    },
    "show_overlay": true
  }
}
```

Tool: `xfive-blocks/block-add`

Response includes `block_id` confirming the field association:

```json
{
  "added": true,
  "block_name": "acf/hero",
  "block_id": "block_682a1c3f4b2e8"
}
```

**Step 3 – Update just the heading later**

```json
{
  "post_id": 42,
  "block_index": 0,
  "acf_fields": {
    "heading": "Accelerate Your Growth"
  }
}
```

Tool: `xfive-blocks/block-update`

---

## Notes

- `acf_fields` is **optional**. If omitted, the ability behaves exactly as before — no ACF operations are performed.
- You do **not** need to pass `acf_fields` for standard WordPress core blocks; it is only meaningful for ACF-registered blocks.
- Field values are saved using ACF's own `update_field()` function, so all ACF hooks, formatting, and validation rules apply normally.
- For **nested fields** inside repeaters or flexible content, pass the full nested structure in a single call rather than using ACF's `add_row()` / `update_row()` API — `update_field()` with a complete array value handles this correctly.
