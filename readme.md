# WP Markdown Endpoint

A WordPress plugin that exposes posts and pages as Markdown via `.md` URL suffix, `Accept` header negotiation, and auto-discovery links.

## Features

- **`.md` URL suffix** — append `.md` to any post or page URL to receive its content as Markdown (e.g. `https://example.com/my-post.md`)
- **`Accept` header negotiation** — send `Accept: text/markdown` to get a Markdown response without changing the URL
- **`?format=md` query parameter** — an alternative way to request Markdown output
- **YAML frontmatter** — every Markdown response includes structured metadata (title, date, author, URL, tags, categories, excerpt)
- **Canonical `Link` header** — Markdown responses include a `Link: <…>; rel="canonical"` HTTP header pointing back to the original HTML page
- **Auto-discovery link** — a `<link rel="alternate" type="text/markdown">` tag is injected into the HTML `<head>` of every singular post/page so clients can discover the Markdown URL automatically
- **HTML-to-Markdown conversion** — converts Gutenberg block output (headings, paragraphs, lists, blockquotes, code blocks, images, links, bold, italic, strikethrough) to clean Markdown

## Requirements

- WordPress 6.0 or later (uses `str_ends_with()`)
- PHP 8.0 or later

## Installation

1. Upload the `wp-markdown-endpoint` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. No configuration required.

## Usage

Given a post at `https://example.com/hello-world/`, you can retrieve its Markdown content in three ways:

```
# URL suffix
GET https://example.com/hello-world.md

# Query parameter
GET https://example.com/hello-world/?format=md

# Accept header
GET https://example.com/hello-world/
Accept: text/markdown
```

### Example response

```markdown
---
title: Hello World
date: 2026-02-23
author: Jane Doe
url: https://example.com/hello-world/
tags: ["news", "updates"]
categories: ["General"]
---

## Introduction

Welcome to my site! This content is served as **Markdown**.
```

## File Structure

```
wp-markdown-endpoint/
├── wp-markdown-endpoint.php   # Plugin bootstrap, defines constants, loads classes
├── includes/
│   ├── class-rewrite.php      # Intercepts .md URL suffix, sets format query var
│   ├── class-output.php       # Serves Markdown responses and injects discovery link
│   └── class-converter.php    # Converts HTML (Gutenberg output) to Markdown
├── readme.md                  # This file
└── readme.txt                 # WordPress.org-compatible readme
```

## License

GPL-2.0+. See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

Birgit Pauli-Haack
