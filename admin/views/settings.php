<?php
/**
 * Modern Settings view template.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

global $wpdb;

// Determine active tab from URL or default to 'general'.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
$valid_tabs  = array( 'general', 'integrations', 'privacy', 'security', 'notifications', 'guide', 'status' );
if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
	$current_tab = 'general';
}

$active_builder_count = 0;
foreach ( $integrations as $int_id => $int ) {
	if ( $int->is_active() && ABFEM_Settings::is_integration_enabled( $int_id ) ) {
		$active_builder_count++;
	}
}
?>
<div class="wrap abfem-wrap abfem-settings-wrap">

	<!-- Modern Brand Header -->
	<div class="abfem-modern-header">
		<div class="abfem-header-left">
			<div class="abfem-logo-icon">
				<span class="dashicons dashicons-admin-settings"></span>
			</div>
			<div class="abfem-header-title-box">
				<div class="abfem-title-row">
					<h1 class="abfem-header-title"><?php esc_html_e( 'Plugin Settings &amp; Preferences', 'ab-form-entries-manager' ); ?></h1>
					<span class="abfem-version-pill"><?php echo esc_html( 'v' . ABFEM_VERSION ); ?></span>
				</div>
				<p class="abfem-subtitle"><?php esc_html_e( 'Configure capture preferences, builder integrations, data privacy, and notification settings.', 'ab-form-entries-manager' ); ?></p>
			</div>
		</div>

		<div class="abfem-header-actions">
			<span class="abfem-status-badge <?php echo esc_attr( $active_builder_count > 0 ? 'abfem-badge-success' : 'abfem-badge-warning' ); ?>">
				<span class="abfem-dot"></span>
				<?php
				/* translators: %d: count of connected builders */
				echo esc_html( sprintf( _n( '%d Form Builder Connected', '%d Form Builders Connected', (int) $active_builder_count, 'ab-form-entries-manager' ), (int) $active_builder_count ) );
				?>
			</span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-feedback"></span> <?php esc_html_e( 'Submissions', 'ab-form-entries-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-export' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ab-form-entries-manager' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<!-- Navigation Tabs -->
	<nav class="abfem-tabs-nav">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=general' ) ); ?>" class="abfem-tab-btn <?php echo 'general' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="general">
			<span class="dashicons dashicons-admin-generic"></span>
			<span><?php esc_html_e( 'General', 'ab-form-entries-manager' ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=integrations' ) ); ?>" class="abfem-tab-btn <?php echo 'integrations' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="integrations">
			<span class="dashicons dashicons-networking"></span>
			<span><?php esc_html_e( 'Form Integrations', 'ab-form-entries-manager' ); ?></span>
			<span class="abfem-tab-count"><?php echo esc_html( count( $integrations ) ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=privacy' ) ); ?>" class="abfem-tab-btn <?php echo 'privacy' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="privacy">
			<span class="dashicons dashicons-shield"></span>
			<span><?php esc_html_e( 'Privacy &amp; GDPR', 'ab-form-entries-manager' ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=security' ) ); ?>" class="abfem-tab-btn <?php echo 'security' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="security">
			<span class="dashicons dashicons-lock"></span>
			<span><?php esc_html_e( 'Field Security', 'ab-form-entries-manager' ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=notifications' ) ); ?>" class="abfem-tab-btn <?php echo 'notifications' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="notifications">
			<span class="dashicons dashicons-email"></span>
			<span><?php esc_html_e( 'Notifications', 'ab-form-entries-manager' ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=guide' ) ); ?>" class="abfem-tab-btn <?php echo 'guide' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="guide">
			<span class="dashicons dashicons-book-alt"></span>
			<span><?php esc_html_e( 'Instructions &amp; Guide', 'ab-form-entries-manager' ); ?></span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=status' ) ); ?>" class="abfem-tab-btn <?php echo 'status' === $current_tab ? 'abfem-tab-active' : ''; ?>" data-tab="status">
			<span class="dashicons dashicons-admin-tools"></span>
			<span><?php esc_html_e( 'System Status &amp; Tools', 'ab-form-entries-manager' ); ?></span>
		</a>
	</nav>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="abfem-settings-form" id="abfem-settings-form">
		<input type="hidden" name="action" value="abfem_save_settings" />
		<input type="hidden" name="active_tab" id="abfem-active-tab-input" value="<?php echo esc_attr( $current_tab ); ?>" />
		<?php wp_nonce_field( 'abfem_settings_nonce', 'nonce' ); ?>

		<!-- ========================================== -->
		<!-- TAB 1: GENERAL SETTINGS                    -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'general' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-general">
			<div class="abfem-card">
				<div class="abfem-card-header">
					<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Display &amp; Dashboard Preferences', 'ab-form-entries-manager' ); ?></h2>
					<p class="abfem-card-hint"><?php esc_html_e( 'Customize how submissions appear in your WordPress administration area.', 'ab-form-entries-manager' ); ?></p>
				</div>
				<div class="abfem-card-body">
					<table class="form-table abfem-form-table">
						<tr>
							<th scope="row">
								<label for="entries_per_page"><?php esc_html_e( 'Submissions Per Page', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Pagination size on the submissions table (5 to 200).', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<div class="abfem-input-with-unit">
									<input type="number" name="settings[entries_per_page]" id="entries_per_page" min="5" max="200" step="5" value="<?php echo esc_attr( $settings['entries_per_page'] ); ?>" class="small-text abfem-number-input" />
									<span class="abfem-unit"><?php esc_html_e( 'entries', 'ab-form-entries-manager' ); ?></span>
								</div>
								<p class="description"><?php esc_html_e( 'Default is 20 entries per page. Lower numbers load faster on large databases.', 'ab-form-entries-manager' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="date_format"><?php esc_html_e( 'Date &amp; Time Display Format', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Controls how timestamps are formatted across the plugin.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<div class="abfem-select-wrapper">
									<select name="settings[date_format]" id="date_format" class="abfem-select">
										<option value="wp" <?php selected( $settings['date_format'], 'wp' ); ?>>
											<?php
											/* translators: %s: formatted current time */
											printf( esc_html__( 'WordPress Default (%s)', 'ab-form-entries-manager' ), esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) );
											?>
										</option>
										<option value="iso" <?php selected( $settings['date_format'], 'iso' ); ?>>
											<?php
											/* translators: %s: formatted current time */
											printf( esc_html__( 'ISO 8601 Standard (%s)', 'ab-form-entries-manager' ), esc_html( gmdate( 'Y-m-d H:i:s' ) ) );
											?>
										</option>
										<option value="human" <?php selected( $settings['date_format'], 'human' ); ?>>
											<?php esc_html_e( 'Relative Human Time (e.g. 5 minutes ago, 2 hours ago)', 'ab-form-entries-manager' ); ?>
										</option>
									</select>
								</div>
								<div class="abfem-live-preview-box">
									<span class="dashicons dashicons-clock"></span>
									<span class="abfem-preview-label"><?php esc_html_e( 'Live Preview:', 'ab-form-entries-manager' ); ?></span>
									<code id="abfem-date-preview"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></code>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="auto_mark_read_on_view"><?php esc_html_e( 'Read / Unread Behavior', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Automatic status update upon entry review.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[auto_mark_read_on_view]" value="0" />
								<label class="abfem-switch-label" for="auto_mark_read_on_view">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[auto_mark_read_on_view]" id="auto_mark_read_on_view" value="1" <?php checked( $settings['auto_mark_read_on_view'], 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Automatically mark submissions as read when opened', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Opening an unread entry\'s detail view will automatically update its status to "Read".', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="menu_unread_badge"><?php esc_html_e( 'Menu Unread Badge', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Sidebar notification indicator.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[menu_unread_badge]" value="0" />
								<label class="abfem-switch-label" for="menu_unread_badge">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[menu_unread_badge]" id="menu_unread_badge" value="1" <?php checked( $settings['menu_unread_badge'] ?? 1, 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Show unread counter badge in WordPress admin menu', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Displays a red bubble with the total number of unread submissions beside "AB Form Entries" in your left menu.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="admin_bar_menu"><?php esc_html_e( 'Admin Bar Quick Link', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Top toolbar shortcut menu.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[admin_bar_menu]" value="0" />
								<label class="abfem-switch-label" for="admin_bar_menu">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[admin_bar_menu]" id="admin_bar_menu" value="1" <?php checked( $settings['admin_bar_menu'] ?? 1, 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Display AB Form Entries shortcut in top WordPress Toolbar', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Adds a fast access menu to the top admin bar for administrators with shortcuts to entries, forms, and CSV exports.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="dashboard_widget"><?php esc_html_e( 'Dashboard Overview Widget', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Metabox on WP Admin Dashboard (/wp-admin/index.php).', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[dashboard_widget]" value="0" />
								<label class="abfem-switch-label" for="dashboard_widget">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[dashboard_widget]" id="dashboard_widget" value="1" <?php checked( $settings['dashboard_widget'] ?? 1, 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Show submissions summary widget on admin dashboard', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Displays statistics, recent submission cards, and quick actions directly on your main WordPress dashboard.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="dashboard_notice"><?php esc_html_e( 'Dashboard Submission Alert', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Alert notification on /wp-admin/index.php.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[dashboard_notice]" value="0" />
								<label class="abfem-switch-label" for="dashboard_notice">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[dashboard_notice]" id="dashboard_notice" value="1" <?php checked( $settings['dashboard_notice'] ?? 1, 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Display notification banner when new unread submissions arrive', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Shows a notification banner on the main dashboard with quick links to unread entries. Automatically refreshes when new entries arrive.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<!-- ========================================== -->
		<!-- TAB 2: FORM INTEGRATIONS                   -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'integrations' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-integrations">
			<div class="abfem-card">
				<div class="abfem-card-header">
					<h2><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Supported Form Builder Integrations', 'ab-form-entries-manager' ); ?></h2>
					<p class="abfem-card-hint"><?php esc_html_e( 'Toggle which form builders AB Form Entries Manager should capture submissions from.', 'ab-form-entries-manager' ); ?></p>
				</div>
				<div class="abfem-card-body">
					<div class="abfem-integrations-settings-grid">
						<?php foreach ( $integrations as $int_id => $integration ) :
							$is_installed = $integration->is_installed();
							$is_active    = $integration->is_active();
							$is_enabled   = ABFEM_Settings::is_integration_enabled( $int_id );
							$version      = $integration->get_version();

							// Determine card status.
							if ( $is_active && $is_enabled ) {
								$card_status_class = 'abfem-int-active';
								$badge_text = __( 'Active &amp; Ready', 'ab-form-entries-manager' );
								$badge_class = 'abfem-status-active';
							} elseif ( $is_active && ! $is_enabled ) {
								$card_status_class = 'abfem-int-disabled';
								$badge_text = __( 'Disabled in Settings', 'ab-form-entries-manager' );
								$badge_class = 'abfem-status-disabled';
							} elseif ( $is_installed && ! $is_active ) {
								$card_status_class = 'abfem-int-inactive';
								$badge_text = __( 'Installed (Inactive)', 'ab-form-entries-manager' );
								$badge_class = 'abfem-status-inactive';
							} else {
								$card_status_class = 'abfem-int-notinstalled';
								$badge_text = __( 'Not Installed', 'ab-form-entries-manager' );
								$badge_class = 'abfem-status-notinstalled';
							}

							// Captured entries count.
							$captured_count = ABFEM_Storage::get_entry_count_by_plugin( $int_id );
							?>
							<div class="abfem-integration-box <?php echo esc_attr( $card_status_class ); ?>">
								<div class="abfem-int-box-top">
									<div class="abfem-int-brand">
										<span class="abfem-integration-badge abfem-badge-<?php echo esc_attr( $int_id ); ?>">
											<?php echo esc_html( substr( $integration->get_name(), 0, 2 ) ); ?>
										</span>
										<div class="abfem-int-info">
											<h4><?php echo esc_html( $integration->get_name() ); ?></h4>
											<?php if ( ! empty( $version ) ) : ?>
												<span class="abfem-int-ver"><?php echo esc_html( 'v' . $version ); ?></span>
											<?php endif; ?>
										</div>
									</div>

									<span class="abfem-status-tag <?php echo esc_attr( $badge_class ); ?>">
										<?php echo esc_html( $badge_text ); ?>
									</span>
								</div>

								<p class="abfem-int-desc"><?php echo esc_html( $integration->get_description() ); ?></p>

								<div class="abfem-int-stats">
									<span class="abfem-stat-lbl"><?php esc_html_e( 'Captured Submissions:', 'ab-form-entries-manager' ); ?></span>
									<span class="abfem-stat-val"><?php echo esc_html( number_format_i18n( $captured_count ) ); ?></span>
								</div>

								<div class="abfem-int-toggle-row">
									<label class="abfem-switch-label" for="integration_<?php echo esc_attr( $int_id ); ?>">
										<span class="abfem-switch">
											<input type="checkbox" name="settings[enabled_integrations][<?php echo esc_attr( $int_id ); ?>]" id="integration_<?php echo esc_attr( $int_id ); ?>" value="1" <?php checked( $is_enabled ); ?> />
											<span class="abfem-slider"></span>
										</span>
										<span class="abfem-switch-text">
											<strong><?php esc_html_e( 'Capture submissions', 'ab-form-entries-manager' ); ?></strong>
										</span>
									</label>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- ========================================== -->
		<!-- TAB 3: PRIVACY & GDPR                      -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'privacy' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-privacy">
			<div class="abfem-card">
				<div class="abfem-card-header">
					<h2><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Privacy, GDPR &amp; Data Retention', 'ab-form-entries-manager' ); ?></h2>
					<p class="abfem-card-hint"><?php esc_html_e( 'Configure data protection and automated retention policies.', 'ab-form-entries-manager' ); ?></p>
				</div>
				<div class="abfem-card-body">
					<table class="form-table abfem-form-table">
						<tr>
							<th scope="row">
								<label for="anonymize_ip"><?php esc_html_e( 'IP Address Anonymization', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'GDPR / CCPA compliance setting.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[anonymize_ip]" value="0" />
								<label class="abfem-switch-label" for="anonymize_ip">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[anonymize_ip]" id="anonymize_ip" value="1" <?php checked( $settings['anonymize_ip'], 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Anonymize submitter IP addresses before saving', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Zeros out the last octet of IPv4 addresses (e.g., 192.168.1.0) and masks IPv6 addresses for enhanced privacy.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
								<div class="abfem-guide-callout abfem-callout-info" style="margin-top:12px;">
									<span class="dashicons dashicons-info"></span>
									<div>
										<strong><?php esc_html_e( 'GDPR Compliance Tip:', 'ab-form-entries-manager' ); ?></strong>
										<?php esc_html_e( 'IP addresses are classified as personal data under the EU General Data Protection Regulation (GDPR). Enabling anonymization helps you comply without compromising form functionality.', 'ab-form-entries-manager' ); ?>
									</div>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="retention_days"><?php esc_html_e( 'Automatic Data Retention', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Scheduled deletion of old records.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<div class="abfem-select-wrapper">
									<select name="settings[retention_days]" id="retention_days" class="abfem-select">
										<option value="0" <?php selected( $settings['retention_days'], 0 ); ?>><?php esc_html_e( 'Disabled — Keep entries forever (Default)', 'ab-form-entries-manager' ); ?></option>
										<option value="30" <?php selected( $settings['retention_days'], 30 ); ?>><?php esc_html_e( 'Delete entries older than 30 days', 'ab-form-entries-manager' ); ?></option>
										<option value="60" <?php selected( $settings['retention_days'], 60 ); ?>><?php esc_html_e( 'Delete entries older than 60 days', 'ab-form-entries-manager' ); ?></option>
										<option value="90" <?php selected( $settings['retention_days'], 90 ); ?>><?php esc_html_e( 'Delete entries older than 90 days', 'ab-form-entries-manager' ); ?></option>
										<option value="180" <?php selected( $settings['retention_days'], 180 ); ?>><?php esc_html_e( 'Delete entries older than 180 days', 'ab-form-entries-manager' ); ?></option>
										<option value="365" <?php selected( $settings['retention_days'], 365 ); ?>><?php esc_html_e( 'Delete entries older than 365 days (1 year)', 'ab-form-entries-manager' ); ?></option>
									</select>
								</div>
								<div class="abfem-warning-callout">
									<span class="dashicons dashicons-warning"></span>
									<div>
										<strong><?php esc_html_e( 'Automated Deletion Notice:', 'ab-form-entries-manager' ); ?></strong>
										<?php esc_html_e( 'A background daily cron job permanently purges submissions older than this time frame. Once deleted, records cannot be recovered.', 'ab-form-entries-manager' ); ?>
									</div>
								</div>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label><?php esc_html_e( 'WordPress Core Privacy Tools', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Data export and erasure integration.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<div class="abfem-privacy-features-box">
									<div class="abfem-privacy-feat">
										<span class="dashicons dashicons-yes-alt abfem-icon-green"></span>
										<div>
											<strong><?php esc_html_e( 'Export Personal Data Integrated', 'ab-form-entries-manager' ); ?></strong>
											<p><?php esc_html_e( 'When a user submits a GDPR data access request under WordPress Tools &rarr; Export Personal Data, all their form submissions are compiled into their downloadable personal archive.', 'ab-form-entries-manager' ); ?></p>
										</div>
									</div>
									<div class="abfem-privacy-feat">
										<span class="dashicons dashicons-yes-alt abfem-icon-green"></span>
										<div>
											<strong><?php esc_html_e( 'Erase Personal Data Integrated', 'ab-form-entries-manager' ); ?></strong>
											<p><?php esc_html_e( 'When an erasure request is confirmed under WordPress Tools &rarr; Erase Personal Data, all matching submissions are permanently wiped from the database.', 'ab-form-entries-manager' ); ?></p>
										</div>
									</div>
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<!-- ========================================== -->
		<!-- TAB 4: SECURITY & FIELD EXCLUSION          -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'security' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-security">
			<div class="abfem-card">
				<div class="abfem-card-header">
					<h2><span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Field Security &amp; Data Exclusions', 'ab-form-entries-manager' ); ?></h2>
					<p class="abfem-card-hint"><?php esc_html_e( 'Prevent sensitive or confidential information from ever being saved into your database.', 'ab-form-entries-manager' ); ?></p>
				</div>
				<div class="abfem-card-body">
					<table class="form-table abfem-form-table">
						<tr>
							<th scope="row">
								<label for="excluded_fields"><?php esc_html_e( 'Excluded Sensitive Fields', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Patterns to filter out (one per line).', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<div class="abfem-field-presets-bar">
									<span class="abfem-presets-label"><?php esc_html_e( 'Quick Add Presets:', 'ab-form-entries-manager' ); ?></span>
									<button type="button" class="button button-small abfem-preset-btn" data-preset="password&#10;pass&#10;pwd&#10;user_pass">+ <?php esc_html_e( 'Passwords', 'ab-form-entries-manager' ); ?></button>
									<button type="button" class="button button-small abfem-preset-btn" data-preset="credit_card&#10;cc_number&#10;card_number&#10;cvc&#10;cvv&#10;card_exp">+ <?php esc_html_e( 'Credit Cards', 'ab-form-entries-manager' ); ?></button>
									<button type="button" class="button button-small abfem-preset-btn" data-preset="token&#10;secret&#10;api_key&#10;auth_token">+ <?php esc_html_e( 'Tokens &amp; Keys', 'ab-form-entries-manager' ); ?></button>
									<button type="button" class="button button-small abfem-preset-btn" data-preset="ssn&#10;social_security&#10;tax_id&#10;national_id">+ <?php esc_html_e( 'SSN &amp; National ID', 'ab-form-entries-manager' ); ?></button>
								</div>

								<textarea name="settings[excluded_fields]" id="excluded_fields" rows="7" class="large-text code abfem-code-area"><?php echo esc_textarea( $settings['excluded_fields'] ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Any form field whose name or label contains one of these keywords will be stripped out and never stored in the database. Case-insensitive substring matching is used.', 'ab-form-entries-manager' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="delete_data_on_uninstall"><?php esc_html_e( 'Uninstall Data Policy', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Database cleanup on plugin deletion.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[delete_data_on_uninstall]" value="0" />
								<label class="abfem-switch-label" for="delete_data_on_uninstall">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[delete_data_on_uninstall]" id="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?> />
										<span class="abfem-slider abfem-slider-danger"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Permanently delete all form entries and settings when uninstalling this plugin', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'By default, your submission records are preserved even if the plugin is deleted. Enable this option only if you want a complete database purge when deleting the plugin.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<!-- ========================================== -->
		<!-- TAB 5: NOTIFICATIONS                       -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'notifications' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-notifications">
			<div class="abfem-card">
				<div class="abfem-card-header">
					<h2><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Admin Email Notifications', 'ab-form-entries-manager' ); ?></h2>
					<p class="abfem-card-hint"><?php esc_html_e( 'Receive an instant email summary whenever a new form entry is captured.', 'ab-form-entries-manager' ); ?></p>
				</div>
				<div class="abfem-card-body">
					<table class="form-table abfem-form-table">
						<tr>
							<th scope="row">
								<label for="email_notifications"><?php esc_html_e( 'Email Alerts', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Submission notification trigger.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[email_notifications]" value="0" />
								<label class="abfem-switch-label" for="email_notifications">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[email_notifications]" id="email_notifications" value="1" <?php checked( $settings['email_notifications'] ?? 0, 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Send email notification to admin upon every captured submission', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Dispatches an email containing the submitter\'s name, email, form details, and full field answers.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="notification_email"><?php esc_html_e( 'Recipient Email Address', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Where notifications should be sent.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="email" name="settings[notification_email]" id="notification_email" value="<?php echo esc_attr( $settings['notification_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text abfem-input" placeholder="admin@example.com" />
								<p class="description"><?php esc_html_e( 'Defaults to the WordPress site administrator email. Separate multiple email addresses with commas.', 'ab-form-entries-manager' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="notification_subject"><?php esc_html_e( 'Email Subject Template', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Customizable subject format.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="text" name="settings[notification_subject]" id="notification_subject" value="<?php echo esc_attr( $settings['notification_subject'] ?? '[{site_name}] New Form Submission: {form_name}' ); ?>" class="regular-text abfem-input" />
								<p class="description">
									<?php esc_html_e( 'Supported placeholder tags: {site_name}, {form_name}, {entry_id}', 'ab-form-entries-manager' ); ?>
								</p>

								<div class="abfem-test-email-box">
									<button type="button" class="button button-secondary" id="abfem-btn-test-email">
										<span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Send Test Email Now', 'ab-form-entries-manager' ); ?>
									</button>
									<span class="abfem-ajax-feedback" id="abfem-test-email-feedback"></span>
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<!-- ========================================== -->
		<!-- TAB 6: INSTRUCTIONS & GUIDE                -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'guide' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-guide">
			<?php
			// Render the detailed guide template inside the tab.
			$is_embedded_guide = true;
			require ABFEM_PLUGIN_DIR . 'admin/views/guide.php';
			?>
		</div>

		<!-- ========================================== -->
		<!-- TAB 7: SYSTEM STATUS & DIAGNOSTICS         -->
		<!-- ========================================== -->
		<div class="abfem-tab-pane <?php echo 'status' === $current_tab ? 'abfem-pane-active' : ''; ?>" id="tab-status">
			<div class="abfem-card">
				<div class="abfem-card-header">
					<h2><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'System Status &amp; Diagnostics', 'ab-form-entries-manager' ); ?></h2>
					<p class="abfem-card-hint"><?php esc_html_e( 'Environment information, debug logging, and self-test verification tools.', 'ab-form-entries-manager' ); ?></p>
				</div>
				<div class="abfem-card-body">
					<!-- Diagnostic Action Tools -->
					<div class="abfem-tools-callout-grid">
						<div class="abfem-tool-box">
							<div class="abfem-tool-icon"><span class="dashicons dashicons-plus-alt"></span></div>
							<div class="abfem-tool-info">
								<h4><?php esc_html_e( 'Generate Sample Submission', 'ab-form-entries-manager' ); ?></h4>
								<p><?php esc_html_e( 'Creates a sample form entry in your database so you can test and view the dashboard immediately.', 'ab-form-entries-manager' ); ?></p>
								<button type="button" class="button button-secondary abfem-btn-tool" id="abfem-btn-generate-sample">
									<span class="dashicons dashicons-database-add"></span> <?php esc_html_e( 'Generate Test Entry', 'ab-form-entries-manager' ); ?>
								</button>
								<span class="abfem-ajax-feedback" id="abfem-sample-feedback"></span>
							</div>
						</div>

						<div class="abfem-tool-box">
							<div class="abfem-tool-icon"><span class="dashicons dashicons-update"></span></div>
							<div class="abfem-tool-info">
								<h4><?php esc_html_e( 'Flush Counter Transients', 'ab-form-entries-manager' ); ?></h4>
								<p><?php esc_html_e( 'Re-calculates the cached dashboard counters and discovers any newly added forms.', 'ab-form-entries-manager' ); ?></p>
								<button type="button" class="button button-secondary abfem-btn-tool" id="abfem-btn-clear-cache">
									<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear Cache', 'ab-form-entries-manager' ); ?>
								</button>
								<span class="abfem-ajax-feedback" id="abfem-cache-feedback"></span>
							</div>
						</div>
					</div>

					<!-- Environment Table -->
					<div class="abfem-status-table-container">
						<div class="abfem-status-table-header">
							<h3><?php esc_html_e( 'Environment Information', 'ab-form-entries-manager' ); ?></h3>
							<button type="button" class="button button-small" id="abfem-btn-copy-report">
								<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy System Report', 'ab-form-entries-manager' ); ?>
							</button>
						</div>

						<?php
						global $wpdb;
						$counts_info   = ABFEM_Storage::get_counts();
						$total_entries = isset( $counts_info['total'] ) ? (int) $counts_info['total'] : 0;
						$cron_next     = wp_next_scheduled( 'abfem_daily_retention_cleanup' );
						$db_version    = ( isset( $wpdb ) && $wpdb instanceof wpdb && method_exists( $wpdb, 'db_version' ) ) ? $wpdb->db_version() : __( 'N/A', 'ab-form-entries-manager' );
						?>
						<table class="widefat striped abfem-status-table" id="abfem-system-table">
							<tbody>
								<tr>
									<td><strong><?php esc_html_e( 'AB Form Entries Version', 'ab-form-entries-manager' ); ?></strong></td>
									<td><?php echo esc_html( ABFEM_VERSION ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'WordPress Version', 'ab-form-entries-manager' ); ?></strong></td>
									<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'PHP Version', 'ab-form-entries-manager' ); ?></strong></td>
									<td><?php echo esc_html( PHP_VERSION ); ?> (<?php echo esc_html( ini_get( 'memory_limit' ) ); ?> limit)</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Database Engine', 'ab-form-entries-manager' ); ?></strong></td>
									<td><?php echo esc_html( $db_version ); ?></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Custom Post Type (abfem_entry)', 'ab-form-entries-manager' ); ?></strong></td>
									<td>
										<span class="abfem-status-tag abfem-status-active">
											<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Registered &amp; Healthy', 'ab-form-entries-manager' ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Total Entries Stored', 'ab-form-entries-manager' ); ?></strong></td>
									<td><strong><?php echo esc_html( number_format_i18n( $total_entries ) ); ?></strong></td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Active Form Plugins Detected', 'ab-form-entries-manager' ); ?></strong></td>
									<td>
										<?php
										$active_names = array();
										foreach ( $integrations as $int ) {
											if ( $int->is_active() ) {
												$active_names[] = $int->get_name();
											}
										}
										echo ! empty( $active_names ) ? esc_html( implode( ', ', $active_names ) ) : '<em>' . esc_html__( 'None detected yet', 'ab-form-entries-manager' ) . '</em>';
										?>
									</td>
								</tr>
								<tr>
									<td><strong><?php esc_html_e( 'Next Retention Purge Schedule', 'ab-form-entries-manager' ); ?></strong></td>
									<td>
										<?php
										if ( $cron_next ) {
											echo esc_html( human_time_diff( time(), $cron_next ) . ' ' . __( 'from now', 'ab-form-entries-manager' ) );
										} else {
											esc_html_e( 'Not scheduled (Retention disabled)', 'ab-form-entries-manager' );
										}
										?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Debug Log Setting -->
					<table class="form-table abfem-form-table" style="margin-top:20px;">
						<tr>
							<th scope="row">
								<label for="debug_log"><?php esc_html_e( 'Debug Logging', 'ab-form-entries-manager' ); ?></label>
								<span class="abfem-field-help"><?php esc_html_e( 'Development troubleshooting.', 'ab-form-entries-manager' ); ?></span>
							</th>
							<td>
								<input type="hidden" name="settings[debug_log]" value="0" />
								<label class="abfem-switch-label" for="debug_log">
									<span class="abfem-switch">
										<input type="checkbox" name="settings[debug_log]" id="debug_log" value="1" <?php checked( $settings['debug_log'], 1 ); ?> />
										<span class="abfem-slider"></span>
									</span>
									<span class="abfem-switch-text">
										<strong><?php esc_html_e( 'Log form capture activity and hook events to the WordPress debug log', 'ab-form-entries-manager' ); ?></strong>
										<span class="abfem-switch-desc"><?php esc_html_e( 'Writes capture status and payload information to wp-content/debug.log when WP_DEBUG_LOG is enabled.', 'ab-form-entries-manager' ); ?></span>
									</span>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<!-- Sticky Bottom Action Bar -->
		<div class="abfem-sticky-save-bar">
			<div class="abfem-save-bar-content">
				<div class="abfem-save-bar-info">
					<span class="dashicons dashicons-saved"></span>
					<span><?php esc_html_e( 'Remember to save your settings after making modifications.', 'ab-form-entries-manager' ); ?></span>
				</div>
				<div class="abfem-save-bar-actions">
					<button type="submit" class="button button-primary button-large abfem-btn-save">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Settings', 'ab-form-entries-manager' ); ?>
					</button>
				</div>
			</div>
		</div>

	</form>
</div>
