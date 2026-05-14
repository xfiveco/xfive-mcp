<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WidgetsList extends AbilitiesBase {

	public function get_config(): array {
		return array();
	}

	public function get_name(): string {
		return 'Widgets - List';
	}

	public function get_description(): string {
		return 'List all registered sidebars and the widgets assigned to each. Returns sidebar IDs, names, and for each sidebar the ordered list of widgets with their IDs, types (e.g. nav_menu, block, text), and instance settings. Use this to discover sidebar IDs (e.g. "chisel-sidebar-footer-1") before adding/updating widgets.';
	}

	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'sidebar_id' => array(
					'type'        => 'string',
					'description' => 'Optional. If provided, returns widgets only for that sidebar. Otherwise lists all sidebars.',
				),
			),
		);
	}

	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'sidebars' => array(
					'type'        => 'array',
					'description' => 'Registered sidebars with their widgets.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'      => array( 'type' => 'string' ),
							'name'    => array( 'type' => 'string' ),
							'widgets' => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'widget_id' => array( 'type' => 'string' ),
										'type'      => array( 'type' => 'string' ),
										'settings'  => array( 'type' => 'object' ),
									),
								),
							),
						),
					),
				),
			),
		);
	}

	public function execute_callback( array $args = array() ): array|object {
		$filter_sidebar = $args['sidebar_id'] ?? '';
		$registered     = $GLOBALS['wp_registered_sidebars'] ?? array();
		$sidebars_map   = wp_get_sidebars_widgets();

		$results = array();

		foreach ( $registered as $sidebar_id => $sidebar_data ) {
			if ( $filter_sidebar && $filter_sidebar !== $sidebar_id ) {
				continue;
			}

			$widget_ids = $sidebars_map[ $sidebar_id ] ?? array();
			$widgets    = array();

			foreach ( (array) $widget_ids as $widget_id ) {
				$widgets[] = $this->get_widget_data( $widget_id );
			}

			$results[] = array(
				'id'      => $sidebar_id,
				'name'    => $sidebar_data['name'] ?? $sidebar_id,
				'widgets' => $widgets,
			);
		}

		return array( 'sidebars' => $results );
	}

	/**
	 * Get data for a single widget by its widget_id (e.g. "nav_menu-3").
	 *
	 * @param string $widget_id Widget ID.
	 * @return array
	 */
	private function get_widget_data( string $widget_id ): array {
		// Widget ID format: "{type}-{instance_number}".
		if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			return array(
				'widget_id' => $widget_id,
				'type'      => 'unknown',
				'settings'  => array(),
			);
		}

		$type     = $matches[1];
		$number   = (int) $matches[2];
		$instances = get_option( 'widget_' . $type, array() );
		$settings = $instances[ $number ] ?? array();

		return array(
			'widget_id' => $widget_id,
			'type'      => $type,
			'settings'  => $settings,
		);
	}
}
