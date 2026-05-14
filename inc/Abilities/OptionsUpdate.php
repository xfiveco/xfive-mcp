<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class OptionsUpdate extends AbilitiesBase {
	/**
	 * Get configuration for the options update ability.
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
		return 'Options - Update';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Update WordPress options (update_option) or theme mods (set_theme_mod). Use type "option" for wp_options table entries, or "theme_mod" for theme modifications (e.g. custom_logo, nav_menu_locations). For theme mods, the name is the mod name and value is the mod value.';
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
				'type'    => array(
					'type'        => 'string',
					'description' => 'Type of setting to update: "option" for update_option() or "theme_mod" for set_theme_mod().',
					'enum'        => array( 'option', 'theme_mod' ),
					'default'     => 'option',
				),
				'entries' => array(
					'type'        => 'object',
					'description' => 'Object of name => value pairs to update. For options: option_name => value. For theme_mods: mod_name => value (e.g. {"custom_logo": 42} to set the site logo to attachment ID 42).',
				),
			),
			'required'   => array( 'entries' ),
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
				'updated' => array(
					'type'        => 'array',
					'description' => 'List of names that were successfully updated.',
					'items'       => array(
						'type' => 'string',
					),
				),
				'failed'  => array(
					'type'        => 'array',
					'description' => 'List of names that failed to update.',
					'items'       => array(
						'type' => 'string',
					),
				),
				'hint'    => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the options/theme_mod update.
	 *
	 * @param array $args Arguments for updating options or theme mods.
	 * @return array|\WP_Error Array with results on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$type    = $args['type'] ?? 'option';
		$entries = $args['entries'] ?? array();

		if ( empty( $entries ) || ! is_array( $entries ) ) {
			return new \WP_Error( 'missing_param', 'entries must be a non-empty object of name => value pairs.' );
		}

		$updated = array();
		$failed  = array();

		foreach ( $entries as $name => $value ) {
			if ( 'theme_mod' === $type ) {
				set_theme_mod( $name, $value );
				// set_theme_mod doesn't return a success indicator, verify it was set.
				if ( get_theme_mod( $name ) === $value ) {
					$updated[] = $name;
				} else {
					$failed[] = $name;
				}
			} else {
				$result = update_option( $name, $value );
				if ( $result || get_option( $name ) === $value ) {
					$updated[] = $name;
				} else {
					$failed[] = $name;
				}
			}
		}

		$result = array(
			'updated' => $updated,
			'failed'  => $failed,
		);

		if ( ! empty( $failed ) ) {
			$result['hint'] = $type === 'theme_mod'
				? 'For failed theme_mods: confirm the mod name (e.g. custom_logo, nav_menu_locations) and value type (custom_logo expects an attachment ID; nav_menu_locations expects an array { location => menu_id }).'
				: 'For failed options: update_option returns false when the value is unchanged. Names like show_on_front need string "page"; page_on_front needs an integer post ID.';
		} elseif ( in_array( 'show_on_front', $updated, true ) || in_array( 'page_on_front', $updated, true ) ) {
			$result['hint'] = 'Reminder: setting a static front page needs BOTH show_on_front="page" AND page_on_front={post_id}.';
		}

		return $result;
	}
}
