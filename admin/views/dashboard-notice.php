<?php
/**
 * Dashboard notification view template for /wp-admin/index.php.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$submissions_url = admin_url( 'admin.php?page=abfem-submissions' );
$unread_url      = admin_url( 'admin.php?page=abfem-submissions&status=unread' );
$export_url      = admin_url( 'admin.php?page=abfem-export' );
?>
<div class="notice notice-info abfem-dashboard-notice is-dismissible" data-unread="<?php echo esc_attr( (int) $unread_count ); ?>" data-latest-id="<?php echo esc_attr( (int) ( $latest_unread_id ?? 0 ) ); ?>">
	<div class="abfem-notice-inner">
		<div class="abfem-notice-icon">
			<span class="dashicons dashicons-email-alt2"></span>
		</div>
		<div class="abfem-notice-content">
			<h4 class="abfem-notice-title"><?php esc_html_e( 'AB Form Entries — New Submissions Captured', 'ab-form-entries-manager' ); ?></h4>
			<p>
				<?php
				printf(
					/* translators: %d: number of unread submissions */
					esc_html( _n( 'You have %d new unread form submission waiting for review.', 'You have %d new unread form submissions waiting for review.', (int) $unread_count, 'ab-form-entries-manager' ) ),
					(int) $unread_count
				);
				?>
			</p>
			<div class="abfem-notice-actions">
				<a href="<?php echo esc_url( $unread_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'View Unread Submissions', 'ab-form-entries-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( $submissions_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-feedback"></span> <?php esc_html_e( 'All Submissions', 'ab-form-entries-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ab-form-entries-manager' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
