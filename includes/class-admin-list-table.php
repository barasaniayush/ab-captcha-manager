<?php
/**
 * Submissions list table renderer.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Admin_List_Table
 */
class ABFEM_Admin_List_Table {

	/**
	 * Query arguments.
	 *
	 * @var array
	 */
	private $query_args = array();

	/**
	 * Query results.
	 *
	 * @var array
	 */
	private $results = array();

	/**
	 * Constructor.
	 *
	 * @param array $query_args Cleaned query parameters.
	 */
	public function __construct( array $query_args = array() ) {
		$this->query_args = $query_args;
		$this->prepare_items();
	}

	/**
	 * Fetch items from storage.
	 */
	public function prepare_items() {
		$this->results = ABFEM_Storage::get_entries( $this->query_args );
	}

	/**
	 * Get current results.
	 *
	 * @return array
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Render metric cards at top of dashboard.
	 *
	 * @param int $active_integrations_count Active integrations count.
	 */
	public function render_metrics( $active_integrations_count = 0 ) {
		$counts = ABFEM_Storage::get_counts();
		?>
		<div class="abfem-metrics-grid">
			<div class="abfem-metric-card abfem-metric-total">
				<div class="abfem-metric-icon"><span class="dashicons dashicons-email-alt"></span></div>
				<div class="abfem-metric-content">
					<span class="abfem-metric-label"><?php esc_html_e( 'Total Submissions', 'ab-form-entries-manager' ); ?></span>
					<span class="abfem-metric-value"><?php echo esc_html( number_format_i18n( $counts['total'] ) ); ?></span>
				</div>
			</div>

			<div class="abfem-metric-card abfem-metric-today">
				<div class="abfem-metric-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
				<div class="abfem-metric-content">
					<span class="abfem-metric-label"><?php esc_html_e( 'Received Today', 'ab-form-entries-manager' ); ?></span>
					<span class="abfem-metric-value"><?php echo esc_html( number_format_i18n( $counts['today'] ) ); ?></span>
				</div>
			</div>

			<div class="abfem-metric-card abfem-metric-unread">
				<div class="abfem-metric-icon"><span class="dashicons dashicons-marker"></span></div>
				<div class="abfem-metric-content">
					<span class="abfem-metric-label"><?php esc_html_e( 'Unread Submissions', 'ab-form-entries-manager' ); ?></span>
					<span class="abfem-metric-value"><?php echo esc_html( number_format_i18n( $counts['unread'] ) ); ?></span>
				</div>
			</div>

			<div class="abfem-metric-card abfem-metric-integrations">
				<div class="abfem-metric-icon"><span class="dashicons dashicons-networking"></span></div>
				<div class="abfem-metric-content">
					<span class="abfem-metric-label"><?php esc_html_e( 'Active Integrations', 'ab-form-entries-manager' ); ?></span>
					<span class="abfem-metric-value"><?php echo esc_html( number_format_i18n( $active_integrations_count ) ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the search and filter bar.
	 *
	 * @param array $all_forms List of discovered forms for dropdown.
	 */
	public function render_filters( array $all_forms = array() ) {
		$search       = isset( $this->query_args['search'] ) ? $this->query_args['search'] : '';
		$source       = isset( $this->query_args['source_plugin'] ) ? $this->query_args['source_plugin'] : '';
		$form_id      = isset( $this->query_args['form_id'] ) ? $this->query_args['form_id'] : '';
		$status       = isset( $this->query_args['status'] ) ? $this->query_args['status'] : '';
		$date_preset  = isset( $this->query_args['date_preset'] ) ? $this->query_args['date_preset'] : '';
		$date_from    = isset( $this->query_args['date_from'] ) ? $this->query_args['date_from'] : '';
		$date_to      = isset( $this->query_args['date_to'] ) ? $this->query_args['date_to'] : '';

		$has_filters = ! empty( $search ) || ! empty( $source ) || ! empty( $form_id ) || ! empty( $status ) || ! empty( $date_preset ) || ! empty( $date_from ) || ! empty( $date_to );

		$plugins = array(
			'cf7'          => __( 'Contact Form 7', 'ab-form-entries-manager' ),
			'wpforms'      => __( 'WPForms', 'ab-form-entries-manager' ),
			'forminator'   => __( 'Forminator Forms', 'ab-form-entries-manager' ),
			'elementor'    => __( 'Elementor Pro Forms', 'ab-form-entries-manager' ),
			'gravityforms' => __( 'Gravity Forms', 'ab-form-entries-manager' ),
			'ninjaforms'   => __( 'Ninja Forms', 'ab-form-entries-manager' ),
			'jetpack'      => __( 'Jetpack Forms', 'ab-form-entries-manager' ),
			'formidable'   => __( 'Formidable Forms', 'ab-form-entries-manager' ),
		);
		?>
		<div class="abfem-filter-bar">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="abfem-filter-form">
				<input type="hidden" name="page" value="abfem-submissions" />

				<div class="abfem-filter-group abfem-search-group">
					<span class="dashicons dashicons-search abfem-search-icon"></span>
					<input type="search" name="search" id="abfem-search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search ID, name, email, form, values...', 'ab-form-entries-manager' ); ?>" />
				</div>

				<div class="abfem-filter-group">
					<select name="source_plugin" id="abfem-source-plugin">
						<option value=""><?php esc_html_e( 'All Form Builders', 'ab-form-entries-manager' ); ?></option>
						<?php foreach ( $plugins as $p_id => $p_name ) : ?>
							<option value="<?php echo esc_attr( $p_id ); ?>" <?php selected( $source, $p_id ); ?>><?php echo esc_html( $p_name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<?php if ( ! empty( $all_forms ) ) : ?>
					<div class="abfem-filter-group">
						<select name="form_id" id="abfem-form-id">
							<option value=""><?php esc_html_e( 'All Forms', 'ab-form-entries-manager' ); ?></option>
							<?php foreach ( $all_forms as $f ) : ?>
								<option value="<?php echo esc_attr( $f['form_id'] ); ?>" <?php selected( $form_id, $f['form_id'] ); ?>>
									<?php echo esc_html( $f['form_name'] ); ?> (<?php echo esc_html( $f['source_plugin_name'] ?? $f['source_plugin'] ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="abfem-filter-group">
					<select name="status" id="abfem-status">
						<option value=""><?php esc_html_e( 'All Statuses', 'ab-form-entries-manager' ); ?></option>
						<option value="unread" <?php selected( $status, 'unread' ); ?>><?php esc_html_e( 'Unread Only', 'ab-form-entries-manager' ); ?></option>
						<option value="read" <?php selected( $status, 'read' ); ?>><?php esc_html_e( 'Read Only', 'ab-form-entries-manager' ); ?></option>
					</select>
				</div>

				<div class="abfem-filter-group">
					<select name="date_preset" id="abfem-date-preset">
						<option value=""><?php esc_html_e( 'All Dates', 'ab-form-entries-manager' ); ?></option>
						<option value="today" <?php selected( $date_preset, 'today' ); ?>><?php esc_html_e( 'Today', 'ab-form-entries-manager' ); ?></option>
						<option value="yesterday" <?php selected( $date_preset, 'yesterday' ); ?>><?php esc_html_e( 'Yesterday', 'ab-form-entries-manager' ); ?></option>
						<option value="7days" <?php selected( $date_preset, '7days' ); ?>><?php esc_html_e( 'Past 7 Days', 'ab-form-entries-manager' ); ?></option>
						<option value="30days" <?php selected( $date_preset, '30days' ); ?>><?php esc_html_e( 'Past 30 Days', 'ab-form-entries-manager' ); ?></option>
						<option value="this_month" <?php selected( $date_preset, 'this_month' ); ?>><?php esc_html_e( 'This Month', 'ab-form-entries-manager' ); ?></option>
					</select>
				</div>

				<div class="abfem-filter-actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'ab-form-entries-manager' ); ?></button>
					<?php if ( $has_filters ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ); ?>" class="button button-secondary abfem-clear-filters">
							<?php esc_html_e( 'Clear Filters', 'ab-form-entries-manager' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the submissions table with pagination and bulk actions.
	 */
	public function render_table() {
		$entries      = $this->results['entries'];
		$total_items  = $this->results['total_items'];
		$total_pages  = $this->results['total_pages'];
		$current_page = $this->results['current_page'];

		// Build filter query string to preserve on row actions, pagination, etc.
		$filter_params = array();
		foreach ( array( 'search', 'source_plugin', 'form_id', 'status', 'date_preset', 'date_from', 'date_to', 'orderby', 'order' ) as $key ) {
			if ( ! empty( $this->query_args[ $key ] ) ) {
				$filter_params[ $key ] = $this->query_args[ $key ];
			}
		}
		$base_url = add_query_arg( array_merge( array( 'page' => 'abfem-submissions' ), $filter_params ), admin_url( 'admin.php' ) );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="abfem-bulk-form">
			<input type="hidden" name="action" value="abfem_bulk_action" />
			<?php wp_nonce_field( 'abfem_bulk_action_nonce', 'nonce' ); ?>

			<!-- Pass active filter state to return cleanly after bulk operations -->
			<?php foreach ( $filter_params as $pk => $pv ) : ?>
				<input type="hidden" name="filter_<?php echo esc_attr( $pk ); ?>" value="<?php echo esc_attr( $pv ); ?>" />
			<?php endforeach; ?>

			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'ab-form-entries-manager' ); ?></label>
					<select name="bulk_action" id="bulk-action-selector-top">
						<option value="-1"><?php esc_html_e( 'Bulk actions', 'ab-form-entries-manager' ); ?></option>
						<option value="mark_read"><?php esc_html_e( 'Mark as Read', 'ab-form-entries-manager' ); ?></option>
						<option value="mark_unread"><?php esc_html_e( 'Mark as Unread', 'ab-form-entries-manager' ); ?></option>
						<option value="export_csv"><?php esc_html_e( 'Export to CSV', 'ab-form-entries-manager' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'ab-form-entries-manager' ); ?></option>
					</select>
					<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'ab-form-entries-manager' ); ?>" />
				</div>

				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						/* translators: %s: Total number of items */
						printf( esc_html( _n( '%s submission', '%s submissions', $total_items, 'ab-form-entries-manager' ) ), esc_html( number_format_i18n( $total_items ) ) );
						?>
					</span>

					<?php if ( $total_pages > 1 ) : ?>
						<span class="pagination-links">
							<?php if ( $current_page > 1 ) : ?>
								<a class="first-page button" href="<?php echo esc_url( add_query_arg( 'paged', 1, $base_url ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'First page', 'ab-form-entries-manager' ); ?></span>&laquo;</a>
								<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'ab-form-entries-manager' ); ?></span>&lsaquo;</a>
							<?php else : ?>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
							<?php endif; ?>

							<span class="paging-input">
								<span class="current-page"><?php echo esc_html( $current_page ); ?></span> <?php esc_html_e( 'of', 'ab-form-entries-manager' ); ?> <span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
							</span>

							<?php if ( $current_page < $total_pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Next page', 'ab-form-entries-manager' ); ?></span>&rsaquo;</a>
								<a class="last-page button" href="<?php echo esc_url( add_query_arg( 'paged', $total_pages, $base_url ) ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Last page', 'ab-form-entries-manager' ); ?></span>&raquo;</a>
							<?php else : ?>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
							<?php endif; ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<?php
			$current_orderby = $this->query_args['orderby'] ?? 'date';
			$current_order   = strtoupper( $this->query_args['order'] ?? ( 'date' === $current_orderby ? 'desc' : 'desc' ) );

			$id_is_sorted    = ( 'id' === $current_orderby );
			$id_next_order   = ( $id_is_sorted && 'ASC' === $current_order ) ? 'desc' : 'asc';
			$id_th_class     = $id_is_sorted ? 'manage-column column-id sorted ' . strtolower( $current_order ) : 'manage-column column-id sortable desc';

			$date_is_sorted  = ( 'date' === $current_orderby );
			$date_next_order = ( $date_is_sorted && 'ASC' === $current_order ) ? 'desc' : 'asc';
			$date_th_class   = $date_is_sorted ? 'manage-column column-date sorted ' . strtolower( $current_order ) : 'manage-column column-date sortable desc';
			?>
			<table class="wp-list-table widefat fixed striped table-view-list abfem-submissions-table">
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'ab-form-entries-manager' ); ?></label>
							<input id="cb-select-all-1" type="checkbox" />
						</td>
						<th scope="col" class="<?php echo esc_attr( $id_th_class ); ?>" style="width: 90px;">
							<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'id', 'order' => $id_next_order ), $base_url ) ); ?>">
								<span><?php esc_html_e( 'ID', 'ab-form-entries-manager' ); ?></span>
								<span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
							</a>
						</th>
						<th scope="col" class="manage-column column-submitter"><?php esc_html_e( 'Submitter', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="manage-column column-email"><?php esc_html_e( 'Email', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="manage-column column-form"><?php esc_html_e( 'Form', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="manage-column column-plugin" style="width: 140px;"><?php esc_html_e( 'Source Plugin', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="<?php echo esc_attr( $date_th_class ); ?>" style="width: 170px;">
							<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'date', 'order' => $date_next_order ), $base_url ) ); ?>">
								<span><?php esc_html_e( 'Date Submitted', 'ab-form-entries-manager' ); ?></span>
								<span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
							</a>
						</th>
						<th scope="col" class="manage-column column-status" style="width: 100px;"><?php esc_html_e( 'Status', 'ab-form-entries-manager' ); ?></th>
					</tr>
				</thead>

				<tbody id="the-list">
					<?php if ( empty( $entries ) ) : ?>
						<tr class="no-items">
							<td class="colspanchange" colspan="8">
								<div class="abfem-empty-state">
									<span class="dashicons dashicons-email-alt abfem-empty-icon"></span>
									<h3><?php esc_html_e( 'No form submissions found', 'ab-form-entries-manager' ); ?></h3>
									<p class="abfem-empty-desc"><?php esc_html_e( 'When visitors submit forms on your site, entries will automatically appear here.', 'ab-form-entries-manager' ); ?></p>
									<?php if ( ! empty( $this->query_args['search'] ) || ! empty( $this->query_args['source_plugin'] ) || ! empty( $this->query_args['form_id'] ) ) : ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ); ?>" class="button button-secondary">
											<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Clear All Filters', 'ab-form-entries-manager' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $entries as $entry ) :
							$view_url = add_query_arg( array_merge( array( 'page' => 'abfem-submissions', 'action' => 'view', 'entry_id' => $entry['id'] ), $filter_params ), admin_url( 'admin.php' ) );
							$toggle_status_url = wp_nonce_url(
								add_query_arg( array_merge( array( 'action' => 'abfem_toggle_read', 'entry_id' => $entry['id'], 'is_read' => $entry['is_read'] ? 0 : 1 ), $filter_params ), admin_url( 'admin-post.php' ) ),
								'abfem_toggle_read_' . $entry['id']
							);
							$delete_url = wp_nonce_url(
								add_query_arg( array_merge( array( 'action' => 'abfem_delete_entry', 'entry_id' => $entry['id'] ), $filter_params ), admin_url( 'admin-post.php' ) ),
								'abfem_delete_entry_' . $entry['id']
							);
							$export_single_url = wp_nonce_url(
								add_query_arg( array( 'action' => 'abfem_export_single', 'entry_id' => $entry['id'] ), admin_url( 'admin-post.php' ) ),
								'abfem_export_single_' . $entry['id']
							);

							$row_class = $entry['is_read'] ? 'abfem-entry-read' : 'abfem-entry-unread';
							?>
							<tr class="<?php echo esc_attr( $row_class ); ?>">
								<th scope="row" class="check-column">
									<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $entry['id'] ); ?>"><?php esc_html_e( 'Select entry', 'ab-form-entries-manager' ); ?></label>
									<input id="cb-select-<?php echo esc_attr( $entry['id'] ); ?>" type="checkbox" name="entry_ids[]" value="<?php echo esc_attr( $entry['id'] ); ?>" />
								</th>

								<td class="column-id">
									<a href="<?php echo esc_url( $view_url ); ?>" class="abfem-entry-link">
										<strong>#<?php echo esc_html( $entry['id'] ); ?></strong>
									</a>
								</td>

								<td class="column-submitter">
									<strong>
										<a href="<?php echo esc_url( $view_url ); ?>" class="row-title">
											<?php echo esc_html( $entry['submitter_name'] ?: __( 'Anonymous', 'ab-form-entries-manager' ) ); ?>
										</a>
									</strong>
									<div class="row-actions">
										<span class="view"><a href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View Details', 'ab-form-entries-manager' ); ?></a> | </span>
										<span class="toggle-read">
											<a href="<?php echo esc_url( $toggle_status_url ); ?>">
												<?php echo $entry['is_read'] ? esc_html__( 'Mark as Unread', 'ab-form-entries-manager' ) : esc_html__( 'Mark as Read', 'ab-form-entries-manager' ); ?>
											</a> | 
										</span>
										<span class="export"><a href="<?php echo esc_url( $export_single_url ); ?>"><?php esc_html_e( 'Export CSV', 'ab-form-entries-manager' ); ?></a> | </span>
										<span class="trash"><a href="<?php echo esc_url( $delete_url ); ?>" class="abfem-delete-link" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this submission?', 'ab-form-entries-manager' ) ); ?>');"><?php esc_html_e( 'Delete', 'ab-form-entries-manager' ); ?></a></span>
									</div>
								</td>

