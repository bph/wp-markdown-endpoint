<?php
/**
 * Handles Markdown output and auto-discovery.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPMD_Output {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'add_discovery_link' ), 3 );
		add_action( 'template_redirect', array( $this, 'serve_markdown' ), 1 );
	}

	/**
	 * Add Markdown auto-discovery link to HTML head.
	 */
	public function add_discovery_link() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		// Check if this is a public post type
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return;
		}

		$md_url = $this->get_markdown_url( $post );
		if ( $md_url ) {
			printf(
				'<link rel="alternate" type="text/markdown" href="%s" />' . "\n",
				esc_url( $md_url )
			);
		}
	}

	/**
	 * Serve Markdown response if requested.
	 */
	public function serve_markdown() {
		// Check for format=md query var, $_GET, or Accept header
		$format       = get_query_var( 'format' );
		if ( empty( $format ) && isset( $_GET['format'] ) ) {
			$format = $_GET['format'];
		}
		$accept       = isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : '';
		$wants_md     = ( 'md' === $format ) || ( false !== strpos( $accept, 'text/markdown' ) );

		if ( ! $wants_md ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		// Check if this is a public post type
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return;
		}

		$markdown = $this->generate_markdown( $post );

		// Set headers
		status_header( 200 );
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );

		echo $markdown;
		exit;
	}

	/**
	 * Generate Markdown content with frontmatter.
	 *
	 * @param WP_Post $post The post object.
	 * @return string Complete Markdown with frontmatter.
	 */
	private function generate_markdown( $post ) {
		$converter = new WPMD_Converter();

		// Build frontmatter
		$frontmatter = $this->build_frontmatter( $post );

		// Convert content to Markdown
		$content = apply_filters( 'the_content', $post->post_content );
		$body    = $converter->convert( $content );

		return $frontmatter . "\n" . $body;
	}

	/**
	 * Build YAML frontmatter for the post.
	 *
	 * @param WP_Post $post The post object.
	 * @return string YAML frontmatter.
	 */
	private function build_frontmatter( $post ) {
		$meta = array(
			'title'  => $post->post_title,
			'date'   => get_the_date( 'Y-m-d', $post ),
			'author' => get_the_author_meta( 'display_name', $post->post_author ),
			'url'    => get_permalink( $post ),
		);

		// Add tags if available
		$tags = get_the_tags( $post->ID );
		if ( $tags && ! is_wp_error( $tags ) ) {
			$meta['tags'] = array_map( function ( $tag ) {
				return $tag->name;
			}, $tags );
		}

		// Add categories if available
		$categories = get_the_category( $post->ID );
		if ( $categories && ! is_wp_error( $categories ) ) {
			$meta['categories'] = array_map( function ( $cat ) {
				return $cat->name;
			}, $categories );
		}

		// Add excerpt if available
		if ( ! empty( $post->post_excerpt ) ) {
			$meta['excerpt'] = $post->post_excerpt;
		}

		// Build YAML
		$yaml = "---\n";
		foreach ( $meta as $key => $value ) {
			$yaml .= $this->yaml_line( $key, $value );
		}
		$yaml .= "---\n";

		return $yaml;
	}

	/**
	 * Format a single YAML line.
	 *
	 * @param string $key   The key.
	 * @param mixed  $value The value.
	 * @return string YAML line.
	 */
	private function yaml_line( $key, $value ) {
		if ( is_array( $value ) ) {
			if ( empty( $value ) ) {
				return "{$key}: []\n";
			}
			$items = array_map( function ( $item ) {
				return '"' . addslashes( $item ) . '"';
			}, $value );
			return "{$key}: [" . implode( ', ', $items ) . "]\n";
		}

		// Quote strings that might contain special characters
		if ( is_string( $value ) && preg_match( '/[:\-\[\]{}#&*!|>\'"%@`]/', $value ) ) {
			return "{$key}: \"" . addslashes( $value ) . "\"\n";
		}

		return "{$key}: {$value}\n";
	}

	/**
	 * Get the Markdown URL for a post.
	 *
	 * @param WP_Post $post The post object.
	 * @return string The .md URL.
	 */
	private function get_markdown_url( $post ) {
		$permalink = get_permalink( $post );

		// Handle trailing slash
		if ( str_ends_with( $permalink, '/' ) ) {
			$permalink = rtrim( $permalink, '/' );
		}

		return $permalink . '.md';
	}
}
