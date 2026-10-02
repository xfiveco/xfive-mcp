<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class StringTranslate extends AbilitiesBase {
	/**
	 * Get configuration for the string translate ability.
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
		return 'WPML - String Translate';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Save translations for WPML String Translation entries, several strings at once, marked complete. Address a string by id (from xfive-wpml-string-list), or by domain + name + value to register it first when WPML has not seen it yet. Covers site title and tagline, theme and plugin interface strings, form labels and messages.';
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
				'strings' => array(
					'type'        => 'array',
					'description' => 'Strings to translate.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'type'        => 'integer',
								'description' => 'String ID from xfive-wpml-string-list.',
							),
							'domain'       => array(
								'type'        => 'string',
								'description' => 'Domain, to register a string that has no id yet.',
							),
							'name'         => array(
								'type'        => 'string',
								'description' => 'String name, to register a string that has no id yet.',
							),
							'value'        => array(
								'type'        => 'string',
								'description' => 'Original text, to register a string that has no id yet.',
							),
							'translations' => array(
								'type'        => 'object',
								'description' => 'Language code => translated text, e.g. {"de": "…", "zh-cn": "…"}.',
							),
						),
						'required'   => array( 'translations' ),
					),
				),
			),
			'required'   => array( 'strings' ),
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
				'saved'  => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'         => array( 'type' => 'integer' ),
							'languages'  => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'registered' => array( 'type' => 'boolean' ),
						),
					),
				),
				'errors' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'hint'   => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Execute the string translations.
	 *
	 * @param array $args Arguments with strings.
	 * @return array|\WP_Error Result on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		global $sitepress;

		$unavailable = $this->wpml_unavailable();
		if ( $unavailable ) {
			return $unavailable;
		}

		if ( ! function_exists( 'icl_add_string_translation' ) ) {
			return new \WP_Error( 'wpml_st_inactive', 'WPML String Translation is not active on this site.' );
		}

		$items = isset( $args['strings'] ) && is_array( $args['strings'] ) ? $args['strings'] : array();

		if ( ! $items ) {
			return new \WP_Error( 'missing_param', 'strings is required.' );
		}

		$active = array_keys( $sitepress->get_active_languages() );
		$saved  = array();
		$errors = array();

		foreach ( $items as $index => $item ) {
			$string_id  = (int) ( $item['id'] ?? 0 );
			$registered = false;

			if ( ! $string_id ) {
				if ( empty( $item['domain'] ) || empty( $item['name'] ) || ! isset( $item['value'] ) ) {
					$errors[] = sprintf( 'Item %d: pass an id, or domain + name + value to register it.', $index );
					continue;
				}

				$string_id = (int) icl_get_string_id( $item['value'], $item['domain'], $item['name'] );

				if ( ! $string_id ) {
					$string_id  = (int) icl_register_string( $item['domain'], $item['name'], $item['value'] );
					$registered = (bool) $string_id;
				}

				if ( ! $string_id ) {
					$errors[] = sprintf( 'Item %1$d: WPML did not register "%2$s" in domain "%3$s".', $index, $item['name'], $item['domain'] );
					continue;
				}
			}

			$languages = array();

			foreach ( (array) ( $item['translations'] ?? array() ) as $language => $text ) {
				if ( ! in_array( $language, $active, true ) ) {
					$errors[] = sprintf( 'String %1$d: language "%2$s" is not active. Active: %3$s.', $string_id, $language, implode( ', ', $active ) );
					continue;
				}

				if ( icl_add_string_translation( $string_id, $language, (string) $text, ICL_TM_COMPLETE ) ) {
					$languages[] = $language;
				} else {
					$errors[] = sprintf( 'String %1$d: the "%2$s" translation was not saved.', $string_id, $language );
				}
			}

			$saved[] = array(
				'id'         => $string_id,
				'languages'  => $languages,
				'registered' => $registered,
			);
		}

		return array(
			'saved'  => $saved,
			'errors' => $errors,
			'hint'   => $errors ? 'Some translations were not saved; see errors.' : 'Translations saved as complete. Load the page in that language to check them.',
		);
	}
}
