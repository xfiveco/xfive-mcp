<?php

namespace XfiveMCP\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use XfiveMCP\Trait\Singleton;
use XfiveMCP\Trait\Config;

class MCP {

	use Singleton;
	use Config;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action( 'mcp_adapter_init', array( $this, 'register_mcp_server' ) );
	}

	/**
	 * Register MCP server.
	 *
	 * @param \WP\MCP\Adapter $adapter MCP adapter.
	 */
	public function register_mcp_server( $adapter ) {
		$adapter->create_server(
			'xfivemcp', // Unique server identifier.
			'xfive-mcp', // REST API namespace.
			'mcp', // REST API route.
			'Xfive MCP Server', // Server name.
			'Xfive MCP Server', // Server description.
			'v1.0.0', // Server version.
			array(
				\WP\MCP\Transport\HttpTransport::class,
			),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_tools( true ),
			array(),
			array(),
			array( $this, 'permission_callback' )
		);
	}

	/**
	 * Permission callback.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return bool|\WP_Error
	 */
	public function permission_callback( \WP_REST_Request $request ) {
		$auth = $request->get_header( 'authorization' );
		if ( strpos( $auth ?? '', 'Basic ' ) !== 0 ) {
			return new \WP_Error( 'mcp_no_auth', 'Basic Auth required', array( 'status' => 401 ) );
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Used for HTTP Basic Auth header decoding.
		$creds        = base64_decode( substr( $auth, 6 ) );
		$credentials  = explode( ':', $creds, 2 );
		$username     = $credentials[0];
		$app_password = $credentials[1];

		// App password auth (the missing piece).
		$user = wp_authenticate_application_password( null, $username, $app_password );

		if ( ! $user || ! user_can( $user, 'edit_posts' ) ) {
			return new \WP_Error( 'mcp_forbidden', 'Invalid app password or insufficient permissions', array( 'status' => 401 ) );
		}

		wp_set_current_user( $user->ID );
		return true;
	}
}
