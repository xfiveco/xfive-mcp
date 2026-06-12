<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TermDelete extends AbilitiesBase {
	/**
	 * Get configuration for the term delete ability.
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
		return 'Term - Delete';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Delete a taxonomy term by ID. Provide term_id + taxonomy (required). Removes the term and unassigns it from any posts; does not delete the posts.';
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
				'term_id'  => array(
					'type'        => 'integer',
					'description' => 'The ID of the term to delete.',
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'Taxonomy slug the term belongs to (e.g. "condition").',
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
				'deleted'    => array(
					'type'        => 'boolean',
					'description' => 'True when the term was deleted.',
				),
				'unassigned' => array(
					'type'        => 'integer',
					'description' => 'Number of objects (posts) that had this term assigned and were unassigned by the deletion.',
				),
				'hint'       => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the term deletion.
	 *
	 * @param array $args Arguments for deleting a term.
	 * @return array|\WP_Error Array with result on success, WP_Error on failure.
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

		// Capture how many objects are assigned before deletion so we can report
		// how many will be unassigned (the term's count).
		$term       = get_term( $term_id, $taxonomy );
		$unassigned = ( $term instanceof \WP_Term ) ? (int) $term->count : 0;

		// wp_delete_term validates the term itself: WP_Error on failure,
		// false/0 when the term does not exist in the taxonomy.
		$result = wp_delete_term( $term_id, $taxonomy );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( false === $result || 0 === $result ) {
			return new \WP_Error( 'invalid_term', sprintf( 'Term %1$d not found in "%2$s".', $term_id, $taxonomy ) );
		}

		return array(
			'deleted'    => true,
			'unassigned' => $unassigned,
			'hint'       => sprintf( 'Deleted term %1$d from "%2$s". Unassigned from %3$d object(s); the objects themselves were not deleted.', $term_id, $taxonomy, $unassigned ),
		);
	}
}
