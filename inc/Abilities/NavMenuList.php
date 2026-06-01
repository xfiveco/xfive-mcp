<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class NavMenuList extends AbilitiesBase {
	/**
	 * Get configuration for the nav menu list ability.
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
		return 'Nav Menu - List';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'List all navigation menus with their items. Returns each menu (term ID, name, slug) and the theme locations it is assigned to, plus the ordered list of items with title, url, type, object, object_id, parent_id and css classes. Pass menu (term ID, name or slug) to return only that menu. Read counterpart to xfive-menus-nav-menu-create.';
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
				'menu' => array(
					'type'        => array( 'string', 'integer' ),
					'description' => 'Optional. A single menu to return, identified by term ID, name or slug. Omit to list all menus.',
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
				'menus' => array(
					'type'        => 'array',
					'description' => 'List of navigation menus.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'menu_id'   => array( 'type' => 'integer' ),
							'name'      => array( 'type' => 'string' ),
							'slug'      => array( 'type' => 'string' ),
							'count'     => array( 'type' => 'integer' ),
							'locations' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'items'     => array(
								'type'  => 'array',
								'items' => array(
									'type'       => 'object',
									'properties' => array(
										'item_id'   => array( 'type' => 'integer' ),
										'title'     => array( 'type' => 'string' ),
										'url'       => array( 'type' => 'string' ),
										'type'      => array( 'type' => 'string' ),
										'object'    => array( 'type' => 'string' ),
										'object_id' => array( 'type' => 'integer' ),
										'parent_id' => array( 'type' => 'integer' ),
										'menu_order'=> array( 'type' => 'integer' ),
										'target'    => array( 'type' => 'string' ),
										'classes'   => array( 'type' => 'string' ),
									),
								),
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
	 * Execute the nav menu listing.
	 *
	 * @param array $args Arguments for listing nav menus.
	 * @return array|\WP_Error Array with menus on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$requested = $args['menu'] ?? '';

		if ( '' !== $requested && null !== $requested ) {
			$menu_object = wp_get_nav_menu_object( $requested );
			if ( ! $menu_object ) {
				return new \WP_Error( 'not_found', sprintf( 'No menu found matching "%s".', is_scalar( $requested ) ? $requested : '' ) );
			}
			$menu_objects = array( $menu_object );
		} else {
			$menu_objects = wp_get_nav_menus();
		}

		// Build a reverse map of menu_id => array of theme locations.
		$location_map  = array();
		$nav_locations = get_nav_menu_locations();
		foreach ( $nav_locations as $location => $menu_id ) {
			$location_map[ (int) $menu_id ][] = $location;
		}

		$menus = array();
		foreach ( $menu_objects as $menu_object ) {
			$menu_id    = (int) $menu_object->term_id;
			$menu_items = wp_get_nav_menu_items( $menu_id ) ?: array();

			$items = array();
			foreach ( $menu_items as $menu_item ) {
				$items[] = array(
					'item_id'    => (int) $menu_item->ID,
					'title'      => $menu_item->title,
					'url'        => $menu_item->url,
					'type'       => $menu_item->type,
					'object'     => $menu_item->object,
					'object_id'  => (int) $menu_item->object_id,
					'parent_id'  => (int) $menu_item->menu_item_parent,
					'menu_order' => (int) $menu_item->menu_order,
					'target'     => $menu_item->target,
					'classes'    => is_array( $menu_item->classes ) ? trim( implode( ' ', $menu_item->classes ) ) : (string) $menu_item->classes,
				);
			}

			$menus[] = array(
				'menu_id'   => $menu_id,
				'name'      => $menu_object->name,
				'slug'      => $menu_object->slug,
				'count'     => count( $items ),
				'locations' => $location_map[ $menu_id ] ?? array(),
				'items'     => $items,
			);
		}

		return array(
			'menus' => $menus,
			'hint'  => sprintf( '%d menu(s) returned. parent_id 0 means top-level. Recreate on another site with xfive-menus-nav-menu-create (note: object_id values are local to THIS site).', count( $menus ) ),
		);
	}
}
