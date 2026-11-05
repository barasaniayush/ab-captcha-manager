<?php
/**
 * Contact Form 7 integration adapter.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Contact_Form_7
 */
class ABFEM_Contact_Form_7 extends ABFEM_Base_Integration {

	/**
	 * Unique identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'cf7';
	}

	/**
	 * Display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Contact Form 7';
	}

	/**
	 * Check if installed.
	 *
	 * @return bool
	 */
	public function is_installed() {
		return $this->plugin_file_exists( 'contact-form-7/wp-contact-form-7.php' );
	}

	/**
	 * Check if active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return class_exists( 'WPCF7_ContactForm' );
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( defined( 'WPCF7_VERSION' ) ) {
			return WPCF7_VERSION;
		}
		return $this->get_version_from_file( 'contact-form-7/wp-contact-form-7.php' );
	}

	/**
	 * Short description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Captures submissions when Contact Form 7 sends an email notification successfully.', 'ab-form-entries-manager' );
	}

	/**
	 * Notes.
	 *
	 * @return string
	 */
	public function get_notes() {
		return __( 'Hooks into the official "wpcf7_mail_sent" action. File uploads and multi-select tags are fully supported.', 'ab-form-entries-manager' );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'wpcf7_mail_sent', array( $this, 'handle_submission' ), 10, 1 );
	}

	/**
	 * Handle CF7 submission.
	 *
	 * @param WPCF7_ContactForm $contact_form CF7 object.
	 */
	public function handle_submission( $contact_form ) {
		if ( ! is_a( $contact_form, 'WPCF7_ContactForm' ) ) {
			return;
		}

		$submission = class_exists( 'WPCF7_Submission' ) ? WPCF7_Submission::get_instance() : null;
		if ( ! $submission ) {
			return;
		}

		$posted_data    = (array) $submission->get_posted_data();
		$uploaded_files = (array) $submission->uploaded_files();

		$form_id    = (string) $contact_form->id();
		$form_title = $contact_form->title();

		// Tag mapping for accurate labels and types.
		$tags_info = array();
		if ( method_exists( $contact_form, 'scan_form_tags' ) ) {
			$tags = $contact_form->scan_form_tags();
			if ( is_array( $tags ) ) {
				foreach ( $tags as $tag ) {
					if ( ! empty( $tag->name ) ) {
						$tags_info[ $tag->name ] = array(
							'type'  => $tag->basetype ?: $tag->type,
							'label' => ! empty( $tag->labels[0] ) ? $tag->labels[0] : ucwords( str_replace( array( '-', '_' ), ' ', $tag->name ) ),
						);
					}
				}
			}
		}

		$fields          = array();
		$submitter_name  = '';
		$submitter_email = '';

		// Exclude internal CF7 keys.
		$internal_keys = array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post', '_wpcf7_posted_data_hash' );

		foreach ( $posted_data as $key => $val ) {
			if ( in_array( $key, $internal_keys, true ) || 0 === strpos( $key, '_wpcf7' ) ) {
				continue;
			}

			$label = isset( $tags_info[ $key ]['label'] ) ? $tags_info[ $key ]['label'] : ucwords( str_replace( array( '-', '_' ), ' ', $key ) );
			$type  = isset( $tags_info[ $key ]['type'] ) ? $tags_info[ $key ]['type'] : $this->detect_field_type( $key, $val );

			// Check for submitter identity.
			if ( empty( $submitter_email ) && ( 'email' === $type || false !== stripos( $key, 'email' ) ) ) {
				if ( is_string( $val ) && is_email( $val ) ) {
					$submitter_email = $val;
				}
			}

			if ( empty( $submitter_name ) && ( false !== stripos( $key, 'name' ) || false !== stripos( $label, 'name' ) ) ) {
				if ( is_string( $val ) && ! empty( $val ) && ! is_email( $val ) ) {
					$submitter_name = $val;
				}
			}

			$fields[] = array(
				'name'  => $key,
				'label' => $label,
				'type'  => $type,
				'value' => $val,
			);
		}

		// Include uploaded file records.
		foreach ( $uploaded_files as $f_key => $file_paths ) {
			if ( empty( $file_paths ) ) {
				continue;
			}
			$file_names = array();
			if ( is_array( $file_paths ) ) {
				foreach ( $file_paths as $fp ) {
					$file_names[] = basename( $fp );
				}
			} else {
				$file_names[] = basename( $file_paths );
			}

			$fields[] = array(
				'name'  => $f_key,
				'label' => isset( $tags_info[ $f_key ]['label'] ) ? $tags_info[ $f_key ]['label'] : ucwords( str_replace( array( '-', '_' ), ' ', $f_key ) ),
				'type'  => 'file',
				'value' => implode( ', ', $file_names ),
			);
		}

		$payload = array(
			'source_form_id'   => $form_id,
			'source_form_name' => $form_title,
			'source_url'       => $submission->get_meta( 'url' ) ?: ( isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' ),
			'submitter_name'   => $submitter_name,
			'submitter_email'  => $submitter_email,
			'submitter_ip'     => $submission->get_meta( 'remote_ip' ) ?: '',
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
		if ( ! $this->is_active() ) {
			return array();
		}

		$posts = get_posts( array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$forms = array();
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $p ) {
				/* translators: %d: Contact Form 7 ID */
				$fallback_title = sprintf( __( 'CF7 Form #%d', 'ab-form-entries-manager' ), $p->ID );
				$forms[] = array(
					'id'   => (string) $p->ID,
					'name' => $p->post_title ?: $fallback_title,
				);
			}
		}

		return $forms;
	}
}
