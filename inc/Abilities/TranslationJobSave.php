<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TranslationJobSave extends AbilitiesBase {
	/**
	 * Get configuration for the translation job save ability.
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
		return 'WPML - Translation Job Save';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Save translated fields into a WPML translation job from xfive-wpml-translation-job-get. With complete=true (default) every field must be translated; WPML then creates or updates the translated post, links it to the original and rebuilds its blocks - the same path WPML\'s own editors and XLIFF import use. With complete=false the fields are stored and the job stays open. Returns the translated post ID and URL.';
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
				'job_id'   => array(
					'type'        => 'integer',
					'description' => 'Job ID returned by xfive-wpml-translation-job-get.',
				),
				'fields'   => array(
					'type'        => 'object',
					'description' => 'Object of field name => translated text, using the field names from xfive-wpml-translation-job-get. Fields left out keep the translation already stored in the job, including one WPML pre-filled from the previous version.',
				),
				'complete' => array(
					'type'        => 'boolean',
					'description' => 'Finish the job and publish the translation. Defaults to true.',
					'default'     => true,
				),
			),
			'required'   => array( 'job_id', 'fields' ),
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
				'job_id'   => array( 'type' => 'integer' ),
				'complete' => array( 'type' => 'boolean' ),
				'post_id'  => array(
					'type'        => array( 'integer', 'null' ),
					'description' => 'The translated post, once the job is complete.',
				),
				'url'      => array( 'type' => array( 'string', 'null' ) ),
				'hint'     => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Execute the translation save.
	 *
	 * @param array $args Arguments with job_id, fields and complete.
	 * @return array|\WP_Error Result on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		global $sitepress;

		$unavailable = $this->wpml_unavailable();
		if ( $unavailable ) {
			return $unavailable;
		}

		$tm = wpml_load_core_tm();

		$job_id       = absint( $args['job_id'] ?? 0 );
		$translations = isset( $args['fields'] ) && is_array( $args['fields'] ) ? $args['fields'] : array();
		$complete     = ! isset( $args['complete'] ) || (bool) $args['complete'];
		$factory      = wpml_tm_load_job_factory();
		$job          = $job_id ? $factory->get_translation_job( $job_id ) : null;

		if ( ! $job ) {
			return new \WP_Error( 'job_not_found', sprintf( 'Translation job %d not found.', $job_id ) );
		}

		$known   = array();
		$fields  = array();
		$missing = array();

		// A new job version arrives pre-filled from the previous one, unfinished; that text counts.
		foreach ( (array) $job->elements as $element ) {
			$type            = $element->field_type;
			$known[]         = $type;
			$has_new         = array_key_exists( $type, $translations );
			$has_existing    = ! $has_new && '' !== (string) $element->field_data_translated;
			$fields[ $type ] = array(
				'tid'        => (int) $element->tid,
				'field_type' => $type,
				'format'     => $element->field_format,
				'data'       => $has_new ? (string) $translations[ $type ] : $this->existing_translation( $element ),
				'finished'   => $has_new || $has_existing ? 1 : 0,
			);

			if ( ! $has_new && ! $has_existing ) {
				$missing[] = $type;
			}
		}

		$unknown = array_diff( array_keys( $translations ), $known );

		if ( $unknown ) {
			return new \WP_Error( 'unknown_fields', sprintf( 'Job %1$d has no field(s): %2$s. Use the field names from xfive-wpml-translation-job-get.', $job_id, implode( ', ', $unknown ) ) );
		}

		if ( $complete && $missing ) {
			return new \WP_Error( 'missing_fields', sprintf( 'Job %1$d cannot be completed; untranslated field(s): %2$s. Translate them or pass complete=false.', $job_id, implode( ', ', $missing ) ) );
		}

		// Block markup must reach WPML unfiltered, as it does from WPML's own editors.
		kses_remove_filters();
		$saved = wpml_tm_save_data(
			array(
				'job_id'   => $job_id,
				'fields'   => $fields,
				'complete' => $complete ? 1 : 0,
			),
			false
		);
		kses_init();

		if ( ! $saved ) {
			$errors = wp_list_pluck( $tm->messages_by_type( 'error' ), 'text' );

			return new \WP_Error( 'save_failed', sprintf( 'WPML did not save job %1$d. %2$s', $job_id, implode( ' ', array_map( 'wp_strip_all_tags', $errors ) ) ) );
		}

		$post_id = null;

		if ( $complete ) {
			$original = get_post( (int) $job->original_doc_id );
			$post_id  = $original ? (int) apply_filters( 'wpml_object_id', $original->ID, $original->post_type, false, $job->language_code ) : 0;
			$post_id  = $post_id && $post_id !== (int) $job->original_doc_id ? $post_id : null;
		}

		return array(
			'job_id'   => $job_id,
			'complete' => $complete,
			'post_id'  => $post_id,
			'url'      => $post_id && is_post_type_viewable( get_post_type( $post_id ) ) ? get_permalink( $post_id ) : null,
			'hint'     => $complete
				? ( $post_id ? 'Translation published and linked. Check it with xfive-wpml-translation-status.' : 'Job saved as complete, but no translated post was found; check xfive-wpml-translation-status.' )
				: 'Fields stored; the job stays open until saved with complete=true.',
		);
	}

	/**
	 * Return a field's stored translation as plain text for re-saving.
	 *
	 * @param object $element Job element from the WPML job factory.
	 * @return string Plain translated text, or empty.
	 */
	private function existing_translation( object $element ): string {
		$data = (string) $element->field_data_translated;

		if ( '' === $data || 'base64' !== $element->field_format ) {
			return $data;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- WPML stores job fields base64-encoded.
		return (string) base64_decode( $data );
	}
}
