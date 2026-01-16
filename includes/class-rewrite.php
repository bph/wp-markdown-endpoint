<?php
/**
 * Handles URL rewriting to support .md suffix.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPMD_Rewrite {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Intercept .md requests early, before WordPress parses the URL
		$this->maybe_redirect_md_request();
	}

	/**
	 * Register the 'format' query variable.
	 *
	 * @param array $vars Existing query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'format';
		return $vars;
	}

	/**
	 * Detect .md suffix early and redirect internally.
	 */
	public function maybe_redirect_md_request() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$request_uri = $_SERVER['REQUEST_URI'];

		// Parse URL to get path without query string
		$parsed = parse_url( $request_uri );
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '';

		// Check if path ends with .md
		if ( str_ends_with( $path, '.md' ) ) {
			// Strip .md and rebuild the URL
			$new_path = substr( $path, 0, -3 );

			// Add trailing slash if WordPress uses them
			if ( substr( $new_path, -1 ) !== '/' && get_option( 'permalink_structure' ) ) {
				$new_path .= '/';
			}

			// Rebuild with query string if present
			$query = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';

			// Update REQUEST_URI and add format parameter
			$_SERVER['REQUEST_URI'] = $new_path . $query;
			$_GET['format']         = 'md';
			$_REQUEST['format']     = 'md';
		}
	}
}
