<?php
/**
 * Dashboard submissions view template.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap abfem-wrap abfem-dashboard-wrap">
	<!-- Modern Brand Header -->
	<div class="abfem-modern-header">
		<div class="abfem-header-left">
			<div class="abfem-logo-icon">
				<span class="dashicons dashicons-feedback"></span>
			</div>
			<div class="abfem-header-title-box">
				<div class="abfem-title-row">
					<h1 class="abfem-header-title"><?php esc_html_e( 'Form Submissions', 'ab-form-entries-manager' ); ?></h1>
					<span class="abfem-version-pill"><?php echo esc_html( 'v' . ABFEM_VERSION ); ?></span>
				</div>
				<p class="abfem-subtitle"><?php esc_html_e( 'Universal central inbox for all captured form submissions across your site.', 'ab-form-entries-manager' ); ?></p>
			</div>
		</div>

		<div class="abfem-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-forms' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-category"></span> <?php esc_html_e( 'View Forms', 'ab-form-entries-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-export' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ab-form-entries-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings' ) ); ?>" class="button button-primary abfem-btn-modern">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'ab-form-entries-manager' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<?php
	// 1. Metric summary cards.
	$list_table->render_metrics( $active_count );

	// 2. Search and filter bar.
	$list_table->render_filters( $all_forms );

	// 3. Submissions table with bulk actions and pagination.
	$list_table->render_table();
	?>
</div>
