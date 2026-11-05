<?php
/**
 * Data storage and persistence layer for AB Form Entries Manager.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Storage
 */
class ABFEM_Storage {

	/**
	 * Register the custom post type for submissions.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Form Entries', 'post type general name', 'ab-form-entries-manager' ),
			'singular_name'      => _x( 'Form Entry', 'post type singular name', 'ab-form-entries-manager' ),
			'menu_name'          => _x( 'Form Entries', 'admin menu', 'ab-form-entries-manager' ),
			'name_admin_bar'     => _x( 'Form Entry', 'add new on admin bar', 'ab-form-entries-manager' ),
			'view_item'          => __( 'View Entry', 'ab-form-entries-manager' ),
			'search_items'       => __( 'Search Entries', 'ab-form-entries-manager' ),
			'not_found'          => __( 'No entries found.', 'ab-form-entries-manager' ),
			'not_found_in_trash' => __( 'No entries found in Trash.', 'ab-form-entries-manager' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Stored form submissions captured by AB Form Entries Manager.', 'ab-form-entries-manager' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => false,
			'can_export'          => true,
			'delete_with_user'    => false,
			'supports'            => array( 'title', 'custom-fields' ),
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
		);

		register_post_type( ABFEM_POST_TYPE, $args );
	}

	/**
	 * Create and persist a new form submission entry.
	 *
	 * @param array $data Normalized submission data.
	 * @return int|WP_Error Entry post ID or WP_Error on failure.
	 */
	public static function create_entry( array $data ) {
		$form_name   = ! empty( $data['source_form_name'] ) ? sanitize_text_field( $data['source_form_name'] ) : __( 'Untitled Form', 'ab-form-entries-manager' );
		$form_id     = ! empty( $data['source_form_id'] ) ? sanitize_text_field( $data['source_form_id'] ) : '';
		$plugin      = ! empty( $data['source_plugin'] ) ? sanitize_key( $data['source_plugin'] ) : 'unknown';
		$plugin_name = ! empty( $data['source_plugin_name'] ) ? sanitize_text_field( $data['source_plugin_name'] ) : ucfirst( $plugin );

		$post_title = sprintf(
			/* translators: 1: Form Name, 2: Plugin Name, 3: Form ID */
			__( '%1$s (%2$s #%3$s)', 'ab-form-entries-manager' ),
			$form_name,
			$plugin_name,
			$form_id
		);

		$post_arr = array(
			'post_type'   => ABFEM_POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => $post_title,
		);

		$entry_id = wp_insert_post( $post_arr, true );

		if ( is_wp_error( $entry_id ) ) {
			return $entry_id;
		}

		// Prepare search index string (concatenating all values).
		$searchable_text = array();
		if ( ! empty( $data['submitter_name'] ) ) {
			$searchable_text[] = $data['submitter_name'];
		}
		if ( ! empty( $data['submitter_email'] ) ) {
			$searchable_text[] = $data['submitter_email'];
		}
		if ( ! empty( $data['fields'] ) && is_array( $data['fields'] ) ) {
			foreach ( $data['fields'] as $key => $f ) {
				if ( is_array( $f ) ) {
					if ( ! empty( $f['label'] ) ) {
						$searchable_text[] = (string) $f['label'];
					}
					if ( ! empty( $f['value'] ) ) {
						if ( is_array( $f['value'] ) ) {
							$searchable_text[] = implode( ' ', array_map( 'strval', $f['value'] ) );
						} else {
							$searchable_text[] = (string) $f['value'];
						}
					}
				} else {
					if ( is_string( $key ) ) {
						$searchable_text[] = $key;
					}
					if ( ! empty( $f ) ) {
						$searchable_text[] = (string) $f;
					}
				}
			}
		}
		$fields_search = implode( ' ', $searchable_text );

		// Save metadata.
		update_post_meta( $entry_id, '_abfem_source_plugin', $plugin );
		update_post_meta( $entry_id, '_abfem_source_plugin_name', $plugin_name );
		update_post_meta( $entry_id, '_abfem_form_id', $form_id );
		update_post_meta( $entry_id, '_abfem_form_name', $form_name );
		update_post_meta( $entry_id, '_abfem_source_entry_id', ! empty( $data['source_entry_id'] ) ? sanitize_text_field( $data['source_entry_id'] ) : '' );
		update_post_meta( $entry_id, '_abfem_source_url', ! empty( $data['source_url'] ) ? esc_url_raw( $data['source_url'] ) : '' );
		update_post_meta( $entry_id, '_abfem_submitter_name', ! empty( $data['submitter_name'] ) ? sanitize_text_field( $data['submitter_name'] ) : '' );
		update_post_meta( $entry_id, '_abfem_submitter_email', ! empty( $data['submitter_email'] ) ? sanitize_email( $data['submitter_email'] ) : '' );
		update_post_meta( $entry_id, '_abfem_submitter_ip', ! empty( $data['submitter_ip'] ) ? sanitize_text_field( $data['submitter_ip'] ) : '' );
		update_post_meta( $entry_id, '_abfem_user_agent', ! empty( $data['user_agent'] ) ? sanitize_text_field( $data['user_agent'] ) : '' );
		update_post_meta( $entry_id, '_abfem_is_read', 0 ); // Unread by default.
		update_post_meta( $entry_id, '_abfem_fields', ! empty( $data['fields'] ) ? $data['fields'] : array() );
		update_post_meta( $entry_id, '_abfem_fields_search', $fields_search );
		update_post_meta( $entry_id, '_abfem_hash', ! empty( $data['hash'] ) ? sanitize_text_field( $data['hash'] ) : '' );
		update_post_meta( $entry_id, '_abfem_created_at', current_time( 'mysql' ) );

		// Clear cached counts.
		delete_transient( 'abfem_counts' );

		return $entry_id;
	}

