<?php
/**
 * Fired during plugin activation.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Activator
 */
class ABFEM_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		// Ensure default settings exist.
		$existing = get_option( ABFEM_Settings::OPTION_KEY, false );
		if ( false === $existing ) {
			update_option( ABFEM_Settings::OPTION_KEY, ABFEM_Settings::get_defaults() );
		}

		// Register post type to ensure it's known immediately.
		require_once ABFEM_PLUGIN_DIR . 'includes/class-storage.php';
		ABFEM_Storage::register_post_type();

		// Schedule daily retention check if not already scheduled.
		if ( ! wp_next_scheduled( 'abfem_daily_retention_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'abfem_daily_retention_cleanup' );
		}

		// Store version.
		update_option( 'abfem_version', ABFEM_VERSION );
	}
}
