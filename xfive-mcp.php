<?php
/**
 * Plugin Name: xfive Socrates - WordPress MCP Server with Abilities API
 * Description: MCP server with WordPress Abilities API
 * Version: 1.2.0
 * Author: Xfive
 * Author URI: https://xfive.co
 * Copyright: Xfive
 * Text Domain: xfive-mcp
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Xfive\MCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'XFIVE_MCP_NAMESPACE', 'XfiveMCP\\' );

/**
 * Plugin version.
 *
 * @var string
 */
if ( ! defined( 'XFIVE_MCP_VERSION' ) ) {
	define( 'XFIVE_MCP_VERSION', '1.2.0' );
}

/**
 * Plugin slug.
 *
 * @var string
 */
if ( ! defined( 'XFIVE_MCP_SLUG' ) ) {
	define( 'XFIVE_MCP_SLUG', 'xfive-mcp' );
}

/**
 * Plugin file.
 *
 * @var string
 */
if ( ! defined( 'XFIVE_MCP_FILE' ) ) {
	define( 'XFIVE_MCP_FILE', __FILE__ );
}

/**
 * Plugin directory.
 *
 * @var string
 */
if ( ! defined( 'XFIVE_MCP_DIR' ) ) {
	define( 'XFIVE_MCP_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Plugin url.
 *
 * @var string
 */
if ( ! defined( 'XFIVE_MCP_URL' ) ) {
	define( 'XFIVE_MCP_URL', plugin_dir_url( __FILE__ ) );
}

spl_autoload_register(
	function ( $class_name ) {
		$base_directory = XFIVE_MCP_DIR . 'inc/';

		$namespace_prefix_length = strlen( XFIVE_MCP_NAMESPACE );

		if ( strncmp( XFIVE_MCP_NAMESPACE, $class_name, $namespace_prefix_length ) !== 0 ) {
			return;
		}

		$relative_class_name = substr( $class_name, $namespace_prefix_length );

		$class_filename = $base_directory . str_replace( '\\', '/', $relative_class_name ) . '.php';

		if ( file_exists( $class_filename ) ) {
			require $class_filename;
		}
	}
);

require_once 'vendor/autoload.php';

\XfiveMCP\WP\Plugin::get_instance();
