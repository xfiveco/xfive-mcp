<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class OptionsGet extends AbilitiesBase {
	/**
	 * Get configuration for the options get ability.
	 *
	 * @return array Empty array as no configuration is needed.
	 */
	public function get_config(): array {
		return array();
	}

	/**
	 * Get the name of the ability.
	 *
	 * @return string The ability name.
	 */
	public function get_name(): string {
		return 'Options - Get';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Read WordPress options (get_option) or theme mods (get_theme_mod). Use type "option" for wp_options entries, or "theme_mod" for theme modifications (e.g. custom_logo, nav_menu_locations, show_on_front, page_on_front). Pass "names" (array) to read those keys; omit names with type "theme_mod" to return ALL theme mods. A missing key returns null. Read counterpart to xfive-options-options-update — use it to read the current logo, front-page settings, menu locations, etc. before changing them.';
	}

	/**
	 * Get the input schema for the ability.
	 *
	 * @return array Schema defining required input parameters.
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'type'  => array(
					'type'        => 'string',
					'description' => 'Type of setting to read: "option" for get_option() or "theme_mod" for get_theme_mod().',
					'enum'        => array( 'option', 'theme_mod' ),
					'default'     => 'option',
				),
				'names' => array(
					'type'        => 'array',
					'description' => 'Names to read. Required for type "option". For type "theme_mod" you may omit it to return all theme mods.',
					'items'       => array( 'type' => 'string' ),
				),
			),
		);
	}

	/**
	 * Get the output schema for the ability.
	 *
	 * @return array Schema defining the structure of the response.
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'values' => array(
					'type'        => 'object',
					'description' => 'Object of name => value. Value is null when the option/theme_mod is not set.',
				),
				'hint'   => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the options/theme_mod read.
	 *
	 * @param array $args Arguments for reading options or theme mods.
	 * @return array|\WP_Error Array with values on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$type  = $args['type'] ?? 'option';
		$names = $args['names'] ?? array();

		if ( ! is_array( $names ) ) {
			$names = array();
		}

		$values = array();

		if ( 'theme_mod' === $type ) {
			if ( empty( $names ) ) {
				$mods   = get_theme_mods();
				$values = is_array( $mods ) ? $mods : array();

				return array(
					'values' => $values,
					'hint'   => sprintf( '%d theme mod(s) returned. custom_logo is an attachment ID; nav_menu_locations is { location => menu_id }. IDs are site-local.', count( $values ) ),
				);
			}

			foreach ( $names as $name ) {
				$name            = (string) $name;
				$values[ $name ] = get_theme_mod( $name, null );
			}

			return array(
				'values' => $values,
				'hint'   => sprintf( '%d theme mod(s) returned (null = not set).', count( $values ) ),
			);
		}

		if ( empty( $names ) ) {
			return new \WP_Error( 'missing_param', 'names is required for type "option" (an array of option_name strings).' );
		}

		foreach ( $names as $name ) {
			$name            = (string) $name;
			$values[ $name ] = get_option( $name, null );
		}

		return array(
			'values' => $values,
			'hint'   => sprintf( '%d option(s) returned (null = not set). show_on_front is "posts" or "page"; page_on_front is a post ID.', count( $values ) ),
		);
	}
}
