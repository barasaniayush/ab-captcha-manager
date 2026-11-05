<?php
/**
 * Integrations status view template.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wrap abfem-wrap abfem-integrations-wrap">
	<!-- Modern Brand Header -->
	<div class="abfem-modern-header">
		<div class="abfem-header-left">
			<div class="abfem-logo-icon">
				<span class="dashicons dashicons-networking"></span>
			</div>
			<div class="abfem-header-title-box">
				<div class="abfem-title-row">
					<h1 class="abfem-header-title"><?php esc_html_e( 'Form Builder Integrations', 'ab-form-entries-manager' ); ?></h1>
					<span class="abfem-version-pill"><?php echo esc_html( 'v' . ABFEM_VERSION ); ?></span>
				</div>
				<p class="abfem-subtitle"><?php esc_html_e( 'Supported form engines automatically monitored for incoming submissions.', 'ab-form-entries-manager' ); ?></p>
			</div>
		</div>

		<div class="abfem-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings&tab=integrations' ) ); ?>" class="button button-primary abfem-btn-modern">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Manage In Settings', 'ab-form-entries-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-guide' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-book-alt"></span> <?php esc_html_e( 'View Setup Guides', 'ab-form-entries-manager' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<div class="abfem-integrations-grid">
		<?php foreach ( $integrations as $int_id => $integration ) :
			$is_installed = $integration->is_installed();
			$is_active    = $integration->is_active();
			$is_enabled   = ABFEM_Settings::is_integration_enabled( $int_id );
			$version      = $integration->get_version();

			// Determine actual status.
			if ( $is_active && $is_enabled ) {
				$status_class = 'abfem-status-active';
				$status_text  = __( 'Active & Ready', 'ab-form-entries-manager' );
				$icon_class   = 'dashicons-yes-alt';
			} elseif ( $is_active && ! $is_enabled ) {
				$status_class = 'abfem-status-disabled';
				$status_text  = __( 'Disabled in Settings', 'ab-form-entries-manager' );
				$icon_class   = 'dashicons-dismiss';
			} elseif ( $is_installed && ! $is_active ) {
				$status_class = 'abfem-status-inactive';
				$status_text  = __( 'Installed (Inactive)', 'ab-form-entries-manager' );
				$icon_class   = 'dashicons-warning';
			} else {
				$status_class = 'abfem-status-notinstalled';
				$status_text  = __( 'Not Installed', 'ab-form-entries-manager' );
				$icon_class   = 'dashicons-minus';
			}

			// Count captured entries from this builder.
			$captured_count = ABFEM_Storage::get_entry_count_by_plugin( $int_id );
			?>
			<div class="abfem-integration-card <?php echo esc_attr( $status_class ); ?>">
				<div class="abfem-integration-card-header">
					<div class="abfem-integration-title-box">
						<span class="abfem-integration-badge abfem-badge-<?php echo esc_attr( $int_id ); ?>">
							<?php echo esc_html( substr( $integration->get_name(), 0, 2 ) ); ?>
						</span>
						<div class="abfem-integration-names">
							<h3 class="abfem-integration-title"><?php echo esc_html( $integration->get_name() ); ?></h3>
							<?php if ( ! empty( $version ) ) : ?>
								<?php /* translators: %s: Plugin version number */ ?>
								<span class="abfem-integration-version"><?php echo esc_html( sprintf( __( 'v%s', 'ab-form-entries-manager' ), $version ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<span class="abfem-status-tag <?php echo esc_attr( $status_class ); ?>">
						<span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span> <?php echo esc_html( $status_text ); ?>
					</span>
				</div>

				<div class="abfem-integration-card-body">
					<p class="abfem-integration-desc"><?php echo esc_html( $integration->get_description() ); ?></p>

					<div class="abfem-integration-meta">
						<div class="abfem-meta-stat">
							<span class="abfem-stat-label"><?php esc_html_e( 'Captured Entries:', 'ab-form-entries-manager' ); ?></span>
							<span class="abfem-stat-number"><?php echo esc_html( number_format_i18n( $captured_count ) ); ?></span>
						</div>
					</div>

					<div class="abfem-integration-notes">
						<strong><?php esc_html_e( 'Integration Details:', 'ab-form-entries-manager' ); ?></strong>
						<p><?php echo esc_html( $integration->get_notes() ); ?></p>
					</div>
				</div>

				<div class="abfem-integration-card-footer">
					<?php if ( $captured_count > 0 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions&source_plugin=' . $int_id ) ); ?>" class="button button-small button-secondary">
							<?php esc_html_e( 'View Entries', 'ab-form-entries-manager' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( ! $is_installed ) : ?>
						<span class="abfem-muted abfem-install-hint"><?php esc_html_e( 'Install plugin to enable', 'ab-form-entries-manager' ); ?></span>
					<?php elseif ( ! $is_active ) : ?>
						<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Activate in Plugins', 'ab-form-entries-manager' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