	/**
	 * Check if an identical submission exists within the duplicate prevention window.
	 *
	 * @param string $hash Submission content hash.
	 * @param int    $window_seconds Time window in seconds (default 15).
	 * @return bool True if duplicate detected.
	 */
	public static function is_duplicate( $hash, $window_seconds = 15 ) {
		if ( empty( $hash ) ) {
			return false;
		}

		global $wpdb;

		// Calculate threshold timestamp in MySQL format.
		$threshold = gmdate( 'Y-m-d H:i:s', time() - $window_seconds );

		// Query for existing entry with matching hash created after threshold.
		$query = $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type = %s
			   AND p.post_status = 'publish'
			   AND p.post_date_gmt >= %s
			   AND pm.meta_key = '_abfem_hash'
			   AND pm.meta_value = %s
			 LIMIT 1",
			ABFEM_POST_TYPE,
			$threshold,
			$hash
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$existing = $wpdb->get_var( $query );
		return ! empty( $existing );
	}

	/**
	 * Retrieve a single submission entry formatted.
	 *
	 * @param int $entry_id Post ID.
	 * @return array|null
	 */
	public static function get_entry( $entry_id ) {
		$post = get_post( $entry_id );
		if ( ! $post || ABFEM_POST_TYPE !== $post->post_type ) {
			return null;
		}

		$fields = get_post_meta( $entry_id, '_abfem_fields', true );
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		return array(
			'id'                  => (int) $entry_id,
			'title'               => $post->post_title,
			'date'                => $post->post_date,
			'date_gmt'            => $post->post_date_gmt,
			'source_plugin'       => get_post_meta( $entry_id, '_abfem_source_plugin', true ),
			'source_plugin_name'  => get_post_meta( $entry_id, '_abfem_source_plugin_name', true ),
			'form_id'             => get_post_meta( $entry_id, '_abfem_form_id', true ),
			'form_name'           => get_post_meta( $entry_id, '_abfem_form_name', true ),
			'source_entry_id'     => get_post_meta( $entry_id, '_abfem_source_entry_id', true ),
			'source_url'          => get_post_meta( $entry_id, '_abfem_source_url', true ),
			'submitter_name'      => get_post_meta( $entry_id, '_abfem_submitter_name', true ),
			'submitter_email'     => get_post_meta( $entry_id, '_abfem_submitter_email', true ),
			'submitter_ip'        => get_post_meta( $entry_id, '_abfem_submitter_ip', true ),
			'user_agent'          => get_post_meta( $entry_id, '_abfem_user_agent', true ),
			'is_read'             => (int) get_post_meta( $entry_id, '_abfem_is_read', true ),
			'fields'              => $fields,
			'hash'                => get_post_meta( $entry_id, '_abfem_hash', true ),
		);
	}

