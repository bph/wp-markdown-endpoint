=== WP Markdown Endpoint ===
Contributors: bph
Tags: markdown, REST API, content negotiation, headless, API
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exposes posts and pages as Markdown via .md URL suffix, Accept header negotiation, and auto-discovery links.

== Description ==

WP Markdown Endpoint lets any WordPress post or page be retrieved as Markdown. It is useful for headless setups, static site generators, AI ingestion pipelines, and any client that prefers Markdown over HTML.

**Three ways to request Markdown:**

* Append `.md` to any post or page URL — `https://example.com/my-post.md`
* Add `?format=md` as a query parameter
* Send `Accept: text/markdown` in the HTTP request header

**Each Markdown response includes:**

* YAML frontmatter with title, date, author, canonical URL, tags, categories, and excerpt
* The full post content converted from Gutenberg block HTML to clean Markdown
* Headings, paragraphs, lists, blockquotes, code blocks, images, links, bold, italic, and strikethrough are all supported

**Auto-discovery:**

A `<link rel="alternate" type="text/markdown">` tag is injected into the HTML `<head>` of every singular post and page, allowing clients to discover the Markdown URL without prior knowledge.

== Installation ==

1. Upload the `wp-markdown-endpoint` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. No configuration is required.

== Frequently Asked Questions ==

= Does this work with custom post types? =

Yes. Any public post type registered with `public => true` is supported automatically.

= Does this work without pretty permalinks? =

The `.md` suffix requires pretty permalinks. The `?format=md` query parameter and `Accept: text/markdown` header work with any permalink structure.

= Can I filter or modify the Markdown output? =

The plugin uses the standard `the_content` filter before conversion, so any plugin that hooks into content will affect the output. Additional filters specific to the Markdown output may be added in future versions.

= What content is included in the frontmatter? =

Title, publication date, author display name, canonical URL, tags (if any), categories (if any), and excerpt (if set).

== Screenshots ==

1. Requesting a post with the .md suffix returns Markdown with YAML frontmatter.
2. The auto-discovery link in the HTML source of a post.

== Changelog ==

= 1.1.0 =
* Add canonical `Link` header to Markdown responses, pointing back to the original HTML page.

= 1.0.1 =
* Initial public release.

= 1.0.0 =
* Development release.

== Upgrade Notice ==

= 1.1.0 =
Adds a canonical Link header to Markdown responses for better SEO and content attribution.

= 1.0.1 =
Initial release. No upgrade steps required.
