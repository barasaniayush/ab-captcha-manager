<?php
/**
 * Forminator integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Forminator
 */
class ABFEM_Forminator extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'forminator';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Forminator';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'forminator/forminator.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'Forminator' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( defined( 'FORMINATOR_VERSION' ) ) {
			return FORMINATOR_VERSION;
		}
		return $this->get_version_from_file( 'forminator/forminator.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions from Forminator custom forms automatically.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Hooks into Forminator form submission pipeline ("forminator_custom_form_submit_before_set_fields") to record submissions cleanly.', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'forminator_custom_form_submit_before_set_fields', array( $this, 'handle_submission' ), 10, 3 );
	}

	/**
	 * Handle Forminator submission.
	 *
	 * @param object $entry            Entry model.
	 * @param int    $form_id          Form ID.
	 * @param array  $field_data_array Field data array.
	 */
	public function handle_submission( $entry, $form_id, $field_data_array ) {
		/* translators: %d: Forminator Form ID */
		$form_name = sprintf( __( 'Forminator Form #%d', 'ab-form-entries-manager' ), $form_id );
		if ( class_exists( 'Forminator_API' ) ) {
			$model = Forminator_API::get_module( $form_id );
			if ( $model && ! empty( $model->name ) ) {
				$form_name = $model->name;
			}
		}

		$normalized_fields = array();
		$submitter_name    = '';
		$submitter_email   = '';

		if ( ! empty( $field_data_array ) && is_array( $field_data_array ) ) {
			foreach ( $field_data_array as $field ) {
				if ( ! is_array( $field ) || empty( $field['name'] ) ) {
					continue;
				}

				$f_slug = $field['name'];
				$f_val  = $field['value'] ?? '';

				if ( 0 === strpos( $f_slug, '_forminator_' ) ) {
					continue;
				}

				$label = ucwords( str_replace( array( '-', '_' ), ' ', $f_slug ) );
				$type  = $this->detect_field_type( $f_slug, $f_val );

				if ( empty( $submitter_email ) && ( 'email' === $type || false !== stripos( $f_slug, 'email' ) ) ) {
					if ( is_string( $f_val ) && is_email( $f_val ) ) {
						$submitter_email = $f_val;
					}
				}

				if ( empty( $submitter_name ) && ( false !== stripos( $f_slug, 'name' ) ) ) {
					if ( is_string( $f_val ) && ! empty( $f_val ) && ! is_email( $f_val ) ) {
						$submitter_name = $f_val;
					}
				}

				$normalized_fields[] = array(
					'name'  => $f_slug,
					'label' => $label,
					'type'  => $type,
					'value' => $f_val,
				);
			}
		}

		$payload = array(
			'source_form_id'   => (string) $form_id,
			'source_form_name' => $form_name,
			'source_entry_id'  => isset( $entry->entry_id ) ? (string) $entry->entry_id : '',
			'source_url'       => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
			'submitter_name'   => $submitter_name,
			'submitter_email'  => $submitter_email,
			'fields'           => $normalized_fields,
		);

		$this->record_submission( $payload );
	}

	/**
	 * Retrieve all discovered forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		if ( ! $this->is_active() || ! class_exists( 'Forminator_API' ) ) {
			return array();
		}

		$forms_data = array();
		try {
			$modules = Forminator_API::get_forms( null, 1, 100 );
			if ( ! empty( $modules ) && is_array( $modules ) ) {
				foreach ( $modules as $module ) {
					$id = isset( $module->id ) ? (string) $module->id : '';
					/* translators: %s: Forminator Form ID */
					$title = isset( $module->name ) ? $module->name : sprintf( __( 'Forminator #%s', 'ab-form-entries-manager' ), $id );
					$forms_data[ $id ] = array(
						'form_id'            => $id,
						'form_name'          => $title,
						'source_plugin'      => $this->get_id(),
						'source_plugin_name' => $this->get_name(),
					);
				}
			}
		} catch ( \Throwable $e ) {
			// Fail gracefully.
		}

		return $forms_data;
	}
}