								<td class="column-email">
									<?php if ( ! empty( $entry['submitter_email'] ) ) : ?>
										<a href="mailto:<?php echo esc_attr( $entry['submitter_email'] ); ?>" class="abfem-email-link">
											<span class="dashicons dashicons-email"></span> <?php echo esc_html( $entry['submitter_email'] ); ?>
										</a>
									<?php else : ?>
										<span class="abfem-muted">&mdash;</span>
									<?php endif; ?>
								</td>

								<td class="column-form">
									<span class="abfem-form-name"><?php echo esc_html( $entry['form_name'] ); ?></span>
									<?php if ( ! empty( $entry['form_id'] ) ) : ?>
										<span class="abfem-muted">(#<?php echo esc_html( $entry['form_id'] ); ?>)</span>
									<?php endif; ?>
								</td>

								<td class="column-plugin">
									<span class="abfem-badge abfem-badge-<?php echo esc_attr( $entry['source_plugin'] ); ?>">
										<?php echo esc_html( $entry['source_plugin_name'] ?: ucfirst( $entry['source_plugin'] ) ); ?>
									</span>
								</td>

								<td class="column-date">
									<?php
									$time_gmt = strtotime( $entry['date_gmt'] );
									$human_diff = human_time_diff( $time_gmt, time() );
									$formatted_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['date'] ) );
									?>
									<span title="<?php echo esc_attr( $formatted_date ); ?>">
										<?php echo esc_html( $formatted_date ); ?>
									</span>
									<?php /* translators: %s: Human-readable relative time difference (e.g. 2 hours) */ ?>
									<span class="abfem-time-diff abfem-muted">(<?php echo esc_html( sprintf( __( '%s ago', 'ab-form-entries-manager' ), $human_diff ) ); ?>)</span>
								</td>

								<td class="column-status">
									<?php if ( $entry['is_read'] ) : ?>
										<span class="abfem-status-pill abfem-status-read"><?php esc_html_e( 'Read', 'ab-form-entries-manager' ); ?></span>
									<?php else : ?>
										<span class="abfem-status-pill abfem-status-unread"><?php esc_html_e( 'Unread', 'ab-form-entries-manager' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>

				<tfoot>
					<tr>
						<td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox" /></td>
						<th scope="col" class="<?php echo esc_attr( $id_th_class ); ?>">
							<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'id', 'order' => $id_next_order ), $base_url ) ); ?>">
								<span><?php esc_html_e( 'ID', 'ab-form-entries-manager' ); ?></span>
								<span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
							</a>
						</th>
						<th scope="col" class="manage-column column-submitter"><?php esc_html_e( 'Submitter', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="manage-column column-email"><?php esc_html_e( 'Email', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="manage-column column-form"><?php esc_html_e( 'Form', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="manage-column column-plugin"><?php esc_html_e( 'Source Plugin', 'ab-form-entries-manager' ); ?></th>
						<th scope="col" class="<?php echo esc_attr( $date_th_class ); ?>">
							<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'date', 'order' => $date_next_order ), $base_url ) ); ?>">
								<span><?php esc_html_e( 'Date Submitted', 'ab-form-entries-manager' ); ?></span>
								<span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>
							</a>
						</th>
						<th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'ab-form-entries-manager' ); ?></th>
					</tr>
				</tfoot>
			</table>

			<div class="tablenav bottom">
				<div class="alignleft actions bulkactions">
					<select name="bulk_action2" id="bulk-action-selector-bottom">
						<option value="-1"><?php esc_html_e( 'Bulk actions', 'ab-form-entries-manager' ); ?></option>
						<option value="mark_read"><?php esc_html_e( 'Mark as Read', 'ab-form-entries-manager' ); ?></option>
						<option value="mark_unread"><?php esc_html_e( 'Mark as Unread', 'ab-form-entries-manager' ); ?></option>
						<option value="export_csv"><?php esc_html_e( 'Export to CSV', 'ab-form-entries-manager' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'ab-form-entries-manager' ); ?></option>
					</select>
					<input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply', 'ab-form-entries-manager' ); ?>" />
				</div>
			</div>
		</form>
		<?php
	}
}
