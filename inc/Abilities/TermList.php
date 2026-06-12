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
		return 'List terms in a taxonomy. Each term returns id, name, slug, parent, count, description and term meta (non-ACF meta_key => value; for ACF term fields use xfive-acf-acf-field-get with object_id "term_{id}"). Supports search, include/exclude, parent, orderby/order, number/offset paging, and fields ("all" default, or "ids" for a flat ID array). Use to read term IDs back before assigning them or to enumerate a taxonomy for migration. Read counterpart to term-create / term-update / term-delete.';
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
				'include'    => array(
					'type'        => 'array',
					'description' => 'Optional. Only return these term IDs.',
					'items'       => array( 'type' => 'integer' ),
				),
				'exclude'    => array(
					'type'        => 'array',
					'description' => 'Optional. Term IDs to exclude.',
					'items'       => array( 'type' => 'integer' ),
				),
				'parent'     => array(
					'type'        => 'integer',
					'description' => 'Optional. Only return direct children of this parent term ID (0 = top-level).',
				),
				'orderby'    => array(
					'type'        => 'string',
					'description' => 'Optional order field: name (default), slug, term_id, count, parent.',
					'default'     => 'name',
				),
				'order'      => array(
					'type'        => 'string',
					'description' => 'ASC (default) or DESC.',
					'enum'        => array( 'ASC', 'DESC' ),
					'default'     => 'ASC',
				),
				'number'     => array(
					'type'        => 'integer',
					'description' => 'Optional. Max terms to return (0 = all). Pair with offset for paging.',
				),
				'offset'     => array(
					'type'        => 'integer',
					'description' => 'Optional. Number of terms to skip (for paging).',
				),
				'fields'     => array(
					'type'        => 'string',
					'description' => 'Output shape: "all" (default — full term objects) or "ids" (terms is a flat array of integer IDs).',
					'enum'        => array( 'all', 'ids' ),
					'default'     => 'all',
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
							'term_id'     => array( 'type' => 'integer' ),
							'name'        => array( 'type' => 'string' ),
							'slug'        => array( 'type' => 'string' ),
							'parent'      => array( 'type' => 'integer' ),
							'count'       => array( 'type' => 'integer' ),
							'description' => array( 'type' => 'string' ),
							'meta'        => array(
								'type'        => 'object',
								'description' => 'Non-ACF term meta (meta_key => value). Empty when none.',
							),
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

		$fields    = ( ( $args['fields'] ?? 'all' ) === 'ids' ) ? 'ids' : 'all';
		$with_meta = 'all' === $fields;

		$query_args = array(
			'taxonomy'               => $taxonomy,
			'hide_empty'             => ! empty( $args['hide_empty'] ),
			'update_term_meta_cache' => $with_meta,
			'orderby'                => $args['orderby'] ?? 'name',
			'order'                  => ( ( $args['order'] ?? 'ASC' ) === 'DESC' ) ? 'DESC' : 'ASC',
		);

		if ( ! empty( $args['search'] ) ) {
			$query_args['search'] = $args['search'];
		}
		if ( ! empty( $args['include'] ) && is_array( $args['include'] ) ) {
			$query_args['include'] = array_map( 'intval', $args['include'] );
		}
		if ( ! empty( $args['exclude'] ) && is_array( $args['exclude'] ) ) {
			$query_args['exclude'] = array_map( 'intval', $args['exclude'] );
		}
		if ( isset( $args['parent'] ) ) {
			$query_args['parent'] = (int) $args['parent'];
		}
		if ( ! empty( $args['number'] ) ) {
			$query_args['number'] = (int) $args['number'];
		}
		if ( ! empty( $args['offset'] ) ) {
			$query_args['offset'] = (int) $args['offset'];
		}

		if ( 'ids' === $fields ) {
			$query_args['fields'] = 'ids';
			$ids                  = get_terms( $query_args );
			if ( is_wp_error( $ids ) ) {
				return $ids;
			}
			$ids = array_map( 'intval', $ids );
			return array(
				'terms' => $ids,
				'hint'  => sprintf( '%1$d term id(s) in "%2$s" (fields=ids).', count( $ids ), $taxonomy ),
			);
		}

		$terms = get_terms( $query_args );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$result = array();
		foreach ( $terms as $term ) {
			$meta_raw = get_term_meta( $term->term_id );
			$meta     = array();
			if ( is_array( $meta_raw ) ) {
				foreach ( $meta_raw as $key => $values ) {
					$values = array_map( 'maybe_unserialize', (array) $values );
					// Collapse single-value keys to a scalar; keep the full list
					// when a key legitimately holds multiple values.
					$meta[ $key ] = count( $values ) > 1 ? $values : ( $values[0] ?? '' );
				}
			}

			$result[] = array(
				'term_id'     => (int) $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'parent'      => (int) $term->parent,
				'count'       => (int) $term->count,
				'description' => $term->description,
				'meta'        => $meta,
			);
		}

		return array(
			'terms' => $result,
			'hint'  => sprintf( '%1$d term(s) in "%2$s". "meta" is non-ACF term meta; for ACF term fields use xfive-acf-acf-field-get with object_id "term_{id}".', count( $result ), $taxonomy ),
		);
	}
}
