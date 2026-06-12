<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostTrash extends AbilitiesBase {
	/**
	 * Get configuration for the post trash ability.
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
		return 'Post - Trash';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'Move a post to the trash, or restore (untrash) a trashed post. Pass action "trash" (default) or "untrash". This never deletes permanently — trashed posts can be restored. Returns the resulting post status.';
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
					'description' => 'The ID of the post to trash or restore.',
				),
				'action'  => array(
					'type'        => 'string',
					'description' => 'What to do: "trash" (default) moves the post to the trash, "untrash" restores a trashed post to its previous status. Permanent deletion is intentionally NOT supported.',
					'enum'        => array( 'trash', 'untrash' ),
					'default'     => 'trash',
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
				'trashed' => array(
					'type'        => 'boolean',
					'description' => 'True when the post was moved to the trash.',
				),
				'untrashed' => array(
					'type'        => 'boolean',
					'description' => 'True when the post was restored from the trash.',
				),
				'status'  => array(
					'type'        => 'string',
					'description' => 'The post status after the action.',
				),
				'hint'    => array(
					'type' => 'string',
				),
			),
		);
	}

	/**
	 * Execute the post trashing.
	 *
	 * @param array $args Arguments for trashing a post.
	 * @return array|\WP_Error Array with status on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new \WP_Error( 'not_found', 'Post not found' );
		}

		$action = $args['action'] ?? 'trash';

		if ( 'untrash' === $action ) {
			if ( 'trash' !== $post->post_status ) {
				return array(
					'untrashed' => false,
					'status'    => $post->post_status,
					'hint'      => sprintf( 'Post %1$d is not in the trash (status "%2$s"); nothing to restore.', $post_id, $post->post_status ),
				);
			}

			$result = wp_untrash_post( $post_id );

			if ( ! $result ) {
				return new \WP_Error( 'untrash_failed', 'Failed to restore post from trash' );
			}

			$status = get_post_status( $post_id );

			return array(
				'untrashed' => true,
				'status'    => $status,
				'hint'      => sprintf( 'Post %1$d restored from trash to status "%2$s".', $post_id, $status ),
			);
		}

		if ( 'trash' === $post->post_status ) {
			return array(
				'trashed' => true,
				'status'  => 'trash',
				'hint'    => sprintf( 'Post %d is already in the trash. Use action "untrash" to restore it.', $post_id ),
			);
		}

		$result = wp_trash_post( $post_id );

		if ( ! $result ) {
			return new \WP_Error( 'trash_failed', 'Failed to move post to trash' );
		}

		return array(
			'trashed' => true,
			'status'  => 'trash',
			'hint'    => sprintf( 'Post %d moved to trash. Restore with action "untrash"; this tool never deletes permanently.', $post_id ),
		);
	}
}
