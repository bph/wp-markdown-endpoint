<?php
/**
 * Plugin Name: WP Markdown Endpoint
 * Plugin URI: https://github.com/bph/wp-markdown-endpoint
 * Description: Exposes posts and pages as Markdown via .md URL suffix, Accept header negotiation, and auto-discovery links.
 * Version: 1.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Birgit Pauli-Haack
 * Author URI: https://icodeforapurpose.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPMD_VERSION', '1.1.0' );
define( 'WPMD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once WPMD_PLUGIN_DIR . 'includes/class-rewrite.php';
require_once WPMD_PLUGIN_DIR . 'includes/class-converter.php';
require_once WPMD_PLUGIN_DIR . 'includes/class-output.php';

// Initialize rewrite handler (needs to run early for URL interception)
$wpmd_rewrite = new WPMD_Rewrite();
$wpmd_rewrite->init();

// Initialize output handler
$wpmd_output = new WPMD_Output();
$wpmd_output->init();
