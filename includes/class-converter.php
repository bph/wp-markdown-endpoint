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
				$text  = strip_tags( $matches[2] );
				return "\n" . str_repeat( '#', $level ) . ' ' . trim( $text ) . "\n";
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
				$text  = strip_tags( $matches[1] );
				$lines = explode( "\n", trim( $text ) );
				$lines = array_map( function ( $line ) {
					return '> ' . trim( $line );
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
				return "\n" . trim( $matches[1] ) . "\n";
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

		// Remove any remaining HTML tags
		$html = strip_tags( $html );

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
			$text = trim( strip_tags( $item ) );
			if ( $marker === '1.' ) {
				$lines[] = $counter . '. ' . $text;
				$counter++;
			} else {
				$lines[] = $marker . ' ' . $text;
			}
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
		// Remove excessive blank lines
		$markdown = preg_replace( '/\n{3,}/', "\n\n", $markdown );

		// Trim leading/trailing whitespace
		$markdown = trim( $markdown );

		return $markdown;
	}
}
