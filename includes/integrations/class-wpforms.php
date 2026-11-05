<?php
/**
 * WPForms integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_WPForms
 */
class ABFEM_WPForms extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'wpforms';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'WPForms';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'wpforms-lite/wpforms.php' ) || $this->plugin_file_exists( 'wpforms/wpforms.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return function_exists( 'wpforms' ) || class_exists( 'WPForms' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( defined( 'WPFORMS_VERSION' ) ) {
			return WPFORMS_VERSION;
		}
		$ver = $this->get_version_from_file( 'wpforms-lite/wpforms.php' );
		if ( ! empty( $ver ) ) {
			return $ver;
		}
		return $this->get_version_from_file( 'wpforms/wpforms.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions from both WPForms Lite and WPForms Pro.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Works seamlessly with WPForms Lite without requiring the paid WPForms entry storage addon. Hooks into "wpforms_process_complete".', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'wpforms_process_complete', array( $this, 'handle_submission' ), 10, 4 );
	}

	/**
	 * Handle WPForms submission.
	 *
	 * @param array $fields    Processed form fields.
	 * @param array $entry     Raw $_POST entry data.
	 * @param array $form_data Form settings and field definitions.
	 * @param int   $entry_id  WPForms entry ID (if pro).
	 */
	public function handle_submission( $fields, $entry, $form_data, $entry_id = 0 ) {
		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return;
		}

		$form_id    = isset( $form_data['id'] ) ? (string) $form_data['id'] : '';
		/* translators: %s: WPForms ID */
		$form_title = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : sprintf( __( 'WPForms #%s', 'ab-form-entries-manager' ), $form_id );

		$normalized_fields = array();
		$submitter_name    = '';
		$submitter_email   = '';

		if ( ! empty( $fields ) && is_array( $fields ) ) {
			foreach ( $fields as $f_id => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}

				/* translators: %s: Field ID */
				$label = ! empty( $field['name'] ) ? $field['name'] : sprintf( __( 'Field %s', 'ab-form-entries-manager' ), $f_id );
				$type  = ! empty( $field['type'] ) ? $field['type'] : 'text';
				$val   = isset( $field['value'] ) ? $field['value'] : '';

				if ( empty( $submitter_email ) && ( 'email' === $type || false !== stripos( $label, 'email' ) ) ) {
					if ( is_string( $val ) && is_email( $val ) ) {
						$submitter_email = $val;
					}
				}

				if ( empty( $submitter_name ) && ( 'name' === $type || false !== stripos( $label, 'name' ) ) ) {
					if ( is_string( $val ) && ! empty( $val ) && ! is_email( $val ) ) {
						$submitter_name = $val;
					}
				}

				$normalized_fields[] = array(
					'name'  => 'wpforms_field_' . $f_id,
					'label' => $label,
					'type'  => $type,
					'value' => $val,
				);
			}
		}

		// Source page URL.
		$source_url = '';
		if ( ! empty( $entry['post_id'] ) ) {
			$source_url = get_permalink( absint( $entry['post_id'] ) );
		}
		if ( empty( $source_url ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
			$source_url = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}

		$payload = array(
			'source_form_id'   => $form_id,
			'source_form_name' => $form_title,
			'source_entry_id'  => ! empty( $entry_id ) ? (string) $entry_id : '',
			'source_url'       => $source_url,
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
		if ( ! $this->is_active() ) {
			return array();
		}

		$posts = get_posts( array(
			'post_type'      => 'wpforms',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$forms = array();
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $p ) {
				/* translators: %d: WPForms ID */
				$fallback_title = sprintf( __( 'WPForms #%d', 'ab-form-entries-manager' ), $p->ID );
				$forms[] = array(
					'id'   => (string) $p->ID,
					'name' => $p->post_title ?: $fallback_title,
				);
			}
		}

		return $forms;
	}
}
