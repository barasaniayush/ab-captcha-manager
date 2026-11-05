<?php
/**
 * Admin Dashboard Widget (Metabox) view template for /wp-admin/index.php.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$submissions_url = admin_url( 'admin.php?page=abfem-submissions' );
$unread_url      = admin_url( 'admin.php?page=abfem-submissions&status=unread' );
$forms_url        = admin_url( 'admin.php?page=abfem-forms' );
$export_url       = admin_url( 'admin.php?page=abfem-export' );
$settings_url     = admin_url( 'admin.php?page=abfem-settings' );
?>
<div class="abfem-dashboard-widget">

	<!-- Overview Metrics Grid -->
	<div class="abfem-dw-stats-grid">
		<div class="abfem-dw-stat-box">
			<span class="abfem-dw-stat-num"><?php echo esc_html( number_format_i18n( $counts['total'] ?? 0 ) ); ?></span>
			<span class="abfem-dw-stat-label"><?php esc_html_e( 'Total Entries', 'ab-form-entries-manager' ); ?></span>
		</div>

		<div class="abfem-dw-stat-box abfem-dw-stat-unread <?php echo ( ! empty( $counts['unread'] ) && $counts['unread'] > 0 ) ? 'has-unread' : ''; ?>">
			<span class="abfem-dw-stat-num"><?php echo esc_html( number_format_i18n( $counts['unread'] ?? 0 ) ); ?></span>
			<span class="abfem-dw-stat-label"><?php esc_html_e( 'Unread', 'ab-form-entries-manager' ); ?></span>
		</div>

		<div class="abfem-dw-stat-box abfem-dw-stat-today">
			<span class="abfem-dw-stat-num"><?php echo esc_html( number_format_i18n( $counts['today'] ?? 0 ) ); ?></span>
			<span class="abfem-dw-stat-label"><?php esc_html_e( 'Today', 'ab-form-entries-manager' ); ?></span>
		</div>

		<div class="abfem-dw-stat-box">
			<span class="abfem-dw-stat-num"><?php echo esc_html( number_format_i18n( $active_builder_count ?? 0 ) ); ?></span>
			<span class="abfem-dw-stat-label"><?php esc_html_e( 'Active Builders', 'ab-form-entries-manager' ); ?></span>
		</div>
	</div>

	<?php if ( ! empty( $counts['unread'] ) && $counts['unread'] > 0 ) : ?>
		<!-- Unread Submissions Alert Bar -->
		<div class="abfem-dw-unread-alert">
			<span class="dashicons dashicons-bell"></span>
			<div>
				<strong>
					<?php
					printf(
						/* translators: %d: count of unread submissions */
						esc_html( _n( '%d new unread submission requires attention.', '%d new unread submissions require attention.', (int) $counts['unread'], 'ab-form-entries-manager' ) ),
						(int) $counts['unread']
					);
					?>
				</strong>
				<a href="<?php echo esc_url( $unread_url ); ?>" class="abfem-dw-alert-link">
					<?php esc_html_e( 'Filter Unread &rarr;', 'ab-form-entries-manager' ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>

	<!-- Recent Submissions List Section -->
	<div class="abfem-dw-section-header">
		<h4><span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Recent Submissions', 'ab-form-entries-manager' ); ?></h4>
		<?php if ( ! empty( $recent_entries ) ) : ?>
			<a href="<?php echo esc_url( $submissions_url ); ?>" class="abfem-dw-view-all-link">
				<?php esc_html_e( 'View All &rarr;', 'ab-form-entries-manager' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $recent_entries ) ) : ?>
		<ul class="abfem-dw-recent-list">
			<?php foreach ( $recent_entries as $entry ) :
				$entry_id   = (int) $entry['id'];
				$view_url   = admin_url( 'admin.php?page=abfem-submissions&action=view&entry_id=' . $entry_id );
				$is_unread  = empty( $entry['is_read'] );
				$plugin_id  = sanitize_html_class( $entry['source_plugin'] ?? 'custom' );
				$builder    = ! empty( $entry['source_plugin_name'] ) ? $entry['source_plugin_name'] : __( 'Form', 'ab-form-entries-manager' );
				$badge_abbr = substr( $builder, 0, 2 );
				$name       = ! empty( $entry['submitter_name'] ) ? $entry['submitter_name'] : __( 'Guest / Anonymous', 'ab-form-entries-manager' );
				$form_name  = ! empty( $entry['form_name'] ) ? $entry['form_name'] : $builder;
				$time_diff  = human_time_diff( strtotime( $entry['date'] ), current_time( 'timestamp' ) );
			?>
				<li class="abfem-dw-recent-item <?php echo $is_unread ? 'is-unread' : 'is-read'; ?>">
					<div class="abfem-dw-item-main">
						<div class="abfem-dw-item-top">
							<span class="abfem-integration-badge abfem-badge-<?php echo esc_attr( $plugin_id ); ?>" title="<?php echo esc_attr( $builder ); ?>">
								<?php echo esc_html( $badge_abbr ); ?>
							</span>
							<a href="<?php echo esc_url( $view_url ); ?>" class="abfem-dw-submitter-name">
								<strong><?php echo esc_html( $name ); ?></strong>
							</a>
							<?php if ( $is_unread ) : ?>
								<span class="abfem-status-tag abfem-status-unread"><?php esc_html_e( 'New', 'ab-form-entries-manager' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="abfem-dw-item-meta">
							<?php if ( ! empty( $entry['submitter_email'] ) ) : ?>
								<span class="abfem-dw-meta-email">
									<span class="dashicons dashicons-email"></span> <?php echo esc_html( $entry['submitter_email'] ); ?>
								</span>
							<?php endif; ?>
							<span class="abfem-dw-meta-form">
								<span class="dashicons dashicons-feedback"></span> <?php echo esc_html( $form_name ); ?>
							</span>
							<span class="abfem-dw-meta-time">
								<span class="dashicons dashicons-clock"></span>
								<?php
								/* translators: %s: Relative human-readable time difference (e.g. 5 minutes) */
								echo esc_html( sprintf( __( '%s ago', 'ab-form-entries-manager' ), $time_diff ) );
								?>
							</span>
						</div>
					</div>
					<div class="abfem-dw-item-action">
						<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small button-secondary">
							<?php esc_html_e( 'View', 'ab-form-entries-manager' ); ?>
						</a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<div class="abfem-dw-empty">
			<span class="dashicons dashicons-feedback"></span>
			<h4><?php esc_html_e( 'No form entries captured yet', 'ab-form-entries-manager' ); ?></h4>
			<p><?php esc_html_e( 'When visitors submit any form on your site, entries will automatically appear here with full field details.', 'ab-form-entries-manager' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Footer Quick Navigation -->
	<div class="abfem-dw-footer">
		<div class="abfem-dw-footer-links">
			<div class="abfem-dw-footer-primary">
				<a href="<?php echo esc_url( $submissions_url ); ?>" class="abfem-dw-btn abfem-dw-btn-primary button button-primary button-small">
					<span class="dashicons dashicons-feedback" aria-hidden="true"></span>
					<span class="abfem-dw-btn-text"><?php esc_html_e( 'All Submissions', 'ab-form-entries-manager' ); ?></span>
				</a>
			</div>
			<div class="abfem-dw-footer-secondary">
				<a href="<?php echo esc_url( $forms_url ); ?>" class="abfem-dw-btn abfem-dw-btn-secondary button button-secondary button-small">
					<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
					<span class="abfem-dw-btn-text"><?php esc_html_e( 'Forms', 'ab-form-entries-manager' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $export_url ); ?>" class="abfem-dw-btn abfem-dw-btn-secondary button button-secondary button-small">
					<span class="dashicons dashicons-download" aria-hidden="true"></span>
					<span class="abfem-dw-btn-text"><?php esc_html_e( 'Export CSV', 'ab-form-entries-manager' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="abfem-dw-btn abfem-dw-btn-secondary button button-secondary button-small">
					<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
					<span class="abfem-dw-btn-text"><?php esc_html_e( 'Settings', 'ab-form-entries-manager' ); ?></span>
				</a>
			</div>
		</div>
	</div>

</div>
