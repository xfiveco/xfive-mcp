<?php

namespace XfiveMCP\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use XfiveMCP\Trait\Singleton;
use XfiveMCP\Trait\Config;

class Plugin {

	use Singleton;
	use Config;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_core' ) );
		add_action( 'init', array( $this, 'i18n' ) );
	}

	/**
	 * Load plugin core.
	 */
	public function load_plugin_core() {
		global $pagenow;

		if ( $pagenow === 'post.php' ) {
			return;
		}

		Abilities::get_instance();

		if ( class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			\WP\MCP\Core\McpAdapter::instance();
		}

		MCP::get_instance();
	}

	/**
	 * Load plugin translation strings
	 */
	public function i18n() {
		// Load user's custom translations from wp-content/languages/ folder.
		load_textdomain(
			'xfive-mcp',
			sprintf(
				'%s/%s-%s.mo',
				WP_LANG_DIR,
				XFIVE_MCP_SLUG,
				get_locale(),
			)
		);
	}
}
