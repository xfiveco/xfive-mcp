<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base class for all Abilities.
 *
 * Defines the interface that all ability classes must implement.
 */
abstract class AbilitiesBase {

	/**
	 * Get ability configuration.
	 *
	 * @return array Configuration array.
	 */
	abstract public function get_config(): array;

	/**
	 * Get ability name.
	 *
	 * @return string Ability name.
	 */
	abstract public function get_name(): string;

	/**
	 * Get ability description.
	 *
	 * @return string Ability description.
	 */
	abstract public function get_description(): string;

	/**
	 * Get input schema definition.
	 *
	 * @return array Input schema array.
	 */
	abstract public function get_input_schema(): array;

	/**
	 * Get output schema definition.
	 *
	 * @return array Output schema array.
	 */
	abstract public function get_output_schema(): array;

	/**
	 * Execute the ability callback.
	 *
	 * @param array $args Ability arguments.
	 * @return array|\WP_Error Result array or error object.
	 */
	abstract public function execute_callback( array $args = array() );

	/**
	 * Permission callback to check if user can execute this ability.
	 *
	 * @return bool Whether the user has permission.
	 */
	public function permission_callback(): bool {
		return is_user_logged_in();
	}

	/**
	 * Validate that a taxonomy slug is present and registered.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return \WP_Error|null WP_Error when invalid, null when valid.
	 */
	protected function validate_taxonomy( string $taxonomy ): ?\WP_Error {
		if ( '' === $taxonomy ) {
			return new \WP_Error( 'missing_param', 'taxonomy is required.' );
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'invalid_taxonomy', sprintf( 'Taxonomy "%s" is not registered.', $taxonomy ) );
		}

		return null;
	}
}
