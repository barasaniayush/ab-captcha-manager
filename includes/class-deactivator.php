<?php
/**
 * Fired during plugin deactivation.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Deactivator
 */
class ABFEM_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		// Clear scheduled cron tasks.
		$timestamp = wp_next_scheduled( 'abfem_daily_retention_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'abfem_daily_retention_cleanup' );
		}

		// Delete cached counters transient.
		delete_transient( 'abfem_counts' );
	}
}
