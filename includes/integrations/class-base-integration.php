<?php
/**
 * Base abstract class for form builder integrations.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Base_Integration
 */
abstract class ABFEM_Base_Integration implements ABFEM_Integration_Interface {

	/**
	 * Send normalized payload to submission service.
	 *
	 * @param array $payload Normalized submission data.
	 * @return int|false
	 */
	protected function record_submission( array $payload ) {
		$payload['source_plugin']      = $this->get_id();
		$payload['source_plugin_name'] = $this->get_name();

		return ABFEM_Submission_Service::process_submission( $payload );
	}

	/**
	 * Check if file exists relative to WP_PLUGIN_DIR.
	 *
	 * @param string $plugin_rel_path e.g. 'contact-form-7/wp-contact-form-7.php'.
	 * @return bool
	 */
	protected function plugin_file_exists( $plugin_rel_path ) {
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			return file_exists( WP_PLUGIN_DIR . '/' . $plugin_rel_path );
		}
		return false;
	}

	/**
	 * Extract plugin version from header file if possible.
	 *
	 * @param string $plugin_rel_path Plugin main file path relative to WP_PLUGIN_DIR.
	 * @return string
	 */
	protected function get_version_from_file( $plugin_rel_path ) {
		if ( ! function_exists( 'get_file_data' ) && defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( function_exists( 'get_file_data' ) && defined( 'WP_PLUGIN_DIR' ) ) {
			$file = WP_PLUGIN_DIR . '/' . $plugin_rel_path;
			if ( file_exists( $file ) ) {
				$data = get_file_data( $file, array( 'Version' => 'Version' ) );
				if ( ! empty( $data['Version'] ) ) {
					return $data['Version'];
				}
			}
		}

		return '';
	}

	/**
	 * Helper to guess field type from name or value.
	 *
	 * @param string $name Field name.
	 * @param mixed  $val  Field value.
	 * @return string
	 */
	protected function detect_field_type( $name, $val ) {
		if ( is_array( $val ) ) {
			return 'array';
		}

		$name_lower = strtolower( $name );

		if ( false !== strpos( $name_lower, 'email' ) || is_email( (string) $val ) ) {
			return 'email';
		}

		if ( false !== strpos( $name_lower, 'phone' ) || false !== strpos( $name_lower, 'tel' ) ) {
			return 'tel';
		}

		if ( false !== strpos( $name_lower, 'url' ) || false !== strpos( $name_lower, 'website' ) ) {
			return 'url';
		}

		if ( false !== strpos( $name_lower, 'message' ) || false !== strpos( $name_lower, 'comment' ) || false !== strpos( $name_lower, 'notes' ) || strlen( (string) $val ) > 100 ) {
			return 'textarea';
		}

		if ( false !== strpos( $name_lower, 'date' ) ) {
			return 'date';
		}

		if ( false !== strpos( $name_lower, 'file' ) || false !== strpos( $name_lower, 'upload' ) ) {
			return 'file';
		}

		return 'text';
	}
}
