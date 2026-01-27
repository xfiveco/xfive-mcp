<?php

namespace XfiveMCP\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use XfiveMCP\Trait\Singleton;
use XfiveMCP\Trait\Config;

class Abilities {

	use Singleton;
	use Config;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_ability_categories' ) );
	}

	/**
	 * Register ability categories.
	 */
	public function register_ability_categories() {
		foreach ( $this->get_tools() as $tool_category => $tools ) {
			wp_register_ability_category(
				$tool_category,
				array(
					'label'       => 'xfive mcp - blocks',
					'description' => 'xfive mcp server',
				)
			);
		}
	}

	/**
	 * Register abilities.
	 */
	public function register_abilities() {
		foreach ( $this->get_tools() as $tool_category => $tools ) {
			foreach ( $tools as $tool ) {
				$tool_class_parts = explode( '-', $tool );
				$tool_class_name  = implode( '', array_map( 'ucfirst', $tool_class_parts ) );
				$tool_class_name  = XFIVE_MCP_NAMESPACE . 'Abilities\\' . $tool_class_name;

				if ( ! class_exists( $tool_class_name ) ) {
					continue;
				}

				$tool_class = new $tool_class_name();
				$config     = $tool_class->get_config();

				$args = wp_parse_args(
					$config,
					array(
						'label'               => $tool_class->get_name(),
						'description'         => $tool_class->get_description(),
						'category'            => $tool_category,
						'permission_callback' => array( $tool_class, 'permission_callback' ),
						'input_schema'        => $tool_class->get_input_schema(),
						'output_schema'       => $tool_class->get_output_schema(),
						'execute_callback'    => array( $tool_class, 'execute_callback' ),
						'meta'                => array(
							'show_in_rest' => true,
						),
					)
				);

				wp_register_ability(
					$tool_category . '/' . $tool,
					$args
				);
			}
		}
	}
}
