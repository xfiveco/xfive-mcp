<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TermList extends AbilitiesBase {
	/**
	 * Get configuration for the term list ability.
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
		return 'Term - List';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'List terms in a taxonomy (id, name, slug, parent, count). Use to read term IDs back before assigning them (e.g. to an ACF taxonomy field). Read counterpart to term-create / term-update / term-delete.';
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
				'taxonomy'   => array(
					'type'        => 'string',
					'description' => 'Taxonomy slug to list terms from (e.g. "condition", "category", "post_tag").',
				),
				'search'     => array(
					'type'        => 'string',
					'description' => 'Optional. Only return terms whose name matches this search string.',
				),
				'hide_empty' => array(
					'type'        => 'boolean',
					'description' => 'Optional. When true, only return terms assigned to at least one post. Defaults to false.',
					'default'     => false,
				),
			),
			'required'   => array( 'taxonomy' ),
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
				'terms' => array(
					'type'        => 'array',
					'description' => 'The matching terms.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'term_id' => array( 'type' => 'integer' ),
							'name'    => array( 'type' => 'string' ),
							'slug'    => array( 'type' => 'string' ),
							'parent'  => array( 'type' => 'integer' ),
							'count'   => array( 'type' => 'integer' ),
						),
					),
				),
				'hint'  => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the term list.
	 *
	 * @param array $args Arguments for listing terms.
	 * @return array|\WP_Error Array with terms on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$taxonomy = $args['taxonomy'] ?? '';

		$invalid = $this->validate_taxonomy( $taxonomy );
		if ( $invalid instanceof \WP_Error ) {
			return $invalid;
		}

		$query_args = array(
			'taxonomy'               => $taxonomy,
			'hide_empty'             => ! empty( $args['hide_empty'] ),
			'update_term_meta_cache' => false,
		);

		if ( ! empty( $args['search'] ) ) {
			$query_args['search'] = $args['search'];
		}

		$terms = get_terms( $query_args );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$result = array();
		foreach ( $terms as $term ) {
			$result[] = array(
				'term_id' => (int) $term->term_id,
				'name'    => $term->name,
				'slug'    => $term->slug,
				'parent'  => (int) $term->parent,
				'count'   => (int) $term->count,
			);
		}

		return array(
			'terms' => $result,
			'hint'  => sprintf( '%1$d term(s) in "%2$s".', count( $result ), $taxonomy ),
		);
	}
}
