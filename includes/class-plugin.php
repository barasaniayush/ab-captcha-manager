<?php
/**
 * Main coordinator and container for AB Form Entries Manager.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Plugin
 */
class ABFEM_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var ABFEM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Integrations manager instance.
	 *
	 * @var ABFEM_Integrations_Manager
	 */
	public $integrations_manager;

	/**
	 * Admin controller instance.
	 *
	 * @var ABFEM_Admin
	 */
	public $admin;

	/**
	 * Initialization state guard.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Get singleton instance.
	 *
	 * @return ABFEM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {
		$this->integrations_manager = new ABFEM_Integrations_Manager();
		$this->admin                = new ABFEM_Admin( $this->integrations_manager );
	}

	/**
	 * Initialize plugin hooks.
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		// Register custom post type.
		add_action( 'init', array( 'ABFEM_Storage', 'register_post_type' ), 0 );

		// Initialize active form integrations on plugins_loaded / init.
		add_action( 'init', array( $this->integrations_manager, 'init' ), 20 );

		// Initialize Privacy / GDPR exporter & eraser.
		ABFEM_Privacy::init();

		// Admin controller init.
		if ( is_admin() ) {
			$this->admin->init();
		}

		// Email notification upon new submission.
		add_action( 'abfem_submission_saved', array( $this, 'send_submission_email_notification' ), 10, 2 );

		// Daily data retention purge cron task.
		add_action( 'abfem_daily_retention_cleanup', array( $this, 'run_daily_retention_cleanup' ) );
	}

	/**
	 * Send email notification to site admin or configured recipients.
	 *
	 * @param int   $entry_id   Created post ID.
	 * @param array $normalized Normalized submission data.
	 */
	public function send_submission_email_notification( $entry_id, array $normalized ) {
		if ( ! ABFEM_Settings::get( 'email_notifications', 0 ) ) {
			return;
		}

		$to = ABFEM_Settings::get( 'notification_email', get_option( 'admin_email' ) );
		if ( empty( $to ) ) {
			$to = get_option( 'admin_email' );
		}

		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$form_name = ! empty( $normalized['source_form_name'] ) ? $normalized['source_form_name'] : __( 'Form', 'ab-form-entries-manager' );

		$subject_tpl = ABFEM_Settings::get( 'notification_subject', '[{site_name}] New Form Submission: {form_name}' );
		$subject = str_replace(
			array( '{site_name}', '{form_name}', '{entry_id}' ),
			array( $site_name, $form_name, $entry_id ),
			$subject_tpl
		);

		$view_url = admin_url( 'admin.php?page=abfem-submissions&action=view&entry_id=' . $entry_id );

		/* translators: %s: Site name */
		$body  = sprintf( __( "Hello,\n\nA new form submission has been captured by AB Form Entries Manager on %s.\n\n", 'ab-form-entries-manager' ), $site_name );
		/* translators: 1: Form name, 2: Form plugin name */
		$body .= sprintf( __( "Form: %1\$s (%2\$s)\n", 'ab-form-entries-manager' ), $form_name, $normalized['source_plugin_name'] ?? '' );
		/* translators: %d: Submission post ID */
		$body .= sprintf( __( "Submission ID: #%d\n", 'ab-form-entries-manager' ), $entry_id );
		/* translators: %s: Submission date */
		$body .= sprintf( __( "Date: %s\n", 'ab-form-entries-manager' ), current_time( 'mysql' ) );

		if ( ! empty( $normalized['submitter_name'] ) ) {
			/* translators: %s: Submitter name */
			$body .= sprintf( __( "Submitter Name: %s\n", 'ab-form-entries-manager' ), $normalized['submitter_name'] );
		}
		if ( ! empty( $normalized['submitter_email'] ) ) {
			/* translators: %s: Submitter email */
			$body .= sprintf( __( "Submitter Email: %s\n", 'ab-form-entries-manager' ), $normalized['submitter_email'] );
		}

		$body .= "\n" . __( "--- Submitted Fields ---", 'ab-form-entries-manager' ) . "\n";

		if ( ! empty( $normalized['fields'] ) && is_array( $normalized['fields'] ) ) {
			foreach ( $normalized['fields'] as $f ) {
				$label = ! empty( $f['label'] ) ? $f['label'] : ( $f['name'] ?? __( 'Field', 'ab-form-entries-manager' ) );
				$val   = $f['value'] ?? '';
				if ( is_array( $val ) ) {
					$val = implode( ', ', $val );
				}
				$body .= sprintf( "%s: %s\n", $label, (string) $val );
			}
		}

		/* translators: %s: Admin submission view URL */
		$body .= "\n" . sprintf( __( "View Submission in Admin: %s\n", 'ab-form-entries-manager' ), $view_url );

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( ! empty( $normalized['submitter_email'] ) && is_email( $normalized['submitter_email'] ) ) {
			$reply_name = ! empty( $normalized['submitter_name'] ) ? $normalized['submitter_name'] : $normalized['submitter_email'];
			$headers[]  = sprintf( 'Reply-To: %s <%s>', $reply_name, $normalized['submitter_email'] );
		}

		wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Cron job handler to purge old submissions if retention is configured.
	 */
	public function run_daily_retention_cleanup() {
		$retention_days = absint( ABFEM_Settings::get( 'retention_days', 0 ) );
		if ( $retention_days > 0 ) {
			$deleted = ABFEM_Storage::purge_old_entries( $retention_days );
			if ( $deleted > 0 ) {
				ABFEM_Submission_Service::log( sprintf( 'Data retention: purged %d submissions older than %d days.', $deleted, $retention_days ) );
			}
		}
	}
}
