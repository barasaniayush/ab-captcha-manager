<?php
/**
 * Elementor Pro Forms integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Elementor
 */
class ABFEM_Elementor extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'elementor';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Elementor Pro Forms';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'elementor-pro/elementor-pro.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( '\ElementorPro\Plugin' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return ELEMENTOR_PRO_VERSION;
		}
		return $this->get_version_from_file( 'elementor-pro/elementor-pro.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions submitted through Elementor Pro Form widgets.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Requires Elementor Pro. Hooks into the official "elementor_pro/forms/new_record" action.', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'elementor_pro/forms/new_record', array( $this, 'handle_submission' ), 10, 2 );
	}

	/**
	 * Handle Elementor form submission.
	 *
	 * @param mixed $record  Form record object.
	 * @param mixed $handler Form handler object.
	 */
	public function handle_submission( $record, $handler = null ) {
		if ( ! is_object( $record ) || ! method_exists( $record, 'get' ) ) {
			return;
		}

		$form_name = method_exists( $record, 'get_form_settings' ) ? $record->get_form_settings( 'form_name' ) : '';
		$form_id   = method_exists( $record, 'get_form_settings' ) ? $record->get_form_settings( 'id' ) : '';

		if ( empty( $form_name ) ) {
			/* translators: %s: Elementor Form ID */
			$form_name = ! empty( $form_id ) ? sprintf( __( 'Elementor Form (%s)', 'ab-form-entries-manager' ), $form_id ) : __( 'Elementor Form', 'ab-form-entries-manager' );
		}

		$raw_fields = (array) $record->get( 'fields' );
		$meta       = (array) $record->get( 'meta' );
		$files      = (array) $record->get( 'files' );

		$fields          = array();
		$submitter_name  = '';
		$submitter_email = '';

		foreach ( $raw_fields as $f_id => $field_data ) {
			if ( ! is_array( $field_data ) ) {
				continue;
			}

			$f_name  = ! empty( $field_data['id'] ) ? $field_data['id'] : $f_id;
			$f_label = ! empty( $field_data['title'] ) ? $field_data['title'] : ucwords( str_replace( array( '-', '_' ), ' ', $f_name ) );
			$f_val   = isset( $field_data['value'] ) ? $field_data['value'] : '';
			$f_type  = ! empty( $field_data['type'] ) ? $field_data['type'] : $this->detect_field_type( $f_name, $f_val );

			if ( empty( $submitter_email ) && ( 'email' === $f_type || false !== stripos( $f_name, 'email' ) ) ) {
				if ( is_string( $f_val ) && is_email( $f_val ) ) {
					$submitter_email = $f_val;
				}
			}

			if ( empty( $submitter_name ) && ( false !== stripos( $f_name, 'name' ) || false !== stripos( $f_label, 'name' ) ) ) {
				if ( is_string( $f_val ) && ! empty( $f_val ) && ! is_email( $f_val ) ) {
					$submitter_name = $f_val;
				}
			}

			$fields[] = array(
				'name'  => $f_name,
				'label' => $f_label,
				'type'  => $f_type,
				'value' => $f_val,
			);
		}

		// Include uploaded files.
		if ( ! empty( $files ) && is_array( $files ) ) {
			foreach ( $files as $file_id => $file_info ) {
				$file_names = array();
				if ( is_array( $file_info ) && isset( $file_info['name'] ) ) {
					$file_names[] = is_array( $file_info['name'] ) ? implode( ', ', $file_info['name'] ) : $file_info['name'];
				} elseif ( is_array( $file_info ) && isset( $file_info['url'] ) ) {
					$file_names[] = is_array( $file_info['url'] ) ? implode( ', ', $file_info['url'] ) : $file_info['url'];
				}

				$fields[] = array(
					'name'  => $file_id,
					'label' => ucwords( str_replace( array( '-', '_' ), ' ', $file_id ) ),
					'type'  => 'file',
					'value' => implode( ', ', $file_names ),
				);
			}
		}

		$source_url = ! empty( $meta['page_url']['value'] ) ? $meta['page_url']['value'] : ( isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' );
		$ip         = ! empty( $meta['remote_ip']['value'] ) ? $meta['remote_ip']['value'] : '';

		$payload = array(
			'source_form_id'   => (string) $form_id,
			'source_form_name' => $form_name,
			'source_url'       => $source_url,
			'submitter_name'   => $submitter_name,
			'submitter_email'  => $submitter_email,
			'submitter_ip'     => $ip,
			'fields'           => $fields,
		);

		$this->record_submission( $payload );
	}

	/**
	 * Retrieve all discovered forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		// Elementor forms are embedded inside post templates or widget metadata.
		// We return unique forms previously recorded by our plugin for this builder.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT pm_id.meta_value AS id, pm_name.meta_value AS name
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm_plugin ON p.ID = pm_plugin.post_id AND pm_plugin.meta_key = '_abfem_source_plugin' AND pm_plugin.meta_value = 'elementor'
				 INNER JOIN {$wpdb->postmeta} pm_id ON p.ID = pm_id.post_id AND pm_id.meta_key = '_abfem_form_id'
				 LEFT JOIN {$wpdb->postmeta} pm_name ON p.ID = pm_name.post_id AND pm_name.meta_key = '_abfem_form_name'
				 WHERE p.post_type = %s",
				ABFEM_POST_TYPE
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}
}
