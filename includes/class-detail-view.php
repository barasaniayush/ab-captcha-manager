<?php
/**
 * Single submission detail view renderer.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Detail_View
 */
class ABFEM_Detail_View {

	/**
	 * Render the full submission detail view.
	 *
	 * @param int   $entry_id      Entry ID.
	 * @param array $filter_params Active filter parameters to preserve on back button / navigation.
	 */
	public static function render( $entry_id, array $filter_params = array() ) {
		$entry = ABFEM_Storage::get_entry( $entry_id );

		if ( ! $entry ) {
			?>
			<div class="wrap abfem-wrap">
				<div class="notice notice-error"><p><?php esc_html_e( 'Submission not found or has been deleted.', 'ab-form-entries-manager' ); ?></p></div>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ); ?>" class="button">&larr; <?php esc_html_e( 'Back to Submissions', 'ab-form-entries-manager' ); ?></a></p>
			</div>
			<?php
			return;
		}

		// Auto mark as read on view if setting enabled, unless the user just explicitly toggled this entry to unread.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$just_marked_unread = isset( $_GET['abfem_notice'] ) && 'unread_marked' === sanitize_key( wp_unslash( $_GET['abfem_notice'] ) );
		if ( ! $entry['is_read'] && ! $just_marked_unread && ABFEM_Settings::get( 'auto_mark_read_on_view', 1 ) ) {
			ABFEM_Storage::update_read_status( $entry_id, 1 );
			$entry['is_read'] = 1;
		}

		// Previous and Next entry IDs respecting active filters.
		$prev_id = ABFEM_Storage::get_adjacent_entry_id( $entry_id, 'prev', $filter_params );
		$next_id = ABFEM_Storage::get_adjacent_entry_id( $entry_id, 'next', $filter_params );

		$back_url = add_query_arg( array_merge( array( 'page' => 'abfem-submissions' ), $filter_params ), admin_url( 'admin.php' ) );

		$toggle_read_url = wp_nonce_url(
			add_query_arg( array_merge( array( 'action' => 'abfem_toggle_read', 'entry_id' => $entry_id, 'is_read' => $entry['is_read'] ? 0 : 1, 'redirect' => 'detail' ), $filter_params ), admin_url( 'admin-post.php' ) ),
			'abfem_toggle_read_' . $entry_id
		);

		$delete_url = wp_nonce_url(
			add_query_arg( array_merge( array( 'action' => 'abfem_delete_entry', 'entry_id' => $entry_id, 'redirect' => 'list' ), $filter_params ), admin_url( 'admin-post.php' ) ),
			'abfem_delete_entry_' . $entry_id
		);

		$export_url = wp_nonce_url(
			add_query_arg( array( 'action' => 'abfem_export_single', 'entry_id' => $entry_id ), admin_url( 'admin-post.php' ) ),
			'abfem_export_single_' . $entry_id
		);

