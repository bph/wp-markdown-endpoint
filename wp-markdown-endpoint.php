<?php
/**
 * Plugin Name: WP Markdown Endpoint
 * Description: Exposes posts and pages as Markdown via .md URL suffix, Accept header negotiation, and auto-discovery links.
 * Version: 1.0.1
 * Author: Birgit Pauli-Haack
 * License: GPL-2.0+
 * Text Domain: wp-markdown-endpoint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPMD_VERSION', '1.0.1' );
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
