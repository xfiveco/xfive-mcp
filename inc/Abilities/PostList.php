<?php

namespace XfiveMCP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PostList extends AbilitiesBase {
	/**
	 * Get configuration for the post list ability.
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
		return 'Post - List';
	}

	/**
	 * Get the description of the ability.
	 *
	 * @return string The ability description.
	 */
	public function get_description(): string {
		return 'List/enumerate posts with pagination, search, status, ordering, taxonomy filter and meta_query. post_type accepts a single type or an array. By default each row has id, title, slug, status, type, date, parent, menu_order, thumbnail_id; set fields="ids" for just an array of IDs (lean) or fields="full" to also include modified date, author, excerpt, comment/ping status. Returns total + total_pages for paging. Use this to discover all entries of a CPT (e.g. every "industry") before reading/migrating — the read counterpart to post-create/post-update. For a single known title use post-by-title instead.';
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
				'post_type'     => array(
					'type'        => array( 'string', 'array' ),
					'description' => 'Post type(s) to list. A single type (e.g. "industry") or an array of types. Defaults to "post".',
					'default'     => 'post',
					'items'       => array( 'type' => 'string' ),
				),
				'post_status'   => array(
					'type'        => 'string',
					'description' => 'Status filter: a single status, "any", or comma-separated list. Defaults to "any".',
					'default'     => 'any',
				),
				'search'        => array(
					'type'        => 'string',
					'description' => 'Optional search string (matches title/content).',
				),
				'fields'        => array(
					'type'        => 'string',
					'description' => 'Output shape: "summary" (default — id/title/slug/status/type/date/parent/menu_order/thumbnail_id), "ids" (lean — posts is a flat array of integer IDs), or "full" (summary plus modified/author/excerpt/comment_status/ping_status).',
					'enum'        => array( 'summary', 'ids', 'full' ),
					'default'     => 'summary',
				),
				'per_page'      => array(
					'type'        => 'integer',
					'description' => 'Results per page (1-100). Defaults to 50. Use -1 for all (no paging).',
					'default'     => 50,
				),
				'page'          => array(
					'type'        => 'integer',
					'description' => 'Page number (1-based). Defaults to 1.',
					'default'     => 1,
				),
				'orderby'       => array(
					'type'        => 'string',
					'description' => 'Order field (e.g. "date", "title", "menu_order", "ID"). Defaults to "date".',
					'default'     => 'date',
				),
				'order'         => array(
					'type'        => 'string',
					'description' => 'ASC or DESC. Defaults to DESC.',
					'default'     => 'DESC',
				),
				'taxonomy'      => array(
					'type'        => 'string',
					'description' => 'Optional taxonomy slug to filter by (pair with "terms").',
				),
				'terms'         => array(
					'type'        => 'array',
					'description' => 'Optional term IDs to filter by within "taxonomy".',
					'items'       => array( 'type' => 'integer' ),
				),
				'meta_query'    => array(
					'type'        => 'array',
					'description' => 'Optional WP_Query meta_query clauses. Array of { key, value, compare, type } objects (e.g. [{"key":"_thumbnail_id","compare":"EXISTS"}]). Combined with meta_relation. Use to find posts by meta.',
					'items'       => array( 'type' => 'object' ),
				),
				'meta_relation' => array(
					'type'        => 'string',
					'description' => 'Relation between meta_query clauses: "AND" (default) or "OR".',
					'enum'        => array( 'AND', 'OR' ),
					'default'     => 'AND',
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
				'posts'       => array(
					'type'        => 'array',
					'description' => 'Matching posts. When fields="ids" this is a flat array of integer IDs; otherwise an array of post objects (summary or full shape).',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'             => array( 'type' => 'integer' ),
							'title'          => array( 'type' => 'string' ),
							'slug'           => array( 'type' => 'string' ),
							'status'         => array( 'type' => 'string' ),
							'type'           => array( 'type' => 'string' ),
							'date'           => array( 'type' => 'string' ),
							'parent'         => array( 'type' => 'integer' ),
							'menu_order'     => array( 'type' => 'integer' ),
							'thumbnail_id'   => array( 'type' => 'integer' ),
							'modified'       => array( 'type' => 'string' ),
							'author'         => array( 'type' => 'integer' ),
							'excerpt'        => array( 'type' => 'string' ),
							'comment_status' => array( 'type' => 'string' ),
							'ping_status'    => array( 'type' => 'string' ),
						),
					),
				),
				'total'       => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
				'hint'        => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Execute the post list.
	 *
	 * @param array $args Arguments for listing posts.
	 * @return array|\WP_Error Array with posts on success, WP_Error on failure.
	 */
	public function execute_callback( array $args = array() ): array|object {
		$post_type = $args['post_type'] ?? 'post';
		$types     = is_array( $post_type ) ? $post_type : array( $post_type );
		$types     = array_values( array_filter( array_map( 'strval', $types ) ) );

		if ( empty( $types ) ) {
			return new \WP_Error( 'invalid_post_type', 'Provide at least one post_type.' );
		}

		foreach ( $types as $type ) {
			if ( ! post_type_exists( $type ) ) {
				return new \WP_Error( 'invalid_post_type', sprintf( 'Post type "%s" is not registered.', $type ) );
			}
		}

		$fields = $args['fields'] ?? 'summary';
		if ( ! in_array( $fields, array( 'summary', 'ids', 'full' ), true ) ) {
			$fields = 'summary';
		}

		$per_page = isset( $args['per_page'] ) ? (int) $args['per_page'] : 50;
		if ( -1 !== $per_page ) {
			$per_page = max( 1, min( 100, $per_page ) );
		}

		// WP_Query ignores "paged" when posts_per_page is -1 (all results), so
		// pin the page to 1 there to avoid reporting a misleading page number.
		$page = ( -1 === $per_page ) ? 1 : max( 1, (int) ( $args['page'] ?? 1 ) );

		$status = $this->parse_status_arg( $args['post_status'] ?? 'any' );

		// Summary/full rows include the thumbnail id, so prime the meta cache to
		// avoid a per-row get_post_thumbnail_id() query (N+1). "ids" mode never
		// hydrates posts, so leave it off there.
		$need_meta = 'ids' !== $fields;

		$query_args = array(
			'post_type'              => count( $types ) === 1 ? $types[0] : $types,
			'post_status'            => $status,
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => $args['orderby'] ?? 'date',
			'order'                  => $args['order'] ?? 'DESC',
			'no_found_rows'          => false,
			'update_post_meta_cache' => $need_meta,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
		);

		// "ids" mode lets WP_Query return only IDs — cheaper, no post hydration.
		if ( 'ids' === $fields ) {
			$query_args['fields'] = 'ids';
		}

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = $args['search'];
		}

