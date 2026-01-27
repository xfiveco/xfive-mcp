<?php

namespace XfiveMCP\Trait;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait Config {
	/**
	 * Tools.
	 *
	 * @var array
	 */
	private array $tools = array(
		'xfive-blocks' => array(
			'block-tree',
			'block-add',
			'block-update',
			'block-remove',
			'block-move',
			'block-replace',
			'block-schema',
		),
		'xfive-posts'  => array(
			'post-by-title',
			'post-get-content',
			'post-update-content',
			'post-create',
			'post-update',
			'post-trash',
		),
	);

	/**
	 * Get tools with optional category prefix.
	 *
	 * @param bool $with_category Whether to include the category prefix.
	 *
	 * @return array
	 */
	public function get_tools( bool $with_category = false ): array {
		if ( $with_category ) {
			$tools = array();

			foreach ( $this->tools as $category => $cat_tools ) {
				$tools = array_merge(
					$tools,
					array_map(
						fn( $tool ) => $category . '/' . $tool,
						$cat_tools
					)
				);
			}

			return $tools;
		}

		return $this->tools;
	}
}
