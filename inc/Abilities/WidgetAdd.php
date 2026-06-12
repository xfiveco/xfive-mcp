<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WidgetAdd extends AbilitiesBase {

	public function get_config(): array {
		return array();
	}

	public function get_name(): string {
		return 'Widget - Add';
	}

	public function get_description(): string {
		return 'Add a widget to a sidebar. Provide sidebar_id (e.g. "chisel-sidebar-footer-1"), widget type (e.g. "nav_menu", "text", "custom_html", "block"), and settings object. For nav_menu: {"title":"...", "nav_menu":<menu_term_id>}. For text: {"title":"...", "text":"..."}. For custom_html: {"title":"...", "content":"..."}. For block: {"content":"<!-- wp:paragraph --><p>...</p><!-- /wp:paragraph -->"}. Returns the new widget_id (e.g. "nav_menu-4") and updated widget list for the sidebar. Position defaults to the end; pass position (0-based index) to insert at a specific slot.';
	}

	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'sidebar_id' => array(
					'type'        => 'string',
					'description' => 'Target sidebar ID (e.g. "chisel-sidebar-footer-1"). Use widgets-list to discover.',
				),
				'type'       => array(
					'type'        => 'string',
					'description' => 'Widget type slug. Common: "nav_menu", "text", "custom_html", "block", "search", "recent-posts", "recent-comments", "categories", "archives", "tag_cloud", "meta", "calendar", "pages", "rss".',
				),
				'settings'   => array(
					'type'        => 'object',
					'description' => 'Widget instance settings. Shape depends on widget type. See description for common types.',
				),
				'position'   => array(
					'type'        => 'integer',
					'description' => '0-based position in the sidebar. Optional; defaults to end.',
				),
			),
			'required'   => array( 'sidebar_id', 'type' ),
		);
	}

	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'widget_id' => array( 'type' => 'string' ),
				'sidebar'   => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'hint'      => array( 'type' => 'string' ),
			),
		);
	}

	public function execute_callback( array $args = array() ): array|object {
		$sidebar_id = $args['sidebar_id'] ?? '';
		$type       = $args['type'] ?? '';
		$settings   = $args['settings'] ?? array();
		$position   = $args['position'] ?? null;

		if ( empty( $sidebar_id ) ) {
			return new \WP_Error( 'missing_param', 'sidebar_id is required.' );
		}
		if ( empty( $type ) ) {
			return new \WP_Error( 'missing_param', 'type is required.' );
		}

		$sidebars = wp_get_sidebars_widgets();
		if ( ! isset( $sidebars[ $sidebar_id ] ) ) {
			return new \WP_Error( 'unknown_sidebar', sprintf( 'Sidebar "%s" not registered. Use widgets-list to discover valid IDs.', $sidebar_id ) );
		}

		// Get existing instances for this widget type.
		$option_key = 'widget_' . $type;
		$instances  = get_option( $option_key, array() );

		// Strip `_multiwidget` metadata for slot calculation.
		$multi = $instances['_multiwidget'] ?? 1;
		unset( $instances['_multiwidget'] );

		// Find next free numeric index.
		$next_number = 1;
		if ( ! empty( $instances ) ) {
			$next_number = max( array_map( 'intval', array_keys( $instances ) ) ) + 1;
		}

		// Store the new instance.
		$instances[ $next_number ]  = $settings;
		$instances['_multiwidget']  = $multi;
		update_option( $option_key, $instances );

		// Add widget ID to the sidebar.
		$widget_id            = $type . '-' . $next_number;
		$current              = array_values( (array) $sidebars[ $sidebar_id ] );
		if ( null !== $position && $position >= 0 && $position <= count( $current ) ) {
			array_splice( $current, $position, 0, $widget_id );
		} else {
			$current[] = $widget_id;
		}
		$sidebars[ $sidebar_id ] = $current;
		wp_set_sidebars_widgets( $sidebars );

		return array(
			'widget_id' => $widget_id,
			'sidebar'   => $current,
			'hint'      => sprintf( 'Widget "%1$s" added to "%2$s" (%3$d widget(s) now). Verify/edit with widgets-list / widget-update.', $widget_id, $sidebar_id, count( $current ) ),
		);
	}
}
