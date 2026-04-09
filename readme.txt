=== WP Markdown Endpoint ===
Contributors: necroob
Original Contributors: bph
Tags: markdown, REST API, content negotiation, headless, API
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.3
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

Yes. The plugin exposes several filter hooks:

* `wpmd_enabled_post_ids` — restrict .md support to specific post/page/CPT IDs (empty array = all enabled)
* `wpmd_cache_ttl` — control how long generated Markdown is cached (default: 86400 seconds / 24 hours)
* `wpmd_use_raw_content` — return `true` to bypass `the_content` filters and use raw post content
* `wpmd_pre_convert_html` — modify the HTML string just before it is converted to Markdown

= What content is included in the frontmatter? =

Title, publication date, author display name, canonical URL, tags (if any), categories (if any), and excerpt (if set).

== Screenshots ==

1. Requesting a post with the .md suffix returns Markdown with YAML frontmatter.
2. The auto-discovery link in the HTML source of a post.

== Changelog ==

= 1.1.3 =
* Add transient caching for generated Markdown output, automatically invalidated on post save.
* Add `wpmd_enabled_post_ids` filter to restrict .md support to specific post IDs.
* Add `wpmd_cache_ttl` filter to control cache duration.

= 1.1.1 =
* Reduce excessive whitespace in Mardown output due to extra wrapping form custom blocks or other components.

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
