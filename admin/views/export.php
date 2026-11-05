<?php
/**
 * CSV Export view template.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wrap abfem-wrap abfem-export-wrap">
	<!-- Modern Brand Header -->
	<div class="abfem-modern-header">
		<div class="abfem-header-left">
			<div class="abfem-logo-icon">
				<span class="dashicons dashicons-download"></span>
			</div>
			<div class="abfem-header-title-box">
				<div class="abfem-title-row">
					<h1 class="abfem-header-title"><?php esc_html_e( 'Export Submissions to CSV', 'ab-form-entries-manager' ); ?></h1>
					<span class="abfem-version-pill"><?php echo esc_html( 'v' . ABFEM_VERSION ); ?></span>
				</div>
				<p class="abfem-subtitle"><?php esc_html_e( 'Download filtered or complete form submission datasets formatted for Microsoft Excel, Google Sheets, or Apple Numbers.', 'ab-form-entries-manager' ); ?></p>
			</div>
		</div>

		<div class="abfem-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'All Submissions', 'ab-form-entries-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings' ) ); ?>" class="button button-primary abfem-btn-modern">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'ab-form-entries-manager' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<div class="abfem-export-container">
		<!-- Main Export Configuration Card -->
		<div class="abfem-card abfem-export-card">
			<div class="abfem-card-header">
				<h2>
					<span class="dashicons dashicons-filter"></span>
					<?php esc_html_e( 'Configure Export Options', 'ab-form-entries-manager' ); ?>
				</h2>
				<span class="abfem-card-hint"><?php esc_html_e( 'Filter submissions before generating CSV', 'ab-form-entries-manager' ); ?></span>
			</div>

			<div class="abfem-card-body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="abfem-export-form">
					<input type="hidden" name="action" value="abfem_export_csv" />
					<?php wp_nonce_field( 'abfem_export_nonce', 'nonce' ); ?>

					<div class="abfem-export-field-group">
						<div class="abfem-form-row">
							<label for="export-source-plugin" class="abfem-form-label">
								<?php esc_html_e( 'Form Builder Plugin', 'ab-form-entries-manager' ); ?>
							</label>
							<div class="abfem-select-wrapper">
								<select name="source_plugin" id="export-source-plugin" class="abfem-select">
									<option value=""><?php esc_html_e( 'All Form Builders (Everything)', 'ab-form-entries-manager' ); ?></option>
									<option value="cf7"><?php esc_html_e( 'Contact Form 7', 'ab-form-entries-manager' ); ?></option>
									<option value="wpforms"><?php esc_html_e( 'WPForms', 'ab-form-entries-manager' ); ?></option>
									<option value="elementor"><?php esc_html_e( 'Elementor Pro Forms', 'ab-form-entries-manager' ); ?></option>
									<option value="gravityforms"><?php esc_html_e( 'Gravity Forms', 'ab-form-entries-manager' ); ?></option>
									<option value="ninjaforms"><?php esc_html_e( 'Ninja Forms', 'ab-form-entries-manager' ); ?></option>
									<option value="jetpack"><?php esc_html_e( 'Jetpack Forms', 'ab-form-entries-manager' ); ?></option>
									<option value="formidable"><?php esc_html_e( 'Formidable Forms', 'ab-form-entries-manager' ); ?></option>
									<option value="forminator"><?php esc_html_e( 'Forminator Forms', 'ab-form-entries-manager' ); ?></option>
								</select>
							</div>
							<p class="description"><?php esc_html_e( 'Limit export to submissions captured from a specific builder plugin.', 'ab-form-entries-manager' ); ?></p>
						</div>

						<?php if ( ! empty( $all_forms ) ) : ?>
							<div class="abfem-form-row">
								<label for="export-form-id" class="abfem-form-label">
									<?php esc_html_e( 'Specific Form', 'ab-form-entries-manager' ); ?>
								</label>
								<div class="abfem-select-wrapper">
									<select name="form_id" id="export-form-id" class="abfem-select">
										<option value=""><?php esc_html_e( 'All Forms', 'ab-form-entries-manager' ); ?></option>
										<?php foreach ( $all_forms as $f ) : ?>
											<option value="<?php echo esc_attr( $f['form_id'] ); ?>">
												<?php echo esc_html( $f['form_name'] ); ?> (<?php echo esc_html( $f['source_plugin_name'] ?? $f['source_plugin'] ); ?> #<?php echo esc_html( $f['form_id'] ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<p class="description"><?php esc_html_e( 'Export submissions for a specific individual form.', 'ab-form-entries-manager' ); ?></p>
							</div>
						<?php endif; ?>

						<div class="abfem-form-row">
							<label for="export-status" class="abfem-form-label">
								<?php esc_html_e( 'Read / Unread Status', 'ab-form-entries-manager' ); ?>
							</label>
							<div class="abfem-select-wrapper">
								<select name="status" id="export-status" class="abfem-select">
									<option value=""><?php esc_html_e( 'All Submissions (Read & Unread)', 'ab-form-entries-manager' ); ?></option>
									<option value="unread"><?php esc_html_e( 'Unread Entries Only', 'ab-form-entries-manager' ); ?></option>
									<option value="read"><?php esc_html_e( 'Read Entries Only', 'ab-form-entries-manager' ); ?></option>
								</select>
							</div>
							<p class="description"><?php esc_html_e( 'Filter entries according to whether they have been reviewed.', 'ab-form-entries-manager' ); ?></p>
						</div>

						<div class="abfem-form-row abfem-date-range-row">
							<label class="abfem-form-label">
								<?php esc_html_e( 'Date Range (Optional)', 'ab-form-entries-manager' ); ?>
							</label>
							<div class="abfem-date-inputs">
								<div class="abfem-date-field">
									<span class="abfem-date-label"><?php esc_html_e( 'From:', 'ab-form-entries-manager' ); ?></span>
									<input type="date" name="date_from" id="export-date-from" class="abfem-input abfem-date-input" />
								</div>
								<div class="abfem-date-field">
									<span class="abfem-date-label"><?php esc_html_e( 'To:', 'ab-form-entries-manager' ); ?></span>
									<input type="date" name="date_to" id="export-date-to" class="abfem-input abfem-date-input" />
								</div>
							</div>
							<p class="description"><?php esc_html_e( 'Leave dates empty to include all available submissions.', 'ab-form-entries-manager' ); ?></p>
						</div>
					</div>

					<div class="abfem-form-actions">
						<button type="submit" class="button button-primary button-hero abfem-export-btn">
							<span class="dashicons dashicons-download"></span>
							<span><?php esc_html_e( 'Download CSV File', 'ab-form-entries-manager' ); ?></span>
						</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Security & Spec info box -->
		<div class="abfem-card abfem-info-card">
			<div class="abfem-card-header">
				<h2>
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Security & Export Standards', 'ab-form-entries-manager' ); ?>
				</h2>
			</div>
			<div class="abfem-card-body">
				<div class="abfem-feature-list">
					<div class="abfem-feature-item">
						<div class="abfem-feature-icon abfem-icon-success">
							<span class="dashicons dashicons-yes"></span>
						</div>
						<div class="abfem-feature-content">
							<h4><?php esc_html_e( 'CSV Formula Injection Protection', 'ab-form-entries-manager' ); ?></h4>
							<p><?php esc_html_e( 'Cells starting with =, +, -, @, or tabs are safely escaped to prevent malicious formula execution in spreadsheet software.', 'ab-form-entries-manager' ); ?></p>
						</div>
					</div>

					<div class="abfem-feature-item">
						<div class="abfem-feature-icon abfem-icon-success">
							<span class="dashicons dashicons-yes"></span>
						</div>
						<div class="abfem-feature-content">
							<h4><?php esc_html_e( 'Unicode UTF-8 Compatibility', 'ab-form-entries-manager' ); ?></h4>
							<p><?php esc_html_e( 'Generated with a UTF-8 Byte Order Mark (BOM) to ensure accented, international, and non-Latin characters render cleanly in Excel.', 'ab-form-entries-manager' ); ?></p>
						</div>
					</div>

					<div class="abfem-feature-item">
						<div class="abfem-feature-icon abfem-icon-success">
							<span class="dashicons dashicons-yes"></span>
						</div>
						<div class="abfem-feature-content">
							<h4><?php esc_html_e( 'RFC 4180 Standard Compliance', 'ab-form-entries-manager' ); ?></h4>
							<p><?php esc_html_e( 'Multiline entries, quotes, and commas are strictly escaped according to standard CSV specifications.', 'ab-form-entries-manager' ); ?></p>
						</div>
					</div>

					<div class="abfem-feature-item">
						<div class="abfem-feature-icon abfem-icon-success">
							<span class="dashicons dashicons-yes"></span>
						</div>
						<div class="abfem-feature-content">
							<h4><?php esc_html_e( 'Capability & Nonce Guarded', 'ab-form-entries-manager' ); ?></h4>
							<p><?php esc_html_e( 'Only authenticated administrators with manage permissions can generate and download exports.', 'ab-form-entries-manager' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
