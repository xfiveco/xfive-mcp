<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TermTranslate extends AbilitiesBase {
	/**
	 * Get configuration for the term translate ability.
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
		return 'WPML - Term Translate';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Translate taxonomy terms (categories, tags, WooCommerce attribute values) through WPML, several at once. Each translation is created linked to its original, the way WPML\'s Taxonomy Translation screen does it, or renamed when it already exists; posts in that language then carry the translated term. Omit translations to read a term\'s existing translations. copy_meta copies term meta keys from the original onto each translation, e.g. "order" for the order WooCommerce shows attribute values and categories in.';
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
				'taxonomy'  => array(
					'type'        => 'string',
					'description' => 'Taxonomy slug, e.g. "product_cat" or "pa_material".',
				),
				'terms'     => array(
					'type'        => 'array',
					'description' => 'Terms to translate.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'term_id'      => array(
								'type'        => 'integer',
								'description' => 'ID of the term, in any of its languages.',
							),
							'translations' => array(
								'type'        => 'object',
								'description' => 'Language code => {"name": "…", "slug": "…", "description": "…"}; slug and description optional. Omit to only read the term\'s translations.',
							),
						),
						'required'   => array( 'term_id' ),
					),
				),
				'copy_meta' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Term meta keys copied from the original onto each saved translation.',
				),
			),
			'required'   => array( 'taxonomy', 'terms' ),
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
							'term_id'            => array(
								'type'        => 'integer',
								'description' => 'The original term.',
							),
							'language'           => array( 'type' => 'string' ),
							'translated_term_id' => array( 'type' => 'integer' ),
							'created'            => array(
								'type'        => 'boolean',
								'description' => 'False when an existing translation was updated.',
							),
						),
					),
				),
				'terms'  => array(
					'type'        => 'array',
					'description' => 'Each term asked about, with its translation in every active language.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'original_term_id' => array( 'type' => 'integer' ),
							'translations'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'language'    => array( 'type' => 'string' ),
										'term_id'     => array( 'type' => array( 'integer', 'null' ) ),
										'name'        => array( 'type' => array( 'string', 'null' ) ),
										'slug'        => array( 'type' => array( 'string', 'null' ) ),
										'is_original' => array( 'type' => 'boolean' ),
									),
								),
							),
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
	 * Execute the term translations.
	 *
	 * @param array $args Arguments with taxonomy, terms and copy_meta.
	 * @return array|\WP_Error Result on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		global $sitepress;

		$unavailable = $this->wpml_unavailable();
		if ( $unavailable ) {
			return $unavailable;
		}

		$taxonomy = (string) ( $args['taxonomy'] ?? '' );

		$invalid = $this->validate_taxonomy( $taxonomy );
		if ( $invalid instanceof \WP_Error ) {
			return $invalid;
		}

		if ( ! $sitepress->is_translated_taxonomy( $taxonomy ) ) {
			return new \WP_Error( 'not_translatable', sprintf( 'Taxonomy "%s" is not set as translatable in WPML.', $taxonomy ) );
		}

		if ( ! class_exists( 'WPML_Terms_Translations' ) ) {
			return new \WP_Error( 'wpml_inactive', 'WPML\'s term translation code is not loaded on this site.' );
		}

		$items = isset( $args['terms'] ) && is_array( $args['terms'] ) ? $args['terms'] : array();

		if ( ! $items ) {
			return new \WP_Error( 'missing_param', 'terms is required.' );
		}

		$copy_meta    = array_filter( array_map( 'strval', (array) ( $args['copy_meta'] ?? array() ) ) );
		$element_type = 'tax_' . $taxonomy;
		$saved        = array();
		$terms        = array();
		$errors       = array();

		foreach ( $items as $index => $item ) {
			$term = get_term( (int) ( $item['term_id'] ?? 0 ), $taxonomy );

			if ( ! $term instanceof \WP_Term ) {
				$errors[] = sprintf( 'Item %1$d: term %2$d not found in "%3$s".', $index, (int) ( $item['term_id'] ?? 0 ), $taxonomy );
				continue;
			}

			$trid = (int) $sitepress->get_element_trid( $term->term_taxonomy_id, $element_type );

			if ( ! $trid ) {
				$errors[] = sprintf( 'Term %d has no WPML language record yet.', $term->term_id );
				continue;
			}

			$original = $this->original_term( $trid, $element_type, $taxonomy ) ?? $term;
			$source   = (string) $sitepress->get_language_for_element( $original->term_taxonomy_id, $element_type );

			foreach ( (array) ( $item['translations'] ?? array() ) as $language => $fields ) {
				$language = (string) $language;
				$fields   = is_array( $fields ) ? $fields : array( 'name' => (string) $fields );
				$name     = trim( (string) ( $fields['name'] ?? '' ) );

				$invalid_language = $this->validate_wpml_language( $language );
				if ( $invalid_language ) {
					$errors[] = sprintf( 'Term %1$d: %2$s', $original->term_id, $invalid_language->get_error_message() );
					continue;
				}

				if ( $language === $source ) {
					$errors[] = sprintf( 'Term %1$d: "%2$s" is its original language; edit it with xfive-terms-term-update.', $original->term_id, $language );
					continue;
				}

				if ( '' === $name ) {
					$errors[] = sprintf( 'Term %1$d: the "%2$s" translation needs a name.', $original->term_id, $language );
					continue;
				}

				$existing = $sitepress->get_element_translations( $trid, $element_type, false, false, true );

				// WPML's own path: creates the term in that language and links it by trid, or
				// updates the translation that is already there.
				$result = \WPML_Terms_Translations::create_new_term(
					array(
						'term'            => $name,
						'taxonomy'        => $taxonomy,
						'lang_code'       => $language,
						'trid'            => $trid,
						'source_language' => $source,
						'slug'            => (string) ( $fields['slug'] ?? '' ),
						'description'     => $fields['description'] ?? false,
					)
				);

				if ( ! is_array( $result ) || empty( $result['term_id'] ) ) {
					$errors[] = sprintf(
						'Term %1$d: the "%2$s" translation was not saved%3$s.',
						$original->term_id,
						$language,
						is_wp_error( $result ) ? ': ' . $result->get_error_message() : ''
					);
					continue;
				}

				foreach ( $copy_meta as $key ) {
					$value = get_term_meta( $original->term_id, $key, true );

					if ( '' !== $value ) {
						update_term_meta( (int) $result['term_id'], $key, $value );
					}
				}

				$saved[] = array(
					'term_id'            => $original->term_id,
					'language'           => $language,
					'translated_term_id' => (int) $result['term_id'],
					'created'            => ! isset( $existing[ $language ] ),
				);
			}

			$terms[] = $this->describe_translations( $trid, $element_type, $taxonomy, $original );
		}

		return array(
			'saved'  => $saved,
			'terms'  => $terms,
			'errors' => $errors,
			'hint'   => $errors ? 'Some translations were not saved; see errors.' : 'Translate terms before the posts that use them, so each translated post picks up the translated terms.',
		);
	}

	/**
	 * The original term of a translation group.
	 *
	 * @param int    $trid         WPML translation group ID.
	 * @param string $element_type WPML element type, "tax_{taxonomy}".
	 * @param string $taxonomy     Taxonomy slug.
	 * @return \WP_Term|null The original term, or null when WPML has none marked.
	 */
	private function original_term( int $trid, string $element_type, string $taxonomy ): ?\WP_Term {
		global $sitepress;

		foreach ( $sitepress->get_element_translations( $trid, $element_type, false, false, true ) as $row ) {
			if ( ! empty( $row->original ) ) {
				$term = get_term_by( 'term_taxonomy_id', (int) $row->element_id, $taxonomy );

				return $term instanceof \WP_Term ? $term : null;
			}
		}

		return null;
	}

	/**
	 * A term's translation in every active language.
	 *
	 * @param int      $trid         WPML translation group ID.
	 * @param string   $element_type WPML element type, "tax_{taxonomy}".
	 * @param string   $taxonomy     Taxonomy slug.
	 * @param \WP_Term $original     The original term.
	 * @return array Original term ID and one row per active language.
	 */
	private function describe_translations( int $trid, string $element_type, string $taxonomy, \WP_Term $original ): array {
		global $sitepress;

		$existing     = $sitepress->get_element_translations( $trid, $element_type, false, false, true );
		$translations = array();

		foreach ( array_keys( $sitepress->get_active_languages() ) as $code ) {
			$row  = $existing[ $code ] ?? null;
			$term = $row ? get_term_by( 'term_taxonomy_id', (int) $row->element_id, $taxonomy ) : null;
			$term = $term instanceof \WP_Term ? $term : null;

			$translations[] = array(
				'language'    => $code,
				'term_id'     => $term ? $term->term_id : null,
				'name'        => $term ? $term->name : null,
				'slug'        => $term ? $term->slug : null,
				'is_original' => $term && $term->term_id === $original->term_id,
			);
		}

		return array(
			'original_term_id' => $original->term_id,
			'translations'     => $translations,
		);
	}
}
