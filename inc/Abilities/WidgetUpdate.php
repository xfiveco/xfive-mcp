<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WidgetUpdate extends AbilitiesBase {

	public function get_config(): array {
		return array();
	}

	public function get_name(): string {
		return 'Widget - Update';
	}

	public function get_description(): string {
		return 'Update an existing widget\'s settings. Provide widget_id (e.g. "nav_menu-3") and a settings object that will be merged with current settings. Pass replace:true to replace settings entirely instead of merging. Use widgets-list to find widget IDs.';
	}

	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'widget_id' => array(
					'type'        => 'string',
					'description' => 'Widget ID (e.g. "nav_menu-3", "text-2"). Format: "{type}-{number}".',
				),
				'settings'  => array(
					'type'        => 'object',
					'description' => 'Settings to merge (or replace) on the widget.',
				),
				'replace'   => array(
					'type'        => 'boolean',
					'description' => 'If true, replace settings entirely. Default false (merge).',
					'default'     => false,
				),
			),
			'required'   => array( 'widget_id', 'settings' ),
		);
	}

	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'widget_id' => array( 'type' => 'string' ),
				'settings'  => array( 'type' => 'object' ),
				'hint'      => array( 'type' => 'string' ),
			),
		);
	}

	public function execute_callback( array $args = array() ): array|object {
		$widget_id = $args['widget_id'] ?? '';
		$settings  = $args['settings'] ?? array();
		$replace   = ! empty( $args['replace'] );

		if ( empty( $widget_id ) ) {
			return new \WP_Error( 'missing_param', 'widget_id is required.' );
		}
		if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $matches ) ) {
			return new \WP_Error( 'bad_widget_id', 'widget_id must be in format "{type}-{number}".' );
		}

		$type   = $matches[1];
		$number = (int) $matches[2];

		$option_key = 'widget_' . $type;
		$instances  = get_option( $option_key, array() );

		if ( ! isset( $instances[ $number ] ) ) {
			return new \WP_Error( 'not_found', sprintf( 'Widget "%s" not found.', $widget_id ) );
		}

		$current  = $instances[ $number ];
		$new_data = $replace ? $settings : array_merge( (array) $current, (array) $settings );

		$instances[ $number ] = $new_data;
		update_option( $option_key, $instances );

		return array(
			'widget_id' => $widget_id,
			'settings'  => $new_data,
			'hint'      => sprintf( 'Widget "%1$s" %2$s. Stored settings returned for verification.', $widget_id, $replace ? 'settings replaced' : 'settings merged' ),
		);
	}
}
