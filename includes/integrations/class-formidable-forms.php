<?php
/**
 * Formidable Forms integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Formidable_Forms
 */
class ABFEM_Formidable_Forms extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'formidable';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Formidable Forms';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'formidable/formidable.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'FrmForm' ) || class_exists( 'FrmEntry' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( class_exists( 'FrmAppHelper' ) && method_exists( 'FrmAppHelper', 'plugin_version' ) ) {
			return FrmAppHelper::plugin_version();
		}
		return $this->get_version_from_file( 'formidable/formidable.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions created in Formidable Forms.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Hooks into the official "frm_after_create_entry" action. Supports both Lite and Pro editions.', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'frm_after_create_entry', array( $this, 'handle_submission' ), 10, 2 );
	}

	/**
	 * Handle Formidable Forms entry creation.
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $form_id  Form ID.
	 */
	public function handle_submission( $entry_id, $form_id ) {
		if ( ! class_exists( 'FrmEntry' ) ) {
			return;
		}

		$entry = FrmEntry::getOne( $entry_id, true );
		if ( ! $entry ) {
			return;
		}

		/* translators: %d: Formidable Form ID */
		$form_name = sprintf( __( 'Formidable Form #%d', 'ab-form-entries-manager' ), $form_id );
		if ( class_exists( 'FrmForm' ) ) {
			$form_obj = FrmForm::getOne( $form_id );
			if ( $form_obj && ! empty( $form_obj->name ) ) {
				$form_name = $form_obj->name;
			}
		}

		$fields_meta = array();
		if ( class_exists( 'FrmField' ) ) {
			$raw_fields = FrmField::get_all_for_form( $form_id );
			if ( is_array( $raw_fields ) ) {
				foreach ( $raw_fields as $f ) {
					$fields_meta[ $f->id ] = array(
						'name'  => $f->field_key,
						'label' => $f->name,
						'type'  => $f->type,
					);
				}
			}
		}

		$normalized_fields = array();
		$submitter_name    = '';
		$submitter_email   = '';

		$entry_metas = isset( $entry->metas ) && is_array( $entry->metas ) ? $entry->metas : array();

		foreach ( $entry_metas as $f_id => $val ) {
			$meta = $fields_meta[ $f_id ] ?? array();
			$f_name  = $meta['name'] ?? ( 'field_' . $f_id );
			/* translators: %s: Field ID */
			$f_label = $meta['label'] ?? sprintf( __( 'Field %s', 'ab-form-entries-manager' ), $f_id );
			$f_type  = $meta['type'] ?? $this->detect_field_type( $f_name, $val );

			if ( empty( $submitter_email ) && ( 'email' === $f_type || false !== stripos( $f_name, 'email' ) || false !== stripos( $f_label, 'email' ) ) ) {
				if ( is_string( $val ) && is_email( $val ) ) {
					$submitter_email = $val;
				}
			}

			if ( empty( $submitter_name ) && ( 'name' === $f_type || false !== stripos( $f_name, 'name' ) || false !== stripos( $f_label, 'name' ) ) ) {
				if ( is_string( $val ) && ! empty( $val ) && ! is_email( $val ) ) {
					$submitter_name = $val;
				}
			}

			$normalized_fields[] = array(
				'name'  => $f_name,
				'label' => $f_label,
				'type'  => $f_type,
				'value' => $val,
			);
		}

		$payload = array(
			'source_form_id'   => (string) $form_id,
			'source_form_name' => $form_name,
			'source_entry_id'  => (string) $entry_id,
			'source_url'       => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
			'submitter_name'   => $submitter_name,
			'submitter_email'  => $submitter_email,
			'submitter_ip'     => isset( $entry->ip ) ? $entry->ip : '',
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
		if ( ! $this->is_active() || ! class_exists( 'FrmForm' ) ) {
			return array();
		}

		$forms = FrmForm::getAll( array( 'is_template' => 0 ) );
		$list  = array();

		if ( is_array( $forms ) ) {
			foreach ( $forms as $f ) {
				/* translators: %d: Formidable Form ID */
				$fallback_name = sprintf( __( 'Formidable Form #%d', 'ab-form-entries-manager' ), $f->id );
				$list[] = array(
					'id'   => (string) $f->id,
					'name' => ! empty( $f->name ) ? $f->name : $fallback_name,
				);
			}
		}

		return $list;
	}
}
