<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TranslationJobGet extends AbilitiesBase {
	/**
	 * Get configuration for the translation job get ability.
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
		return 'WPML - Translation Job Get';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Open WPML\'s translation job for a post and a target language, creating it when none is open, and return every field WPML wants translated with its source text (title, slug, each block\'s text, image alt texts, translatable custom fields). Translate each field and send the results to xfive-wpml-translation-job-save; WPML then builds and links the translated post itself, keeping the block markup. Pass the original post, not a translation. Works for pages, posts, wp_navigation and wp_template_part.';
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
				'post_id'  => array(
					'type'        => 'integer',
					'description' => 'ID of the original post.',
				),
				'language' => array(
					'type'        => 'string',
					'description' => 'Target language code as WPML names it, e.g. "de", "zh-cn", "pt-br".',
				),
				'refresh'  => array(
					'type'        => 'boolean',
					'description' => 'Create a new job even when one is open, so the fields follow the current original (after the original or the WPML config changed).',
					'default'     => false,
				),
			),
			'required'   => array( 'post_id', 'language' ),
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
				'job_id'          => array( 'type' => 'integer' ),
				'post_id'         => array( 'type' => 'integer' ),
				'source_language' => array( 'type' => 'string' ),
				'language'        => array( 'type' => 'string' ),
				'editor'          => array(
					'type'        => array( 'string', 'null' ),
					'description' => 'Which WPML editor the job belongs to (ate = Advanced Translation Editor, wpml = Classic).',
				),
				'created'         => array(
					'type'        => 'boolean',
					'description' => 'True when this call created the job, false when an open one was reused.',
				),
				'fields'          => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'field'       => array( 'type' => 'string' ),
							'source'      => array( 'type' => 'string' ),
							'translation' => array( 'type' => 'string' ),
							'finished'    => array( 'type' => 'boolean' ),
						),
					),
				),
				'hint'            => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Execute the translation job lookup.
	 *
	 * @param array $args Arguments with post_id and language.
	 * @return array|\WP_Error Job and fields on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		global $sitepress;

		$unavailable = $this->wpml_unavailable();
		if ( $unavailable ) {
			return $unavailable;
		}

		$tm = wpml_load_core_tm();

		$language = sanitize_text_field( $args['language'] ?? '' );
		$invalid  = $this->validate_wpml_language( $language );
		if ( $invalid ) {
			return $invalid;
		}

		$post_id = absint( $args['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		if ( ! $sitepress->is_translated_post_type( $post->post_type ) ) {
			return new \WP_Error( 'not_translatable', sprintf( 'Post type "%s" is not set as translatable in WPML.', $post->post_type ) );
		}

		$element_type = 'post_' . $post->post_type;
		$details      = $sitepress->get_element_language_details( $post_id, $element_type );

		if ( ! $details || empty( $details->trid ) ) {
			return new \WP_Error( 'no_language', sprintf( 'Post %d has no WPML language record yet.', $post_id ) );
		}

		if ( ! empty( $details->source_language_code ) ) {
			return new \WP_Error( 'not_original', sprintf( 'Post %d is itself a translation. Pass the original post; xfive-wpml-translation-status shows its ID.', $post_id ) );
		}

		if ( $details->language_code === $language ) {
			return new \WP_Error( 'same_language', sprintf( 'Post %1$d is already in "%2$s".', $post_id, $language ) );
		}

		$factory = wpml_tm_load_job_factory();
		$job_id  = (int) $tm->get_translation_job_id( (int) $details->trid, $language );
		$job     = $job_id ? $factory->get_translation_job( $job_id ) : null;
		$created = false;

		if ( ! $job || ! empty( $job->translated ) || ! empty( $args['refresh'] ) ) {
			$job_id  = (int) $factory->create_local_post_job( $post_id, $language, get_current_user_id() );
			$job     = $job_id ? $factory->get_translation_job( $job_id ) : null;
			$created = true;
		}

		if ( ! $job ) {
			return new \WP_Error( 'job_not_created', sprintf( 'WPML did not create a translation job for post %1$d into "%2$s".', $post_id, $language ) );
		}

		$fields = array();

		foreach ( (array) $job->elements as $element ) {
			$fields[] = array(
				'field'       => $element->field_type,
				'source'      => $this->decode_field( (string) $element->field_data, (string) $element->field_format ),
				'translation' => $this->decode_field( (string) $element->field_data_translated, (string) $element->field_format ),
				'finished'    => (bool) $element->field_finished,
			);
		}

		return array(
			'job_id'          => $job_id,
			'post_id'         => $post_id,
			'source_language' => $details->language_code,
			'language'        => $language,
			'editor'          => $job->editor ?? null,
			'created'         => $created,
			'fields'          => $fields,
			'hint'            => sprintf( 'Translate every field\'s source into "%1$s" and pass them to xfive-wpml-translation-job-save as {"field": "translated text"} with job_id %2$d. Keep HTML tags, placeholders and URLs as they are; translate only the words.', $language, $job_id ),
		);
	}

	/**
	 * Decode a job field the way WPML stores it.
	 *
	 * @param string $data   Stored field value, already decompressed by the job factory.
	 * @param string $format WPML field format: base64, csv_base64 or empty.
	 * @return string Plain text.
	 */
	private function decode_field( string $data, string $format ): string {
		if ( '' === $data ) {
			return '';
		}

		if ( 'base64' === $format ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- WPML stores job fields base64-encoded.
			return (string) base64_decode( $data );
		}

		if ( 'csv_base64' === $format ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- WPML stores list fields as comma-separated base64 values.
			return implode( ', ', array_map( 'base64_decode', explode( ',', $data ) ) );
		}

		return $data;
	}
}
