<?php
/**
 * Ninja Forms integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Ninja_Forms
 */
class ABFEM_Ninja_Forms extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'ninjaforms';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Ninja Forms';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'ninja-forms/ninja-forms.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'Ninja_Forms' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( class_exists( 'Ninja_Forms' ) && isset( Ninja_Forms::$version ) ) {
			return Ninja_Forms::$version;
		}
		return $this->get_version_from_file( 'ninja-forms/ninja-forms.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions processed by Ninja Forms.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Hooks into the official "ninja_forms_after_submission" action. Normalizes field keys, labels, and action submission IDs.', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'ninja_forms_after_submission', array( $this, 'handle_submission' ), 10, 1 );
	}

	/**
	 * Handle Ninja Forms submission.
	 *
	 * @param array $form_data Form submission data array.
	 */
	public function handle_submission( $form_data ) {
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return;
		}

		$form_id    = isset( $form_data['form_id'] ) ? (string) $form_data['form_id'] : '';
		/* translators: %s: Ninja Form ID */
		$form_title = isset( $form_data['settings']['title'] ) ? $form_data['settings']['title'] : sprintf( __( 'Ninja Form #%s', 'ab-form-entries-manager' ), $form_id );
		$entry_id   = isset( $form_data['actions']['save']['sub_id'] ) ? (string) $form_data['actions']['save']['sub_id'] : '';

		$fields          = array();
		$submitter_name  = '';
		$submitter_email = '';

		$raw_fields = isset( $form_data['fields'] ) && is_array( $form_data['fields'] ) ? $form_data['fields'] : array();

		foreach ( $raw_fields as $f ) {
			if ( ! is_array( $f ) ) {
				continue;
			}

			$f_type = $f['type'] ?? 'text';
			if ( in_array( $f_type, array( 'submit', 'hr', 'html', 'recaptcha', 'spam' ), true ) ) {
				continue;
			}

			$f_id    = $f['id'] ?? '';
			$f_key   = $f['key'] ?? ( 'field_' . $f_id );
			$f_label = ! empty( $f['label'] ) ? $f['label'] : ( ! empty( $f['admin_label'] ) ? $f['admin_label'] : ucwords( str_replace( array( '-', '_' ), ' ', $f_key ) ) );
			$f_val   = $f['value'] ?? '';

			if ( empty( $submitter_email ) && ( 'email' === $f_type || false !== stripos( $f_key, 'email' ) ) ) {
				if ( is_string( $f_val ) && is_email( $f_val ) ) {
					$submitter_email = $f_val;
				}
			}

			if ( empty( $submitter_name ) && ( false !== stripos( $f_key, 'name' ) || false !== stripos( $f_label, 'name' ) ) ) {
				if ( is_string( $f_val ) && ! empty( $f_val ) && ! is_email( $f_val ) ) {
					$submitter_name = $f_val;
				}
			}

			$fields[] = array(
				'name'  => $f_key,
				'label' => $f_label,
				'type'  => $f_type,
				'value' => $f_val,
			);
		}

		$payload = array(
			'source_form_id'   => $form_id,
			'source_form_name' => $form_title,
			'source_entry_id'  => $entry_id,
			'source_url'       => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
			'submitter_name'   => $submitter_name,
			'submitter_email'  => $submitter_email,
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
		if ( ! $this->is_active() || ! function_exists( 'Ninja_Forms' ) ) {
			return array();
		}

		$forms = Ninja_Forms()->form()->get_forms();
		$list  = array();

		if ( is_array( $forms ) ) {
			foreach ( $forms as $f ) {
				if ( is_object( $f ) ) {
					/* translators: %d: Ninja Form ID */
					$fallback_title = sprintf( __( 'Ninja Form #%d', 'ab-form-entries-manager' ), $f->get_id() );
					$list[] = array(
						'id'   => (string) $f->get_id(),
						'name' => $f->get_setting( 'title' ) ?: $fallback_title,
					);
				}
			}
		}

		return $list;
	}
}