	/**
	 * Update read/unread status for an entry.
	 *
	 * @param int $entry_id Post ID.
	 * @param int $is_read  1 for read, 0 for unread.
	 * @return bool
	 */
	public static function update_read_status( $entry_id, $is_read ) {
		$post = get_post( $entry_id );
		if ( ! $post || ABFEM_POST_TYPE !== $post->post_type ) {
			return false;
		}

		$status  = $is_read ? 1 : 0;
		$current = get_post_meta( $entry_id, '_abfem_is_read', true );

		if ( '' !== $current && (int) $current === $status ) {
			delete_transient( 'abfem_counts' );
			return true;
		}

		$updated = update_post_meta( $entry_id, '_abfem_is_read', $status );
		delete_transient( 'abfem_counts' );
		return false !== $updated;
	}

	/**
	 * Delete a single entry.
	 *
	 * @param int  $entry_id Post ID.
	 * @param bool $force    Force delete without trashing.
	 * @return bool
	 */
	public static function delete_entry( $entry_id, $force = true ) {
		$post = get_post( $entry_id );
		if ( ! $post || ABFEM_POST_TYPE !== $post->post_type ) {
			return false;
		}

		$deleted = wp_delete_post( $entry_id, $force );
		delete_transient( 'abfem_counts' );
		return ! empty( $deleted );
	}

	/**
	 * Bulk update read status.
	 *
	 * @param array $entry_ids Array of post IDs.
	 * @param int   $is_read   1 for read, 0 for unread.
	 * @return int Number of updated entries.
	 */
	public static function bulk_update_read_status( array $entry_ids, $is_read ) {
		$count = 0;
		$status = $is_read ? 1 : 0;
		foreach ( $entry_ids as $id ) {
			$id = absint( $id );
			if ( $id && self::update_read_status( $id, $status ) ) {
				$count++;
			}
		}
		delete_transient( 'abfem_counts' );
		return $count;
	}

	/**
	 * Bulk delete entries.
	 *
	 * @param array $entry_ids Array of post IDs.
	 * @return int Number of deleted entries.
	 */
	public static function bulk_delete_entries( array $entry_ids ) {
		$count = 0;
		foreach ( $entry_ids as $id ) {
			$id = absint( $id );
			if ( $id && self::delete_entry( $id, true ) ) {
				$count++;
			}
		}
		delete_transient( 'abfem_counts' );
		return $count;
	}

	/**
	 * Query submissions with server-side pagination, search, and filtering.
	 *
	 * @param array $args Query arguments.
	 * @return array Array containing 'entries', 'total_items', 'total_pages', 'current_page'.
	 */
	public static function get_entries( array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'page'          => 1,
			'per_page'      => ABFEM_Settings::get( 'entries_per_page', 20 ),
			'search'        => '',
			'source_plugin' => '',
			'form_id'       => '',
			'status'        => '', // 'read', 'unread', or '' for all.
			'date_from'     => '',
			'date_to'       => '',
			'date_preset'   => '', // 'today', 'yesterday', '7days', '30days', 'this_month'
			'orderby'       => 'date',
			'order'         => 'DESC',
			'include'       => array(),
		);

		$params = wp_parse_args( $args, $defaults );

