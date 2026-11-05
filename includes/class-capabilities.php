<?php
/**
 * Permissions and capabilities helper.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Capabilities
 */
class ABFEM_Capabilities {

	/**
	 * Get the capability required to manage form entries.
	 *
	 * @return string
	 */
	public static function get_capability() {
		/**
		 * Filter the capability required to view and manage AB Form Entries.
		 *
		 * @param string $capability Default capability 'manage_options'.
		 */
		return apply_filters( 'abfem_manage_capability', 'manage_options' );
	}

	/**
	 * Check if the current user has permission to manage form entries.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( self::get_capability() );
	}

	/**
	 * Enforce permission check or die with an unauthorized message.
	 */
	public static function check_manage_permission() {
		if ( ! self::can_manage() ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'ab-form-entries-manager' ),
				esc_html__( 'Unauthorized', 'ab-form-entries-manager' ),
				array( 'response' => 403 )
			);
		}
	}
}