		if ( ! empty( $args['taxonomy'] ) && ! empty( $args['terms'] ) && is_array( $args['terms'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => $args['taxonomy'],
					'field'    => 'term_id',
					'terms'    => array_map( 'intval', $args['terms'] ),
				),
			);
		}

		if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
			$meta_query = $args['meta_query'];
			$relation   = ( ( $args['meta_relation'] ?? 'AND' ) === 'OR' ) ? 'OR' : 'AND';
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = $relation;
			}
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta filtering is the explicit purpose of this read tool.
			$query_args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $query_args );

		if ( 'ids' === $fields ) {
			$posts = array_map( 'intval', $query->posts );
		} else {
			$posts = array();
			foreach ( $query->posts as $post ) {
				$row = array(
					'id'           => (int) $post->ID,
					'title'        => $post->post_title,
					'slug'         => $post->post_name,
					'status'       => $post->post_status,
					'type'         => $post->post_type,
					'date'         => $post->post_date,
					'parent'       => (int) $post->post_parent,
					'menu_order'   => (int) $post->menu_order,
					'thumbnail_id' => (int) get_post_thumbnail_id( $post->ID ),
				);

				if ( 'full' === $fields ) {
					$row['modified']       = $post->post_modified;
					$row['author']         = (int) $post->post_author;
					$row['excerpt']        = $post->post_excerpt;
					$row['comment_status'] = $post->comment_status;
					$row['ping_status']    = $post->ping_status;
				}

				$posts[] = $row;
			}
		}

		return array(
			'posts'       => $posts,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'hint'        => sprintf( '%1$d of %2$d %3$s post(s) (page %4$d/%5$d, fields=%6$s).', count( $posts ), (int) $query->found_posts, implode( '+', $types ), $page, max( 1, (int) $query->max_num_pages ), $fields ),
		);
	}
}
