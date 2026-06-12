<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AcfFieldSchema extends AbilitiesBase {
	/**
	 * Get configuration for the ACF field schema ability.
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
		return 'ACF - Field Schema';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Read the STRUCTURE (not values) of ACF field groups that apply to a target: field names, keys, types, and nested sub-fields for repeater/group/flexible_content (and choices for select/radio/checkbox). Pass object_id ("option", a numeric post ID, "term_{id}", or "user_{id}") to resolve groups by that object\'s location rules, OR post_type / taxonomy to resolve by type. Use this BEFORE writing complex fields with xfive-acf-acf-field-update so you know the exact shape (e.g. a repeater\'s sub-field names). For VALUES use xfive-acf-acf-field-get.';
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
				'object_id' => array(
					'type'        => array( 'string', 'integer' ),
					'description' => 'Resolve field groups by this object\'s location rules. "option" for an options page, a numeric post ID, "term_{id}", or "user_{id}". Mutually exclusive with post_type/taxonomy.',
				),
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Resolve field groups assigned to this post type (e.g. "industry").',
				),
				'taxonomy'  => array(
					'type'        => 'string',
					'description' => 'Resolve field groups assigned to this taxonomy (e.g. "condition").',
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
				'groups' => array(
					'type'        => 'array',
					'description' => 'Matching field groups. Each has key, title, and fields (recursive: each field has name, key, type, label, required, and sub_fields/layouts for repeater/group/flexible_content, choices for select-like types).',
					'items'       => array( 'type' => 'object' ),
				),
				'hint'   => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the ACF field schema read.
	 *
	 * @param array $args Arguments.
	 * @return array|\WP_Error Array with groups on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return new \WP_Error( 'acf_missing', 'Advanced Custom Fields plugin is not active.' );
		}

		$filter = $this->build_filter( $args );
		if ( $filter instanceof \WP_Error ) {
			return $filter;
		}

		$field_groups = acf_get_field_groups( $filter );

		$groups = array();
		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group['key'] );
			$groups[] = array(
				'key'    => $group['key'] ?? '',
				'title'  => $group['title'] ?? '',
				'fields' => $this->map_fields( is_array( $fields ) ? $fields : array() ),
			);
		}

		return array(
			'groups' => $groups,
			'hint'   => sprintf( '%d field group(s). Use field "name" (not key) when writing with xfive-acf-acf-field-update; for repeaters/flex pass arrays matching sub_fields/layouts.', count( $groups ) ),
		);
	}

	/**
	 * Build the acf_get_field_groups() location filter from the args.
	 *
	 * @param array $args Ability arguments.
	 * @return array|\WP_Error Filter array or error.
	 */
	private function build_filter( array $args ) {
		if ( ! empty( $args['post_type'] ) ) {
			if ( ! post_type_exists( $args['post_type'] ) ) {
				return new \WP_Error( 'invalid_post_type', sprintf( 'Post type "%s" is not registered.', $args['post_type'] ) );
			}
			return array( 'post_type' => $args['post_type'] );
		}

		if ( ! empty( $args['taxonomy'] ) ) {
			if ( ! taxonomy_exists( $args['taxonomy'] ) ) {
				return new \WP_Error( 'invalid_taxonomy', sprintf( 'Taxonomy "%s" is not registered.', $args['taxonomy'] ) );
			}
			return array( 'taxonomy' => $args['taxonomy'] );
		}

		if ( isset( $args['object_id'] ) ) {
			$object_id = $args['object_id'];

			if ( 'option' === $object_id || 'options' === $object_id ) {
				return array( 'options_page' => 'acf-options' );
			}
			if ( is_string( $object_id ) && preg_match( '/^term_(\d+)$/', $object_id, $m ) ) {
				$term = get_term( (int) $m[1] );
				if ( ! $term instanceof \WP_Term ) {
					return new \WP_Error( 'not_found', sprintf( 'Term ID %d not found.', (int) $m[1] ) );
				}
				return array( 'taxonomy' => $term->taxonomy );
			}
			if ( is_string( $object_id ) && preg_match( '/^user_(\d+)$/', $object_id, $m ) ) {
				if ( ! get_userdata( (int) $m[1] ) ) {
					return new \WP_Error( 'not_found', sprintf( 'User ID %d not found.', (int) $m[1] ) );
				}
				return array( 'user_id' => (int) $m[1] );
			}

			$post_id = (int) $object_id;
			if ( ! get_post( $post_id ) ) {
				return new \WP_Error( 'not_found', sprintf( 'Post ID %d not found.', $post_id ) );
			}
			return array( 'post_id' => $post_id );
		}

		// No target: return all field groups.
		return array();
	}

	/**
	 * Map ACF field definitions to a compact recursive structure.
	 *
	 * @param array $fields ACF field definition array.
	 * @return array Mapped fields.
	 */
	private function map_fields( array $fields ): array {
		$out = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$type  = $field['type'] ?? '';
			$entry = array(
				'name'     => $field['name'] ?? '',
				'key'      => $field['key'] ?? '',
				'type'     => $type,
				'label'    => $field['label'] ?? '',
				'required' => ! empty( $field['required'] ),
			);

			if ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
				$entry['choices'] = $field['choices'];
			}

			// Recurse into containers.
			if ( in_array( $type, array( 'repeater', 'group' ), true ) && ! empty( $field['sub_fields'] ) ) {
				$entry['sub_fields'] = $this->map_fields( $field['sub_fields'] );
			}

			if ( 'flexible_content' === $type && ! empty( $field['layouts'] ) ) {
				$layouts = array();
				foreach ( $field['layouts'] as $layout ) {
					$layouts[] = array(
						'name'       => $layout['name'] ?? '',
						'label'      => $layout['label'] ?? '',
						'sub_fields' => $this->map_fields( $layout['sub_fields'] ?? array() ),
					);
				}
				$entry['layouts'] = $layouts;
			}

			$out[] = $entry;
		}

		return $out;
	}
}
