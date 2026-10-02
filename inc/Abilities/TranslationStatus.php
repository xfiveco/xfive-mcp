<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TranslationStatus extends AbilitiesBase {
	/**
	 * WPML translation status codes and what they mean.
	 *
	 * @var string[]
	 */
	private const STATUS_LABELS = array(
		0  => 'not translated',
		1  => 'waiting for translator',
		2  => 'in progress',
		3  => 'needs update',
		9  => 'duplicate',
		10 => 'complete',
	);

	/**
	 * Get configuration for the translation status ability.
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
		return 'WPML - Translation Status';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Show a post\'s WPML language and, for every language active on this site, its translation: post ID, status, whether it needs an update, and the translation job. Works for any translatable post type, including wp_navigation and wp_template_part. Read this before xfive-wpml-translation-job-get to see what exists.';
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the post, in any of its languages.',
				),
			),
			'required'   => array( 'post_id' ),
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
				'post_id'          => array( 'type' => 'integer' ),
				'language'         => array(
					'type'        => 'string',
					'description' => 'Language of the post asked about.',
				),
				'original_post_id' => array(
					'type'        => 'integer',
					'description' => 'The post the translations are made from.',
				),
				'default_language' => array( 'type' => 'string' ),
				'translations'     => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'language'     => array( 'type' => 'string' ),
							'name'         => array( 'type' => 'string' ),
							'post_id'      => array( 'type' => array( 'integer', 'null' ) ),
							'is_original'  => array( 'type' => 'boolean' ),
							'status'       => array( 'type' => 'string' ),
							'needs_update' => array( 'type' => 'boolean' ),
							'job_id'       => array( 'type' => array( 'integer', 'null' ) ),
							'editor'       => array( 'type' => array( 'string', 'null' ) ),
							'url'          => array( 'type' => array( 'string', 'null' ) ),
						),
					),
				),
				'hint'             => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Execute the translation status lookup.
	 *
	 * @param array $args Arguments with post_id.
	 * @return array|\WP_Error Status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		global $sitepress;

		$unavailable = $this->wpml_unavailable();
		if ( $unavailable ) {
			return $unavailable;
		}

		$tm = wpml_load_core_tm();

		$post_id = absint( $args['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		if ( ! $sitepress->is_translated_post_type( $post->post_type ) ) {
			return new \WP_Error( 'not_translatable', sprintf( 'Post type "%s" is not set as translatable in WPML.', $post->post_type ) );
		}

		$element_type = 'post_' . $post->post_type;
		$trid         = (int) $sitepress->get_element_trid( $post_id, $element_type );

		if ( ! $trid ) {
			return new \WP_Error( 'no_language', sprintf( 'Post %d has no WPML language record yet.', $post_id ) );
		}

		$existing    = $sitepress->get_element_translations( $trid, $element_type );
		$original_id = $post_id;
		$language    = '';

		foreach ( $existing as $code => $row ) {
			if ( ! empty( $row->original ) ) {
				$original_id = (int) $row->element_id;
			}
			if ( (int) $row->element_id === $post_id ) {
				$language = $code;
			}
		}

		$translations = array();

		foreach ( $sitepress->get_active_languages() as $code => $details ) {
			$row         = $existing[ $code ] ?? null;
			$is_original = $row && ! empty( $row->original );
			$status_row  = $row && ! $is_original ? $tm->get_element_translation( $original_id, $code, $element_type ) : null;
			$status_code = $status_row ? (int) $status_row->status : ( $row ? 10 : 0 );
			$job_id      = $is_original ? null : $tm->get_translation_job_id( $trid, $code );
			$element_id  = $row && $row->element_id ? (int) $row->element_id : null;

			$translations[] = array(
				'language'     => $code,
				'name'         => $details['english_name'] ?? $code,
				'post_id'      => $element_id,
				'is_original'  => $is_original,
				'status'       => $is_original ? 'original' : ( self::STATUS_LABELS[ $status_code ] ?? (string) $status_code ),
				'needs_update' => $status_row ? (bool) $status_row->needs_update : false,
				'job_id'       => $job_id ? (int) $job_id : null,
				'editor'       => $job_id ? $tm->get_translation_job_editor( $trid, $code ) : null,
				'url'          => $element_id && is_post_type_viewable( $post->post_type ) ? get_permalink( $element_id ) : null,
			);
		}

		return array(
			'post_id'          => $post_id,
			'language'         => $language,
			'original_post_id' => $original_id,
			'default_language' => $sitepress->get_default_language(),
			'translations'     => $translations,
			'hint'             => 'To translate into a language, call xfive-wpml-translation-job-get with the original_post_id and the language code, then xfive-wpml-translation-job-save.',
		);
	}
}
