<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class StringList extends AbilitiesBase {
	/**
	 * Get configuration for the string list ability.
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
		return 'WPML - String List';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'List WPML String Translation entries with their translations: site title and tagline (domain "WP"), theme and plugin interface strings (their text domain), form labels and messages (e.g. "gravity_form-1"), widget and admin texts. Filter by domain and/or a search on the original text or name. Use the IDs with xfive-wpml-string-translate. A string missing here has not been registered or scanned yet (WPML -> Theme and plugins localization).';
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
				'domain'   => array(
					'type'        => 'string',
					'description' => 'Exact domain (WPML calls it context), e.g. "WP", "chisel", "gravity_form-1".',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Text contained in the original value or the string name.',
				),
				'page'     => array(
					'type'    => 'integer',
					'default' => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Results per page, 1-200. Defaults to 50.',
					'default'     => 50,
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
				'strings' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array( 'type' => 'integer' ),
							'domain'       => array( 'type' => 'string' ),
							'name'         => array( 'type' => 'string' ),
							'value'        => array( 'type' => 'string' ),
							'language'     => array( 'type' => 'string' ),
							'translations' => array(
								'type'        => 'object',
								'description' => 'Language code => {value, status}. Status 10 is complete.',
							),
						),
					),
				),
				'total'   => array( 'type' => 'integer' ),
				'domains' => array(
					'type'        => 'array',
					'description' => 'Every domain on this site with its string count, when no domain filter was given.',
				),
				'hint'    => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Execute the string listing.
	 *
	 * @param array $args Arguments with optional domain, search, page and per_page.
	 * @return array|\WP_Error Strings on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		global $wpdb;

		$unavailable = $this->wpml_unavailable();
		if ( $unavailable ) {
			return $unavailable;
		}

		if ( ! function_exists( 'icl_add_string_translation' ) ) {
			return new \WP_Error( 'wpml_st_inactive', 'WPML String Translation is not active on this site.' );
		}

		$domain   = sanitize_text_field( $args['domain'] ?? '' );
		$search   = sanitize_text_field( $args['search'] ?? '' );
		$per_page = min( 200, max( 1, (int) ( $args['per_page'] ?? 50 ) ) );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$where    = array( '1 = %d' );
		$params   = array( 1 );

		if ( '' !== $domain ) {
			$where[]  = 'context = %s';
			$params[] = $domain;
		}

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '( value LIKE %s OR name LIKE %s )';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$table     = $wpdb->prefix . 'icl_strings';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- WPML keeps strings in its own tables and has no listing API; the WHERE clause is built from placeholders only.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, context, name, value, language FROM {$table} WHERE {$where_sql} ORDER BY context, id LIMIT %d OFFSET %d",
				array_merge( $params, array( $per_page, ( $page - 1 ) * $per_page ) )
			)
		);

		$translations = array();

		if ( $rows ) {
			$ids   = implode( ',', array_map( 'intval', wp_list_pluck( $rows, 'id' ) ) );
			$found = $wpdb->get_results( "SELECT string_id, language, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id IN ({$ids})" );

			foreach ( $found as $row ) {
				$translations[ (int) $row->string_id ][ $row->language ] = array(
					'value'  => (string) $row->value,
					'status' => (int) $row->status,
				);
			}
		}

		$domains = array();

		if ( '' === $domain ) {
			$domains = $wpdb->get_results( "SELECT context AS domain, COUNT(*) AS strings FROM {$table} GROUP BY context ORDER BY context", ARRAY_A );
		}
		// phpcs:enable

		$strings = array();

		foreach ( (array) $rows as $row ) {
			$strings[] = array(
				'id'           => (int) $row->id,
				'domain'       => $row->context,
				'name'         => $row->name,
				'value'        => $row->value,
				'language'     => $row->language,
				'translations' => (object) ( $translations[ (int) $row->id ] ?? array() ),
			);
		}

		return array(
			'strings' => $strings,
			'total'   => $total,
			'domains' => $domains,
			'hint'    => sprintf( '%1$d of %2$d string(s), page %3$d. Translate with xfive-wpml-string-translate using the id.', count( $strings ), $total, $page ),
		);
	}
}