		$page     = max( 1, absint( $params['page'] ) );
		$per_page = max( 1, absint( $params['per_page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		$where   = array( "p.post_type = '" . ABFEM_POST_TYPE . "'", "p.post_status = 'publish'" );
		$joins   = array();
		$join_count = 0;

		// Filter by include IDs.
		if ( ! empty( $params['include'] ) && is_array( $params['include'] ) ) {
			$ids = array_filter( array_map( 'absint', $params['include'] ) );
			if ( ! empty( $ids ) ) {
				$where[] = 'p.ID IN (' . implode( ',', $ids ) . ')';
			}
		}

		// Filter by source plugin.
		if ( ! empty( $params['source_plugin'] ) ) {
			$plugin_val = sanitize_key( $params['source_plugin'] );
			$join_count++;
			$alias = 'pm_plugin_' . $join_count;
			$joins[] = "INNER JOIN {$wpdb->postmeta} {$alias} ON p.ID = {$alias}.post_id AND {$alias}.meta_key = '_abfem_source_plugin'";
			$where[] = $alias . '.meta_value = ' . $wpdb->prepare( '%s', $plugin_val );
		}

		// Filter by form ID.
		if ( ! empty( $params['form_id'] ) ) {
			$form_id_val = sanitize_text_field( $params['form_id'] );
			$join_count++;
			$alias = 'pm_form_' . $join_count;
			$joins[] = "INNER JOIN {$wpdb->postmeta} {$alias} ON p.ID = {$alias}.post_id AND {$alias}.meta_key = '_abfem_form_id'";
			$where[] = $alias . '.meta_value = ' . $wpdb->prepare( '%s', $form_id_val );
		}

		// Filter by status (read / unread).
		if ( 'read' === $params['status'] ) {
			$join_count++;
			$alias = 'pm_read_' . $join_count;
			$joins[] = "INNER JOIN {$wpdb->postmeta} {$alias} ON p.ID = {$alias}.post_id AND {$alias}.meta_key = '_abfem_is_read'";
			$where[] = "{$alias}.meta_value = '1'";
		} elseif ( 'unread' === $params['status'] ) {
			$join_count++;
			$alias = 'pm_unread_' . $join_count;
			$joins[] = "LEFT JOIN {$wpdb->postmeta} {$alias} ON p.ID = {$alias}.post_id AND {$alias}.meta_key = '_abfem_is_read'";
			$where[] = "( {$alias}.meta_value = '0' OR {$alias}.meta_value IS NULL )";
		}

		// Handle Date Range Presets.
		if ( ! empty( $params['date_preset'] ) ) {
			$today_start = current_time( 'Y-m-d 00:00:00' );
			$today_end   = current_time( 'Y-m-d 23:59:59' );

			switch ( $params['date_preset'] ) {
				case 'today':
					$params['date_from'] = current_time( 'Y-m-d' );
					$params['date_to']   = current_time( 'Y-m-d' );
					break;
				case 'yesterday':
					$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
					$params['date_from'] = $yesterday;
					$params['date_to']   = $yesterday;
					break;
				case '7days':
					$params['date_from'] = gmdate( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) );
					$params['date_to']   = current_time( 'Y-m-d' );
					break;
				case '30days':
					$params['date_from'] = gmdate( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) );
					$params['date_to']   = current_time( 'Y-m-d' );
					break;
				case 'this_month':
					$params['date_from'] = current_time( 'Y-m-01' );
					$params['date_to']   = current_time( 'Y-m-t' );
					break;
			}
		}

		// Filter by custom date range.
		if ( ! empty( $params['date_from'] ) ) {
			$date_from = sanitize_text_field( $params['date_from'] ) . ' 00:00:00';
			$where[]   = $wpdb->prepare( 'p.post_date >= %s', $date_from );
		}
		if ( ! empty( $params['date_to'] ) ) {
			$date_to = sanitize_text_field( $params['date_to'] ) . ' 23:59:59';
			$where[] = $wpdb->prepare( 'p.post_date <= %s', $date_to );
		}

		// Search: searches submission ID, form name, submitter name, email, and fields search text.
		if ( ! empty( $params['search'] ) ) {
			$search_raw = trim( $params['search'] );
			$search_like = '%' . $wpdb->esc_like( $search_raw ) . '%';

			$search_clauses = array();

			// Exact ID search if numeric.
			if ( is_numeric( $search_raw ) ) {
				$search_clauses[] = $wpdb->prepare( 'p.ID = %d', absint( $search_raw ) );
			}

			// Post title / form name search.
			$search_clauses[] = $wpdb->prepare( 'p.post_title LIKE %s', $search_like );

			// Postmeta search via subquery for fast indexed matching.
			$search_clauses[] = $wpdb->prepare(
				"p.ID IN (
					SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key IN ('_abfem_submitter_name', '_abfem_submitter_email', '_abfem_form_name', '_abfem_fields_search', '_abfem_source_entry_id')
					  AND meta_value LIKE %s
				)",
				$search_like
			);

			$where[] = '(' . implode( ' OR ', $search_clauses ) . ')';
		}

