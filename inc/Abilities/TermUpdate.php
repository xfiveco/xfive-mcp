<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TermUpdate extends AbilitiesBase {
	/**
	 * Get configuration for the term update ability.
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
		return 'Term - Update';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Update an existing taxonomy term. Provide term_id + taxonomy (required); any of name, slug, description, parent, and meta (object of non-ACF term meta_key => value; pass null as a value to delete that meta key) to change. Only the provided fields are updated. For ACF term fields use xfive-acf-acf-field-update with object_id "term_{id}".';
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
				'term_id'     => array(
					'type'        => 'integer',
					'description' => 'The ID of the term to update.',
				),
				'taxonomy'    => array(
					'type'        => 'string',
					'description' => 'Taxonomy slug the term belongs to (e.g. "condition").',
				),
				'name'        => array(
					'type'        => 'string',
					'description' => 'Optional new term name.',
				),
				'slug'        => array(
					'type'        => 'string',
					'description' => 'Optional new term slug.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional new term description.',
				),
				'parent'      => array(
					'type'        => 'integer',
					'description' => 'Optional new parent term ID (for hierarchical taxonomies).',
				),
				'meta'        => array(
					'type'        => 'object',
					'description' => 'Optional non-ACF term meta to set: meta_key => value. Pass null as a value to delete that key.',
				),
			),
			'required'   => array( 'term_id', 'taxonomy' ),
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
				'term_id' => array(
					'type'        => 'integer',
					'description' => 'The updated term ID.',
				),
				'hint'    => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the term update.
	 *
	 * @param array $args Arguments for updating a term.
	 * @return array|\WP_Error Array with term data on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$term_id  = (int) ( $args['term_id'] ?? 0 );
		$taxonomy = $args['taxonomy'] ?? '';

		if ( 0 === $term_id || '' === $taxonomy ) {
			return new \WP_Error( 'missing_param', 'term_id and taxonomy are required.' );
		}

		$invalid = $this->validate_taxonomy( $taxonomy );
		if ( $invalid instanceof \WP_Error ) {
			return $invalid;
		}

		$update_args = array();
		foreach ( array( 'name', 'slug', 'description' ) as $key ) {
			if ( isset( $args[ $key ] ) && '' !== $args[ $key ] ) {
				$update_args[ $key ] = $args[ $key ];
			}
		}
		if ( isset( $args['parent'] ) ) {
			$update_args['parent'] = (int) $args['parent'];
		}

		$has_meta = ! empty( $args['meta'] ) && is_array( $args['meta'] );

		if ( empty( $update_args ) && ! $has_meta ) {
			return new \WP_Error( 'no_changes', 'Provide at least one of name, slug, description, parent, meta to update.' );
		}

		if ( ! empty( $update_args ) ) {
			$result = wp_update_term( $term_id, $taxonomy, $update_args );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		} elseif ( ! get_term( $term_id, $taxonomy ) instanceof \WP_Term ) {
			return new \WP_Error( 'invalid_term', sprintf( 'Term %1$d not found in "%2$s".', $term_id, $taxonomy ) );
		}

		$meta_keys = array();
		if ( $has_meta ) {
			foreach ( $args['meta'] as $key => $value ) {
				$key = (string) $key;
				if ( null === $value ) {
					delete_term_meta( $term_id, $key );
				} else {
					update_term_meta( $term_id, $key, $value );
				}
				$meta_keys[] = $key;
			}
		}

		$hint = sprintf( 'Updated term %1$d in "%2$s".', $term_id, $taxonomy );
		if ( $meta_keys ) {
			$hint .= sprintf( ' Meta set: %s.', implode( ', ', $meta_keys ) );
		}

		return array(
			'term_id' => $term_id,
			'hint'    => $hint,
		);
	}
}
