<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class NavMenuCreate extends AbilitiesBase {
	/**
	 * Get configuration for the nav menu create ability.
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
		return 'Nav Menu - Create';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Create a navigation menu, optionally assign it to a theme location, and add menu items. Provide menu_name (required), location (optional, e.g. "chisel_main_nav"), and items (optional array of menu items).';
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
				'menu_name' => array(
					'type'        => 'string',
					'description' => 'The name of the menu to create.',
				),
				'location'  => array(
					'type'        => 'string',
					'description' => 'Theme menu location to assign (e.g. "chisel_main_nav", "chisel_footer_nav"). Optional.',
				),
				'items'     => array(
					'type'        => 'array',
					'description' => 'Array of menu items to add. Each item is an object with: title (required), url (for custom links), object_id (for posts/pages/CPTs), object (post type, e.g. "page"), type ("custom", "post_type", "taxonomy"), parent_index (0-based index of parent item in this array for nesting), classes (CSS classes string), target ("_blank" for new tab).',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'title'        => array(
								'type'        => 'string',
								'description' => 'Menu item label.',
							),
							'url'          => array(
								'type'        => 'string',
								'description' => 'URL for custom link items.',
							),
							'object_id'    => array(
								'type'        => 'integer',
								'description' => 'Post/page/CPT ID for post_type items, or term ID for taxonomy items.',
							),
							'object'       => array(
								'type'        => 'string',
								'description' => 'Object type: "page", "post", CPT slug, or taxonomy slug.',
							),
							'type'         => array(
								'type'        => 'string',
								'description' => 'Item type: "custom", "post_type", or "taxonomy". Defaults to "custom".',
								'default'     => 'custom',
							),
							'parent_index' => array(
								'type'        => 'integer',
								'description' => '0-based index of the parent item in this items array (for creating submenus).',
							),
							'classes'      => array(
								'type'        => 'string',
								'description' => 'Space-separated CSS classes for this menu item.',
							),
							'target'       => array(
								'type'        => 'string',
								'description' => 'Link target, e.g. "_blank" for new tab.',
							),
						),
						'required'   => array( 'title' ),
					),
				),
			),
			'required'   => array( 'menu_name' ),
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
				'menu_id'  => array(
					'type'        => 'integer',
					'description' => 'The term ID of the created menu.',
				),
				'item_ids' => array(
					'type'        => 'array',
					'description' => 'Array of created menu item IDs (in the order they were added).',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'location' => array(
					'type'        => array( 'string', 'null' ),
					'description' => 'The theme location the menu was assigned to (if any). Null when no location was assigned.',
				),
				'hint'     => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the nav menu creation.
	 *
	 * @param array $args Arguments for creating a nav menu.
	 * @return array|\WP_Error Array with menu data on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$menu_name = $args['menu_name'] ?? '';
		$location  = $args['location'] ?? '';
		$items     = $args['items'] ?? array();

		if ( empty( $menu_name ) ) {
			return new \WP_Error( 'missing_param', 'menu_name is required.' );
		}

		// Check if menu already exists.
		$existing = wp_get_nav_menu_object( $menu_name );
		if ( $existing ) {
			$menu_id = $existing->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $menu_name );
			if ( is_wp_error( $menu_id ) ) {
				return $menu_id;
			}
		}

		// Assign to theme location if provided.
		if ( ! empty( $location ) ) {
			$locations              = get_theme_mod( 'nav_menu_locations', array() );
			$locations[ $location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}

		// Add menu items.
		$item_ids = array();
		foreach ( $items as $index => $item ) {
			$item_data = array(
				'menu-item-title'  => $item['title'] ?? '',
				'menu-item-status' => 'publish',
			);

			// Determine item type.
			$type = $item['type'] ?? 'custom';

			if ( 'post_type' === $type ) {
				$item_data['menu-item-type']      = 'post_type';
				$item_data['menu-item-object']    = $item['object'] ?? 'page';
				$item_data['menu-item-object-id'] = $item['object_id'] ?? 0;
			} elseif ( 'taxonomy' === $type ) {
				$item_data['menu-item-type']      = 'taxonomy';
				$item_data['menu-item-object']    = $item['object'] ?? 'category';
				$item_data['menu-item-object-id'] = $item['object_id'] ?? 0;
			} else {
				$item_data['menu-item-type'] = 'custom';
				$item_data['menu-item-url']  = $item['url'] ?? '#';
			}

			// Handle parent (nesting).
			if ( isset( $item['parent_index'] ) && isset( $item_ids[ $item['parent_index'] ] ) ) {
				$item_data['menu-item-parent-id'] = $item_ids[ $item['parent_index'] ];
			}

			// Optional fields.
			if ( ! empty( $item['classes'] ) ) {
				$item_data['menu-item-classes'] = $item['classes'];
			}
			if ( ! empty( $item['target'] ) ) {
				$item_data['menu-item-target'] = $item['target'];
			}

			$item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );

			if ( is_wp_error( $item_id ) ) {
				return new \WP_Error(
					'item_failed',
					sprintf( 'Failed to add menu item "%s": %s', $item['title'] ?? '', $item_id->get_error_message() )
				);
			}

			$item_ids[] = $item_id;
		}

		$assigned_location = ! empty( $location ) ? $location : null;
		$hint              = sprintf( 'Menu "%1$s" ready (%2$d items).', $menu_name, count( $item_ids ) );

		if ( $assigned_location === null ) {
			$hint .= ' No theme location assigned — pass `location` (e.g. "chisel_main_nav") to wire it up.';
		}

		return array(
			'menu_id'  => $menu_id,
			'item_ids' => $item_ids,
			'location' => $assigned_location,
			'hint'     => $hint,
		);
	}
}
