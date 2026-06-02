<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TermCreate extends AbilitiesBase {
	/**
	 * Get configuration for the term create ability.
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
		return 'Term - Create';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Create a taxonomy term. Provide name + taxonomy (required); slug, description, parent (term ID) optional. If a term with the same name already exists in the taxonomy, returns its existing term_id (does not error). Returns the term_id for assigning to posts or ACF taxonomy fields.';
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
				'name'        => array(
					'type'        => 'string',
					'description' => 'The term name (display label).',
				),
				'taxonomy'    => array(
					'type'        => 'string',
					'description' => 'Taxonomy slug to create the term in (e.g. "condition").',
				),
				'slug'        => array(
					'type'        => 'string',
					'description' => 'Optional term slug. Auto-generated from name when omitted.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional term description.',
				),
				'parent'      => array(
					'type'        => 'integer',
					'description' => 'Optional parent term ID (for hierarchical taxonomies).',
				),
			),
			'required'   => array( 'name', 'taxonomy' ),
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
				'term_id'  => array(
					'type'        => 'integer',
					'description' => 'The term ID (existing or newly created).',
				),
				'existing' => array(
					'type'        => 'boolean',
					'description' => 'True when a term with this name already existed and its ID was returned.',
				),
				'hint'     => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the term creation.
	 *
	 * @param array $args Arguments for creating a term.
	 * @return array|\WP_Error Array with term data on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$name     = $args['name'] ?? '';
		$taxonomy = $args['taxonomy'] ?? '';

		if ( '' === $name || '' === $taxonomy ) {
			return new \WP_Error( 'missing_param', 'name and taxonomy are required.' );
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'invalid_taxonomy', sprintf( 'Taxonomy "%s" is not registered.', $taxonomy ) );
		}

		// Return the existing term if one with this name already exists.
		$existing = get_term_by( 'name', $name, $taxonomy );
		if ( $existing instanceof \WP_Term ) {
			return array(
				'term_id'  => (int) $existing->term_id,
				'existing' => true,
				'hint'     => sprintf( 'Term "%1$s" already existed in "%2$s".', $name, $taxonomy ),
			);
		}

		$insert_args = array();
		if ( ! empty( $args['slug'] ) ) {
			$insert_args['slug'] = $args['slug'];
		}
		if ( ! empty( $args['description'] ) ) {
			$insert_args['description'] = $args['description'];
		}
		if ( ! empty( $args['parent'] ) ) {
			$insert_args['parent'] = (int) $args['parent'];
		}

		$result = wp_insert_term( $name, $taxonomy, $insert_args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'term_id'  => (int) $result['term_id'],
			'existing' => false,
			'hint'     => sprintf( 'Created term "%1$s" in "%2$s".', $name, $taxonomy ),
		);
	}
}
