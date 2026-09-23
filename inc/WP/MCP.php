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
	 * Resolve the user open mode acts as, for the site handling this request.
	 *
	 * Ordered by ID so the identity is stable as users are added. On a network a
	 * site can exist with no administrator of its own - sites created with
	 * wp_insert_site() get no users at all, and a super admin holds no role on a
	 * site they were not added to - so super admins are the fallback. They have
	 * full capabilities on every site in the network, and the scope stays one
	 * site either way because each site has its own endpoint.
	 *
	 * @return int User ID, or 0 when the site has nobody who could act.
	 */
	private function resolve_open_mode_user(): int {
		$admins = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);

		if ( ! empty( $admins ) ) {
			return (int) $admins[0];
		}

		if ( ! is_multisite() ) {
			return 0;
		}

		$fallback = 0;

		foreach ( get_super_admins() as $login ) {
			$user = get_user_by( 'login', $login );

			if ( ! $user ) {
				continue;
			}

			if ( is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
				return (int) $user->ID;
			}

			if ( ! $fallback ) {
				$fallback = (int) $user->ID;
			}
		}

		return $fallback;
	}

	/**
	 * Permission callback.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return bool|\WP_Error
	 */
	public function permission_callback( \WP_REST_Request $request ) {
		if ( defined( 'MCP_OPEN' ) && MCP_OPEN ) {
			$user_id = $this->resolve_open_mode_user();

			if ( ! $user_id ) {
				return new \WP_Error(
					'mcp_no_admin',
					sprintf( 'MCP_OPEN is enabled but no administrator was found for %s.', home_url( '/' ) ),
					array( 'status' => 500 )
				);
			}

			wp_set_current_user( $user_id );

			return true;
		}

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
