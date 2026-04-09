<?php
/**
 * Converts HTML content to Markdown.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPMD_Converter {

	/**
	 * Convert HTML to Markdown.
	 *
	 * @param string $html HTML content.
	 * @return string Markdown content.
	 */
	public function convert( $html ) {
		// Strip Gutenberg block comments
		$html = preg_replace( '/<!--\s*\/?wp:[^>]*-->/s', '', $html );

		// Remove noise elements and normalize layout wrappers before anything else
		$html = $this->pre_process_html( $html );

		// Decode HTML entities first
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Process block-level elements first
		$markdown = $this->convert_block_elements( $html );

		// Process inline elements
		$markdown = $this->convert_inline_elements( $markdown );

		// Clean up extra whitespace
		$markdown = $this->cleanup( $markdown );

		return $markdown;
	}

	/**
	 * Pre-process raw HTML before conversion.
	 *
	 * Strips noise elements (scripts, styles, SVG, etc.) entirely and replaces
	 * structural layout wrappers (div, section, etc.) with newlines so their
	 * inner text is preserved but the tags don't collapse surrounding words.
	 *
	 * @param string $html Raw HTML.
	 * @return string Cleaned HTML.
	 */
	private function pre_process_html( $html ) {
		// Remove entire tag + inner content for non-content elements.
		// Use \b (word boundary) to avoid matching e.g. <scriptX> as <script>.
		$noise_tags = array( 'script', 'style', 'noscript', 'svg', 'iframe', 'canvas', 'template' );
		foreach ( $noise_tags as $tag ) {
			$html = preg_replace( '/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/si', '', $html );
			// Also handle self-closed variants like <svg ... />.
			$html = preg_replace( '/<' . $tag . '\b[^>]*\/>/si', '', $html );
		}

		// Replace structural layout wrapper tags with a newline, preserving inner content.
		$layout_tags = array( 'div', 'section', 'article', 'aside', 'header', 'footer', 'nav', 'main', 'address' );
		foreach ( $layout_tags as $tag ) {
			$html = preg_replace( '/<\/?' . $tag . '\b[^>]*>/i', "\n", $html );
		}

		// Strip inline wrapper tags (keep inner content, no extra whitespace).
		$inline_wrapper_tags = array( 'span', 'label', 'ins', 'mark', 'time', 'abbr', 'cite', 'small', 'sub', 'sup', 'button', 'form', 'input', 'select', 'textarea', 'picture', 'source' );
		foreach ( $inline_wrapper_tags as $tag ) {
			$html = preg_replace( '/<\/?' . $tag . '\b[^>]*>/i', '', $html );
		}

		return $html;
	}

	/**
	 * Normalize plain text extracted from HTML.
	 *
	 * Collapses runs of spaces/tabs to a single space and trims the result.
	 * Does NOT touch newlines (those are handled by cleanup()).
	 *
	 * @param string $text Plain text.
	 * @return string Normalized text.
	 */
	private function normalize_text( $text ) {
		// Remove control characters except newlines/tabs.
		$text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text );
		// Collapse multiple non-newline whitespace to a single space.
		$text = preg_replace( '/[^\S\n]+/', ' ', $text );
		return trim( $text );
	}

	/**
	 * Convert block-level HTML elements.
	 *
	 * @param string $html HTML content.
	 * @return string Partially converted content.
	 */
	private function convert_block_elements( $html ) {
		// Headings
		$html = preg_replace_callback(
			'/<h([1-6])[^>]*>(.*?)<\/h\1>/si',
			function ( $matches ) {
				$level = (int) $matches[1];
				$text  = $this->normalize_text( strip_tags( $matches[2] ) );
				return "\n" . str_repeat( '#', $level ) . ' ' . $text . "\n";
			},
			$html
		);

		// Code blocks (pre > code)
		$html = preg_replace_callback(
			'/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/si',
			function ( $matches ) {
				$code = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				return "\n```\n" . trim( $code ) . "\n```\n";
			},
			$html
		);

		// Pre blocks without code
		$html = preg_replace_callback(
			'/<pre[^>]*>(.*?)<\/pre>/si',
			function ( $matches ) {
				$code = html_entity_decode( $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				return "\n```\n" . trim( strip_tags( $code ) ) . "\n```\n";
			},
			$html
		);

		// Blockquotes
		$html = preg_replace_callback(
			'/<blockquote[^>]*>(.*?)<\/blockquote>/si',
			function ( $matches ) {
				$text  = $this->normalize_text( strip_tags( $matches[1] ) );
				$lines = array_filter( array_map( 'trim', explode( "\n", $text ) ) );
				$lines = array_map( function ( $line ) {
					return '> ' . $line;
				}, $lines );
				return "\n" . implode( "\n", $lines ) . "\n";
			},
			$html
		);

		// Unordered lists
		$html = preg_replace_callback(
			'/<ul[^>]*>(.*?)<\/ul>/si',
			function ( $matches ) {
				return $this->convert_list( $matches[1], '-' );
			},
			$html
		);

		// Ordered lists
		$html = preg_replace_callback(
			'/<ol[^>]*>(.*?)<\/ol>/si',
			function ( $matches ) {
				return $this->convert_list( $matches[1], '1.' );
			},
			$html
		);

		// Paragraphs
		$html = preg_replace_callback(
			'/<p[^>]*>(.*?)<\/p>/si',
			function ( $matches ) {
				$inner = preg_replace( '/[^\S\n]+/', ' ', $matches[1] );
				return "\n" . trim( $inner ) . "\n";
			},
			$html
		);

		// Horizontal rules
		$html = preg_replace( '/<hr[^>]*>/i', "\n---\n", $html );

		// Line breaks
		$html = preg_replace( '/<br\s*\/?>/i', "  \n", $html );

		// Figures with images
		$html = preg_replace_callback(
			'/<figure[^>]*>(.*?)<\/figure>/si',
			function ( $matches ) {
				// Extract img and figcaption
				$content = $matches[1];
				preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/i', $content, $img );
				if ( empty( $img ) ) {
					preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $img );
				}
				preg_match( '/<figcaption[^>]*>(.*?)<\/figcaption>/si', $content, $caption );

				if ( ! empty( $img[1] ) ) {
					$alt = isset( $img[2] ) ? $img[2] : '';
					$src = $img[1];
					$md  = "![{$alt}]({$src})";
					if ( ! empty( $caption[1] ) ) {
						$md .= "\n*" . strip_tags( $caption[1] ) . "*";
					}
					return "\n" . $md . "\n";
				}
				return '';
			},
			$html
		);

		return $html;
	}

	/**
	 * Convert inline HTML elements.
	 *
	 * @param string $html HTML content.
	 * @return string Partially converted content.
	 */
	private function convert_inline_elements( $html ) {
		// Images (standalone)
		$html = preg_replace_callback(
			'/<img[^>]+src=["\']([^"\']+)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/i',
			function ( $matches ) {
				return "![{$matches[2]}]({$matches[1]})";
			},
			$html
		);
		$html = preg_replace_callback(
			'/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
			function ( $matches ) {
				return "![]({$matches[1]})";
			},
			$html
		);

		// Links
		$html = preg_replace_callback(
			'/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/si',
			function ( $matches ) {
				$text = strip_tags( $matches[2] );
				return "[{$text}]({$matches[1]})";
			},
			$html
		);

		// Bold
		$html = preg_replace( '/<(strong|b)[^>]*>(.*?)<\/\1>/si', '**$2**', $html );

		// Italic
		$html = preg_replace( '/<(em|i)[^>]*>(.*?)<\/\1>/si', '*$2*', $html );

		// Inline code
		$html = preg_replace( '/<code[^>]*>(.*?)<\/code>/si', '`$1`', $html );

		// Strikethrough
		$html = preg_replace( '/<(del|s|strike)[^>]*>(.*?)<\/\1>/si', '~~$2~~', $html );

		// Remove any remaining HTML tags, then normalize any leftover whitespace.
		$html = strip_tags( $html );
		$html = $this->normalize_text( $html );

		return $html;
	}

	/**
	 * Convert HTML list to Markdown.
	 *
	 * @param string $html   List HTML content.
	 * @param string $marker List marker (- or 1.).
	 * @return string Markdown list.
	 */
	private function convert_list( $html, $marker ) {
		preg_match_all( '/<li[^>]*>(.*?)<\/li>/si', $html, $items );

		if ( empty( $items[1] ) ) {
			return '';
		}

		$lines   = array();
		$counter = 1;
		foreach ( $items[1] as $item ) {
			// Convert inline elements (links, bold, italic, etc.) before stripping remaining tags.
			$text = $this->convert_inline_elements( $item );
			// normalize_text is already applied inside convert_inline_elements; just trim.
			$text = trim( preg_replace( '/[^\S\n]+/', ' ', $text ) );
			if ( '' === $text ) {
				continue; // skip empty list items generated by layout markup
			}
			if ( $marker === '1.' ) {
				$lines[] = $counter . '. ' . $text;
				$counter++;
			} else {
				$lines[] = $marker . ' ' . $text;
			}
		}

		if ( empty( $lines ) ) {
			return '';
		}

		return "\n" . implode( "\n", $lines ) . "\n";
	}

	/**
	 * Clean up the converted Markdown.
	 *
	 * @param string $markdown Markdown content.
	 * @return string Cleaned Markdown.
	 */
	private function cleanup( $markdown ) {
		// Collapse runs of non-newline whitespace (spaces, tabs) to a single space.
		$markdown = preg_replace( '/[^\S\n]+/', ' ', $markdown );

		// Trim every line individually.
		$lines = explode( "\n", $markdown );
		$lines = array_map( 'trim', $lines );

		// Collapse multiple consecutive blank lines into a single blank line.
		$result     = array();
		$prev_blank = false;
		foreach ( $lines as $line ) {
			$is_blank = ( '' === $line );
			if ( $is_blank && $prev_blank ) {
				continue;
			}
			$result[]   = $line;
			$prev_blank = $is_blank;
		}

		$markdown = implode( "\n", $result );

		// Trim leading/trailing whitespace.
		$markdown = trim( $markdown );

		return $markdown;
	}
}