		// Ordering.
		$order = strtoupper( $params['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby_field = 'p.post_date';

		if ( 'id' === $params['orderby'] ) {
			$orderby_field = 'p.ID';
		} elseif ( 'form_name' === $params['orderby'] ) {
			$join_count++;
			$alias = 'pm_ob_name_' . $join_count;
			$joins[] = "LEFT JOIN {$wpdb->postmeta} {$alias} ON p.ID = {$alias}.post_id AND {$alias}.meta_key = '_abfem_form_name'";
			$orderby_field = "{$alias}.meta_value";
		} elseif ( 'status' === $params['orderby'] ) {
			$join_count++;
			$alias = 'pm_ob_status_' . $join_count;
			$joins[] = "LEFT JOIN {$wpdb->postmeta} {$alias} ON p.ID = {$alias}.post_id AND {$alias}.meta_key = '_abfem_is_read'";
			$orderby_field = "{$alias}.meta_value";
		}

		$joins_sql = ! empty( $joins ) ? implode( ' ', array_unique( $joins ) ) : '';
		$where_sql = implode( ' AND ', $where );

		// Count total matching items.
		$count_sql   = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p {$joins_sql} WHERE {$where_sql}";
		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$total_items = (int) $wpdb->get_var( $count_sql );

		// Fetch IDs for current page.
		$query = "SELECT DISTINCT p.ID FROM {$wpdb->posts} p {$joins_sql} WHERE {$where_sql} ORDER BY {$orderby_field} {$order} LIMIT %d OFFSET %d";
		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$post_ids = $wpdb->get_col( $wpdb->prepare( $query, $per_page, $offset ) );

		$entries = array();
		if ( ! empty( $post_ids ) ) {
			foreach ( $post_ids as $pid ) {
				$entry = self::get_entry( $pid );
				if ( $entry ) {
					$entries[] = $entry;
				}
			}
		}

		$total_pages = ceil( $total_items / $per_page );

		return array(
			'entries'      => $entries,
			'total_items'  => $total_items,
			'total_pages'  => max( 1, (int) $total_pages ),
			'current_page' => $page,
		);
	}

	/**
	 * Get metric counters (total, today, unread, active integrations).
	 *
	 * @return array
	 */
	public static function get_counts() {
		$cached = get_transient( 'abfem_counts' );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		// Total submissions.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
				ABFEM_POST_TYPE
			)
		);

