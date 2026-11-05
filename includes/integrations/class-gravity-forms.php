<?php
/**
 * Gravity Forms integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Gravity_Forms
 */
class ABFEM_Gravity_Forms extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'gravityforms';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Gravity Forms';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'gravityforms/gravityforms.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'GFForms' ) || class_exists( 'GFAPI' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( class_exists( 'GFForms' ) && ! empty( GFForms::$version ) ) {
			return GFForms::$version;
		}
		return $this->get_version_from_file( 'gravityforms/gravityforms.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions processed by Gravity Forms.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Hooks into the standard "gform_after_submission" action. Multi-column fields (Name, Address) and file attachments are normalized.', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'gform_after_submission', array( $this, 'handle_submission' ), 10, 2 );
	}

	/**
	 * Handle Gravity Forms submission.
	 *
	 * @param array $entry Submission entry array.
	 * @param array $form  Form array configuration.
	 */
	public function handle_submission( $entry, $form ) {
		if ( empty( $entry ) || empty( $form ) || ! is_array( $form ) ) {
			return;
		}

		$form_id    = isset( $form['id'] ) ? (string) $form['id'] : '';
		/* translators: %s: Gravity Form ID */
		$form_title = isset( $form['title'] ) ? $form['title'] : sprintf( __( 'Gravity Form #%s', 'ab-form-entries-manager' ), $form_id );
		$entry_id   = isset( $entry['id'] ) ? (string) $entry['id'] : '';

		$fields          = array();
		$submitter_name  = '';
		$submitter_email = '';

		if ( ! empty( $form['fields'] ) && is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( ! is_object( $field ) && ! is_array( $field ) ) {
					continue;
				}

				$f_id    = is_object( $field ) ? $field->id : $field['id'];
				$f_type  = is_object( $field ) ? $field->type : $field['type'];
				$f_label = is_object( $field ) ? $field->label : $field['label'];
				$inputs  = is_object( $field ) ? ( $field->inputs ?? null ) : ( $field['inputs'] ?? null );

				if ( empty( $f_label ) ) {
					/* translators: %s: Field ID */
					$f_label = sprintf( __( 'Field %s', 'ab-form-entries-manager' ), $f_id );
				}

				// Skip page breaks, html blocks, section breaks, captchas.
				if ( in_array( $f_type, array( 'page', 'html', 'section', 'captcha' ), true ) ) {
					continue;
				}

				$val = '';
				if ( ! empty( $inputs ) && is_array( $inputs ) ) {
					// Composite field like Name or Address.
					$sub_values = array();
					foreach ( $inputs as $input ) {
						$sub_id = is_object( $input ) ? $input->id : $input['id'];
						if ( isset( $entry[ (string) $sub_id ] ) && '' !== $entry[ (string) $sub_id ] ) {
							$sub_values[] = $entry[ (string) $sub_id ];
						}
					}
					$val = implode( ' ', $sub_values );
				} else {
					$val = isset( $entry[ (string) $f_id ] ) ? $entry[ (string) $f_id ] : '';
				}

				if ( empty( $submitter_email ) && ( 'email' === $f_type || false !== stripos( $f_label, 'email' ) ) ) {
					if ( is_string( $val ) && is_email( $val ) ) {
						$submitter_email = $val;
					}
				}

				if ( empty( $submitter_name ) && ( 'name' === $f_type || false !== stripos( $f_label, 'name' ) ) ) {
					if ( is_string( $val ) && ! empty( $val ) && ! is_email( $val ) ) {
						$submitter_name = $val;
					}
				}

				$fields[] = array(
					'name'  => 'input_' . $f_id,
					'label' => $f_label,
					'type'  => $f_type,
					'value' => $val,
				);
			}
		}

		$payload = array(
			'source_form_id'   => $form_id,
			'source_form_name' => $form_title,
			'source_entry_id'  => $entry_id,
			'source_url'       => isset( $entry['source_url'] ) ? $entry['source_url'] : '',
			'submitter_name'   => $submitter_name,
			'submitter_email'  => $submitter_email,
			'submitter_ip'     => isset( $entry['ip'] ) ? $entry['ip'] : '',
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
		if ( ! $this->is_active() || ! class_exists( 'GFAPI' ) ) {
			return array();
		}

		$forms = GFAPI::get_forms();
		$list  = array();

		if ( is_array( $forms ) ) {
			foreach ( $forms as $f ) {
				/* translators: %s: Gravity Form ID */
				$fallback_title = sprintf( __( 'Gravity Form #%s', 'ab-form-entries-manager' ), $f['id'] );
				$list[] = array(
					'id'   => (string) $f['id'],
					'name' => ! empty( $f['title'] ) ? $f['title'] : $fallback_title,
				);
			}
		}

		return $list;
	}
}
