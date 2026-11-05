<?php
/**
 * Interface for form builder integration adapters.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface ABFEM_Integration_Interface
 */
interface ABFEM_Integration_Interface {

	/**
	 * Unique identifier for the integration (e.g. 'cf7', 'wpforms').
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Display name of the form builder plugin.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Check whether the third-party form builder plugin is installed.
	 *
	 * @return bool
	 */
	public function is_installed();

	/**
	 * Check whether the form builder plugin is currently active and ready.
	 *
	 * @return bool
	 */
	public function is_active();

	/**
	 * Get the detected version of the third-party plugin.
	 *
	 * @return string
	 */
	public function get_version();

	/**
	 * Initialize and register action hooks to capture submissions.
	 */
	public function init();

	/**
	 * Retrieve all discovered forms created in this builder.
	 *
	 * @return array Array of arrays with 'id' and 'name'.
	 */
	public function get_forms();

	/**
	 * Short description of this integration.
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Implementation or troubleshooting notes.
	 *
	 * @return string
	 */
	public function get_notes();
}
