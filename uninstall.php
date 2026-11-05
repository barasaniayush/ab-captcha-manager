<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AB_Form_Entries_Manager
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clear any scheduled cron events.
wp_clear_scheduled_hook( 'abfem_daily_retention_cleanup' );

$abfem_settings    = get_option( 'abfem_settings', array() );
$abfem_delete_data = ! empty( $abfem_settings['delete_data_on_uninstall'] );

if ( $abfem_delete_data ) {
	// Query and delete all abfem_entry posts in safe batches.
	do {
		$abfem_entries = get_posts( array(
			'post_type'      => 'abfem_entry',
			'post_status'    => 'any',
			'posts_per_page' => 100,
			'fields'         => 'ids',
		) );

		if ( empty( $abfem_entries ) ) {
			break;
		}

		foreach ( $abfem_entries as $abfem_entry_id ) {
			wp_delete_post( $abfem_entry_id, true );
		}
	} while ( ! empty( $abfem_entries ) );

	// Remove plugin options.
	delete_option( 'abfem_settings' );
	delete_option( 'abfem_version' );
	delete_transient( 'abfem_counts' );
	delete_transient( 'abfem_all_forms' );
}
