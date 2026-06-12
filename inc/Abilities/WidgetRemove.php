<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WidgetRemove extends AbilitiesBase {

	public function get_config(): array {
		return array();
	}

	public function get_name(): string {
		return 'Widget - Remove';
	}

	public function get_description(): string {
		return 'Remove a widget from a sidebar. Provide widget_id (e.g. "nav_menu-3"). The widget is removed from all sidebars it appears in and its stored settings are deleted.';
	}

	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'widget_id' => array(
					'type'        => 'string',
					'description' => 'Widget ID to remove (e.g. "nav_menu-3").',
				),
			),
			'required'   => array( 'widget_id' ),
		);
	}

	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'removed_from' => array(
					'type'        => 'array',
					'description' => 'Sidebar IDs the widget was removed from.',
					'items'       => array( 'type' => 'string' ),
				),
				'hint'         => array( 'type' => 'string' ),
			),
		);
	}

	public function execute_callback( array $args = array() ): array|object {
		$widget_id = $args['widget_id'] ?? '';

		if ( empty( $widget_id ) ) {
			return new \WP_Error( 'missing_param', 'widget_id is required.' );
		}
		if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			return new \WP_Error( 'bad_widget_id', 'widget_id must be in format "{type}-{number}".' );
		}

		$type   = $matches[1];
		$number = (int) $matches[2];

		// Remove from all sidebars.
		$sidebars     = wp_get_sidebars_widgets();
		$removed_from = array();

		foreach ( $sidebars as $sidebar_id => $widgets ) {
			if ( ! is_array( $widgets ) ) {
				continue;
			}
			$index = array_search( $widget_id, $widgets, true );
			if ( false !== $index ) {
				unset( $sidebars[ $sidebar_id ][ $index ] );
				$sidebars[ $sidebar_id ] = array_values( $sidebars[ $sidebar_id ] );
				$removed_from[]          = $sidebar_id;
			}
		}

		wp_set_sidebars_widgets( $sidebars );

		// Delete the widget instance settings.
		$option_key = 'widget_' . $type;
		$instances  = get_option( $option_key, array() );
		if ( isset( $instances[ $number ] ) ) {
			unset( $instances[ $number ] );
			update_option( $option_key, $instances );
		}

		return array(
			'removed_from' => $removed_from,
			'hint'         => empty( $removed_from )
				? sprintf( 'Widget "%s" was not found in any sidebar; nothing removed.', $widget_id )
				: sprintf( 'Widget "%1$s" removed from %2$d sidebar(s) and its settings deleted.', $widget_id, count( $removed_from ) ),
		);
	}
}