		$formatted_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['date'] ) );
		$human_diff     = human_time_diff( strtotime( $entry['date_gmt'] ), time() );
		?>
		<div class="wrap abfem-wrap abfem-detail-wrap">
			<!-- Header and breadcrumb -->
			<div class="abfem-detail-top-nav">
				<a href="<?php echo esc_url( $back_url ); ?>" class="abfem-back-btn button button-secondary">
					<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Submissions', 'ab-form-entries-manager' ); ?>
				</a>

				<div class="abfem-prev-next-nav">
					<?php if ( $prev_id ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( array( 'page' => 'abfem-submissions', 'action' => 'view', 'entry_id' => $prev_id ), $filter_params ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary" title="<?php esc_attr_e( 'Previous Submission', 'ab-form-entries-manager' ); ?>">
							&larr; <?php esc_html_e( 'Previous', 'ab-form-entries-manager' ); ?>
						</a>
					<?php else : ?>
						<button class="button button-secondary" disabled>&larr; <?php esc_html_e( 'Previous', 'ab-form-entries-manager' ); ?></button>
					<?php endif; ?>

					<?php if ( $next_id ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( array( 'page' => 'abfem-submissions', 'action' => 'view', 'entry_id' => $next_id ), $filter_params ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary" title="<?php esc_attr_e( 'Next Submission', 'ab-form-entries-manager' ); ?>">
							<?php esc_html_e( 'Next', 'ab-form-entries-manager' ); ?> &rarr;
						</a>
					<?php else : ?>
						<button class="button button-secondary" disabled><?php esc_html_e( 'Next', 'ab-form-entries-manager' ); ?> &rarr;</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="abfem-detail-header-card">
				<div class="abfem-detail-title-area">
					<h1 class="abfem-header-title">
						<?php
						/* translators: %d: Entry ID */
						printf( esc_html__( 'Submission #%d', 'ab-form-entries-manager' ), esc_html( $entry_id ) );
						?>
					</h1>
					<span class="abfem-badge abfem-badge-<?php echo esc_attr( $entry['source_plugin'] ); ?>">
						<?php echo esc_html( $entry['source_plugin_name'] ?: ucfirst( $entry['source_plugin'] ) ); ?>
					</span>
					<?php if ( $entry['is_read'] ) : ?>
						<span class="abfem-status-pill abfem-status-read"><?php esc_html_e( 'Read', 'ab-form-entries-manager' ); ?></span>
					<?php else : ?>
						<span class="abfem-status-pill abfem-status-unread"><?php esc_html_e( 'Unread', 'ab-form-entries-manager' ); ?></span>
					<?php endif; ?>
				</div>

				<div class="abfem-detail-actions">
					<a href="<?php echo esc_url( $toggle_read_url ); ?>" class="button button-secondary">
						<span class="dashicons <?php echo $entry['is_read'] ? 'dashicons-visibility' : 'dashicons-yes-alt'; ?>"></span>
						<?php echo $entry['is_read'] ? esc_html__( 'Mark as Unread', 'ab-form-entries-manager' ) : esc_html__( 'Mark as Read', 'ab-form-entries-manager' ); ?>
					</a>

					<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ab-form-entries-manager' ); ?>
					</a>

					<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this submission?', 'ab-form-entries-manager' ) ); ?>');">
						<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete Entry', 'ab-form-entries-manager' ); ?>
					</a>
				</div>
			</div>
			<hr class="wp-header-end">

			<div class="abfem-detail-layout">
				<!-- Left Column: Form Fields -->
				<div class="abfem-detail-main">
					<div class="abfem-card">
						<div class="abfem-card-header">
							<h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Captured Form Fields', 'ab-form-entries-manager' ); ?></h2>
							<span class="abfem-card-count">
								<?php
								$field_count = count( $entry['fields'] );
								/* translators: %d: Field count */
								printf( esc_html( _n( '%d Field', '%d Fields', $field_count, 'ab-form-entries-manager' ) ), esc_html( $field_count ) );
								?>
							</span>
						</div>
						<div class="abfem-card-body abfem-fields-container">
							<?php if ( empty( $entry['fields'] ) ) : ?>
								<p class="abfem-muted"><?php esc_html_e( 'No field data was captured for this entry.', 'ab-form-entries-manager' ); ?></p>
							<?php else : ?>
								<table class="widefat striped abfem-fields-table">
									<thead>
										<tr>
											<th style="width: 25%;"><?php esc_html_e( 'Field Label', 'ab-form-entries-manager' ); ?></th>
											<th style="width: 20%;"><?php esc_html_e( 'Key / Type', 'ab-form-entries-manager' ); ?></th>
											<th style="width: 55%;"><?php esc_html_e( 'Submitted Value', 'ab-form-entries-manager' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ( $entry['fields'] as $key => $field ) :
											$field_item = is_array( $field ) ? $field : array( 'value' => $field );
											$label      = ! empty( $field_item['label'] ) ? $field_item['label'] : ( is_string( $key ) ? ucwords( str_replace( array( '_', '-' ), ' ', $key ) ) : ( ! empty( $field_item['name'] ) ? $field_item['name'] : __( 'Field', 'ab-form-entries-manager' ) ) );
											$name       = ! empty( $field_item['name'] ) ? $field_item['name'] : ( is_string( $key ) ? $key : '' );
											$type       = ! empty( $field_item['type'] ) ? $field_item['type'] : 'text';
											$field_item['label'] = $label;
											$field_item['name']  = $name;
											$field_item['type']  = $type;
											?>
											<tr>
												<td class="abfem-field-label">
													<strong><?php echo esc_html( $label ); ?></strong>
												</td>
												<td class="abfem-field-meta">
													<?php if ( ! empty( $name ) ) : ?>
														<code><?php echo esc_html( $name ); ?></code>
													<?php endif; ?>
													<?php if ( ! empty( $type ) ) : ?>
														<span class="abfem-field-type-tag"><?php echo esc_html( $type ); ?></span>
													<?php endif; ?>
												</td>
												<td class="abfem-field-val">
													<?php self::render_field_value( $field_item ); ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Right Column: Submission Overview / Metadata -->
				<div class="abfem-detail-sidebar">
					<div class="abfem-card">
						<div class="abfem-card-header">
							<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Submission Overview', 'ab-form-entries-manager' ); ?></h3>
						</div>
						<div class="abfem-card-body abfem-overview-list">
							<div class="abfem-overview-item">
								<span class="abfem-label"><?php esc_html_e( 'Form Name:', 'ab-form-entries-manager' ); ?></span>
								<span class="abfem-value"><strong><?php echo esc_html( $entry['form_name'] ); ?></strong></span>
							</div>

							<div class="abfem-overview-item">
								<span class="abfem-label"><?php esc_html_e( 'Form ID:', 'ab-form-entries-manager' ); ?></span>
								<span class="abfem-value"><code><?php echo esc_html( $entry['form_id'] ?: '&mdash;' ); ?></code></span>
							</div>

							<div class="abfem-overview-item">
								<span class="abfem-label"><?php esc_html_e( 'Source Builder:', 'ab-form-entries-manager' ); ?></span>
								<span class="abfem-value"><?php echo esc_html( $entry['source_plugin_name'] ?: ucfirst( $entry['source_plugin'] ) ); ?></span>
							</div>

							<?php if ( ! empty( $entry['source_entry_id'] ) ) : ?>
								<div class="abfem-overview-item">
									<span class="abfem-label"><?php esc_html_e( 'Original Entry ID:', 'ab-form-entries-manager' ); ?></span>
									<span class="abfem-value"><code>#<?php echo esc_html( $entry['source_entry_id'] ); ?></code></span>
								</div>
							<?php endif; ?>

							<div class="abfem-overview-item">
								<span class="abfem-label"><?php esc_html_e( 'Submitted At:', 'ab-form-entries-manager' ); ?></span>
								<span class="abfem-value">
									<?php echo esc_html( $formatted_date ); ?>
									<?php /* translators: %s: Relative time elapsed (e.g. 5 minutes) */ ?>
									<div class="abfem-muted">(<?php echo esc_html( sprintf( __( '%s ago', 'ab-form-entries-manager' ), $human_diff ) ); ?>)</div>
								</span>
							</div>

							<div class="abfem-overview-item">
								<span class="abfem-label"><?php esc_html_e( 'Submitter Name:', 'ab-form-entries-manager' ); ?></span>
								<span class="abfem-value"><?php echo esc_html( $entry['submitter_name'] ?: __( 'Not specified', 'ab-form-entries-manager' ) ); ?></span>
							</div>

							<div class="abfem-overview-item">
								<span class="abfem-label"><?php esc_html_e( 'Submitter Email:', 'ab-form-entries-manager' ); ?></span>
								<span class="abfem-value">
									<?php if ( ! empty( $entry['submitter_email'] ) ) : ?>
										<a href="mailto:<?php echo esc_attr( $entry['submitter_email'] ); ?>">
											<?php echo esc_html( $entry['submitter_email'] ); ?>
										</a>
									<?php else : ?>
										<span class="abfem-muted">&mdash;</span>
									<?php endif; ?>
								</span>
							</div>

							<?php if ( ! empty( $entry['source_url'] ) ) : ?>
								<div class="abfem-overview-item">
									<span class="abfem-label"><?php esc_html_e( 'Submitted From:', 'ab-form-entries-manager' ); ?></span>
									<span class="abfem-value">
										<a href="<?php echo esc_url( $entry['source_url'] ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $entry['source_url'] ); ?> <span class="dashicons dashicons-external"></span>
										</a>
									</span>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $entry['submitter_ip'] ) ) : ?>
								<div class="abfem-overview-item">
									<span class="abfem-label"><?php esc_html_e( 'IP Address:', 'ab-form-entries-manager' ); ?></span>
									<span class="abfem-value"><code><?php echo esc_html( $entry['submitter_ip'] ); ?></code></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render captured field value safely and cleanly.
	 *
	 * @param array $field Normalized field item.
	 */
	public static function render_field_value( array $field ) {
		$val  = $field['value'] ?? '';
		$type = $field['type'] ?? 'text';

		if ( '' === $val || null === $val ) {
			echo '<span class="abfem-muted">&mdash;</span>';
			return;
		}

		if ( is_array( $val ) ) {
			echo '<ul class="abfem-array-list">';
			foreach ( $val as $item ) {
				echo '<li>';
				if ( is_array( $item ) ) {
					echo esc_html( wp_json_encode( $item ) );
				} else {
					echo esc_html( (string) $item );
				}
				echo '</li>';
			}
			echo '</ul>';
			return;
		}

		// URL field.
		if ( 'url' === $type || ( is_string( $val ) && filter_var( $val, FILTER_VALIDATE_URL ) ) ) {
			$url = esc_url( $val );
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $val ) . ' <span class="dashicons dashicons-external"></span></a>';
			return;
		}

		// Email field.
		if ( 'email' === $type || is_email( (string) $val ) ) {
			$email = sanitize_email( $val );
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			return;
		}

		// File upload.
		if ( 'file' === $type ) {
			echo '<span class="dashicons dashicons-media-default"></span> ' . esc_html( (string) $val );
			return;
		}

		// Multiline textarea.
		if ( 'textarea' === $type || false !== strpos( (string) $val, "\n" ) ) {
			echo '<div class="abfem-multiline-value">' . nl2br( esc_html( (string) $val ) ) . '</div>';
			return;
		}

		// Default scalar text.
		echo esc_html( (string) $val );
	}
}