		// Submissions received today.
		$today_start = current_time( 'Y-m-d 00:00:00' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$today = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND post_date >= %s",
				ABFEM_POST_TYPE,
				$today_start
			)
		);

		// Unread submissions.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$unread = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(p.ID) FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = %s AND p.post_status = 'publish'
				   AND pm.meta_key = '_abfem_is_read' AND pm.meta_value = '0'",
				ABFEM_POST_TYPE
			)
		);

		$counts = array(
			'total'  => $total,
			'today'  => $today,
			'unread' => $unread,
		);

		// Cache for 5 minutes.
		set_transient( 'abfem_counts', $counts, 300 );

		return $counts;
	}

	/**
	 * Get total count of unread submissions.
	 *
	 * @return int
	 */
	public static function get_unread_count() {
		$counts = self::get_counts();
		return isset( $counts['unread'] ) ? (int) $counts['unread'] : 0;
	}

	/**
	 * Get the ID of the most recent unread submission.
	 *
	 * @return int ID of the latest unread submission, or 0 if none.
	 */
	public static function get_latest_unread_entry_id() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = %s AND p.post_status = 'publish'
				   AND pm.meta_key = '_abfem_is_read' AND pm.meta_value = '0'
				 ORDER BY p.ID DESC LIMIT 1",
				ABFEM_POST_TYPE
			)
		);
	}

	/**
	 * Get total submissions captured by a specific builder plugin.
	 *
	 * @param string $plugin_id Plugin identifier (e.g. 'cf7', 'wpforms').
	 * @return int
	 */
	public static function get_entry_count_by_plugin( $plugin_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(p.ID) FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_abfem_source_plugin'
				 WHERE p.post_type = %s AND p.post_status = 'publish' AND pm.meta_value = %s",
				ABFEM_POST_TYPE,
				sanitize_key( $plugin_id )
			)
		);
	}

	/**
	 * Get aggregated summary of forms that have submissions.
	 *
	 * @return array
	 */
	public static function get_all_forms_summary() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					pm_form.meta_value AS form_id,
					pm_plugin.meta_value AS source_plugin,
					pm_name.meta_value AS form_name,
					COUNT(p.ID) AS submission_count,
					MAX(p.post_date) AS latest_submission
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm_form ON p.ID = pm_form.post_id AND pm_form.meta_key = '_abfem_form_id'
				 INNER JOIN {$wpdb->postmeta} pm_plugin ON p.ID = pm_plugin.post_id AND pm_plugin.meta_key = '_abfem_source_plugin'
				 LEFT JOIN {$wpdb->postmeta} pm_name ON p.ID = pm_name.post_id AND pm_name.meta_key = '_abfem_form_name'
				 WHERE p.post_type = %s AND p.post_status = 'publish'
				 GROUP BY pm_form.meta_value, pm_plugin.meta_value, pm_name.meta_value
				 ORDER BY submission_count DESC",
				ABFEM_POST_TYPE
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Find the adjacent entry (next or previous) relative to a given entry ID.
	 *
	 * @param int    $current_id Entry ID.
	 * @param string $direction  'prev' or 'next'.
	 * @param array  $filter_args Current filter parameters to respect navigation context.
	 * @return int|null Adjacent ID or null.
	 */
	public static function get_adjacent_entry_id( $current_id, $direction = 'next', array $filter_args = array() ) {
		$current_entry = self::get_entry( $current_id );
		if ( ! $current_entry ) {
			return null;
		}

		global $wpdb;

		$order = ( 'next' === $direction ) ? 'DESC' : 'ASC';

		$where = array(
			"p.post_type = '" . ABFEM_POST_TYPE . "'",
			"p.post_status = 'publish'",
		);

		if ( 'next' === $direction ) {
			$where[] = $wpdb->prepare( 'p.post_date < %s', $current_entry['date'] );
		} else {
			$where[] = $wpdb->prepare( 'p.post_date > %s', $current_entry['date'] );
		}

		$joins = array();

		// Apply same filters if provided.
		if ( ! empty( $filter_args['source_plugin'] ) ) {
			$plugin_val = sanitize_key( $filter_args['source_plugin'] );
			$joins[] = "INNER JOIN {$wpdb->postmeta} pm_p ON p.ID = pm_p.post_id AND pm_p.meta_key = '_abfem_source_plugin'";
			$where[] = $wpdb->prepare( 'pm_p.meta_value = %s', $plugin_val );
		}

		if ( ! empty( $filter_args['form_id'] ) ) {
			$form_val = sanitize_text_field( $filter_args['form_id'] );
			$joins[] = "INNER JOIN {$wpdb->postmeta} pm_f ON p.ID = pm_f.post_id AND pm_f.meta_key = '_abfem_form_id'";
			$where[] = $wpdb->prepare( 'pm_f.meta_value = %s', $form_val );
		}

		if ( 'read' === ( $filter_args['status'] ?? '' ) ) {
			$joins[] = "INNER JOIN {$wpdb->postmeta} pm_r ON p.ID = pm_r.post_id AND pm_r.meta_key = '_abfem_is_read'";
			$where[] = "pm_r.meta_value = '1'";
		} elseif ( 'unread' === ( $filter_args['status'] ?? '' ) ) {
			$joins[] = "LEFT JOIN {$wpdb->postmeta} pm_r ON p.ID = pm_r.post_id AND pm_r.meta_key = '_abfem_is_read'";
			$where[] = "( pm_r.meta_value = '0' OR pm_r.meta_value IS NULL )";
		}

		$joins_sql = ! empty( $joins ) ? implode( ' ', array_unique( $joins ) ) : '';
		$where_sql = implode( ' AND ', $where );

		$query = "SELECT p.ID FROM {$wpdb->posts} p {$joins_sql} WHERE {$where_sql} ORDER BY p.post_date {$order} LIMIT 1";
		// phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$adjacent_id = $wpdb->get_var( $query );

		return $adjacent_id ? (int) $adjacent_id : null;
	}

	/**
	 * Purge entries older than the specified retention days.
	 *
	 * @param int $days Number of days to retain (e.g., 30, 90).
	 * @return int Number of deleted entries.
	 */
	public static function purge_old_entries( $days ) {
		$days = absint( $days );
		if ( $days <= 0 ) {
			return 0;
		}

		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = %s
				   AND post_status = 'publish'
				   AND post_date_gmt < %s
				 LIMIT 200",
				ABFEM_POST_TYPE,
				$cutoff_date
			)
		);

		$deleted_count = 0;
		if ( ! empty( $old_ids ) ) {
			foreach ( $old_ids as $id ) {
				if ( self::delete_entry( (int) $id, true ) ) {
					$deleted_count++;
				}
			}
		}

		return $deleted_count;
	}
}
