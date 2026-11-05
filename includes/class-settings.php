<?php
/**
 * Plugin settings management.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Settings
 */
class ABFEM_Settings {

	/**
	 * Option key in wp_options.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'abfem_settings';

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $settings = null;

	/**
	 * Get all default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'entries_per_page'         => 20,
			'date_format'              => 'wp',
			'auto_mark_read_on_view'   => 1,
			'menu_unread_badge'        => 1,
			'admin_bar_menu'           => 1,
			'dashboard_widget'         => 1,
			'dashboard_notice'         => 1,
			'anonymize_ip'             => 0,
			'retention_days'           => 0, // 0 = disabled.
			'enabled_integrations'     => array(
				'cf7'          => 1,
				'elementor'    => 1,
				'gravityforms' => 1,
				'ninjaforms'   => 1,
				'wpforms'      => 1,
				'jetpack'      => 1,
				'formidable'   => 1,
				'forminator'   => 1,
			),
			'excluded_fields'          => "password\npass\npwd\ncredit_card\ncc_number\ncvc\ncvv\ntoken\nsecret",
			'email_notifications'      => 0,
			'notification_email'       => get_option( 'admin_email', '' ),
			'notification_subject'     => '[{site_name}] New Form Submission: {form_name}',
			'debug_log'                => 0,
			'delete_data_on_uninstall' => 0,
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( null !== self::$settings ) {
			return self::$settings;
		}

		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		self::$settings = wp_parse_args( $saved, self::get_defaults() );
		return self::$settings;
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Optional default if not found.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		if ( isset( $all[ $key ] ) ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Check if an integration is enabled.
	 *
	 * @param string $integration_id Integration identifier.
	 * @return bool
	 */
	public static function is_integration_enabled( $integration_id ) {
		$enabled = self::get( 'enabled_integrations', array() );
		if ( ! is_array( $enabled ) ) {
			return true;
		}

		$registered = array( 'cf7', 'elementor', 'gravityforms', 'ninjaforms', 'wpforms', 'jetpack', 'formidable', 'forminator' );
		if ( ! in_array( $integration_id, $registered, true ) ) {
			return true;
		}

		return ! empty( $enabled[ $integration_id ] );
	}

	/**
	 * Save updated settings.
	 *
	 * @param array $new_settings Raw settings from form submission.
	 * @return bool
	 */
	public static function save( array $new_settings ) {
		$current = self::get_all();
		$clean   = array();

		// Entries per page.
		if ( isset( $new_settings['entries_per_page'] ) ) {
			$per_page = absint( $new_settings['entries_per_page'] );
			if ( $per_page < 5 ) {
				$per_page = 5;
			} elseif ( $per_page > 200 ) {
				$per_page = 200;
			}
			$clean['entries_per_page'] = $per_page;
		} else {
			$clean['entries_per_page'] = $current['entries_per_page'];
		}

		// Date format.
		if ( isset( $new_settings['date_format'] ) ) {
			$date_format = sanitize_text_field( $new_settings['date_format'] );
			$clean['date_format'] = in_array( $date_format, array( 'wp', 'iso', 'human', 'custom' ), true ) ? $date_format : 'wp';
		} else {
			$clean['date_format'] = $current['date_format'];
		}

		// Auto mark read on view.
		if ( isset( $new_settings['auto_mark_read_on_view'] ) ) {
			$clean['auto_mark_read_on_view'] = ! empty( $new_settings['auto_mark_read_on_view'] ) ? 1 : 0;
		} else {
			$clean['auto_mark_read_on_view'] = $current['auto_mark_read_on_view'] ?? 1;
		}

		// Menu unread badge.
		if ( isset( $new_settings['menu_unread_badge'] ) ) {
			$clean['menu_unread_badge'] = ! empty( $new_settings['menu_unread_badge'] ) ? 1 : 0;
		} else {
			$clean['menu_unread_badge'] = $current['menu_unread_badge'] ?? 1;
		}

		// Admin bar menu.
		if ( isset( $new_settings['admin_bar_menu'] ) ) {
			$clean['admin_bar_menu'] = ! empty( $new_settings['admin_bar_menu'] ) ? 1 : 0;
		} else {
			$clean['admin_bar_menu'] = $current['admin_bar_menu'] ?? 1;
		}

		// Dashboard widget.
		if ( isset( $new_settings['dashboard_widget'] ) ) {
			$clean['dashboard_widget'] = ! empty( $new_settings['dashboard_widget'] ) ? 1 : 0;
		} else {
			$clean['dashboard_widget'] = $current['dashboard_widget'] ?? 1;
		}

		// Dashboard notice.
		if ( isset( $new_settings['dashboard_notice'] ) ) {
			$clean['dashboard_notice'] = ! empty( $new_settings['dashboard_notice'] ) ? 1 : 0;
		} else {
			$clean['dashboard_notice'] = $current['dashboard_notice'] ?? 1;
		}

		// Anonymize IP.
		if ( isset( $new_settings['anonymize_ip'] ) ) {
			$clean['anonymize_ip'] = ! empty( $new_settings['anonymize_ip'] ) ? 1 : 0;
		} else {
			$clean['anonymize_ip'] = $current['anonymize_ip'];
		}

		// Retention days.
		if ( isset( $new_settings['retention_days'] ) ) {
			$retention = absint( $new_settings['retention_days'] );
			$allowed_retention = array( 0, 30, 60, 90, 180, 365 );
			$clean['retention_days'] = in_array( $retention, $allowed_retention, true ) ? $retention : 0;
		} else {
			$clean['retention_days'] = $current['retention_days'];
		}

		// Enabled integrations.
		if ( isset( $new_settings['enabled_integrations'] ) && is_array( $new_settings['enabled_integrations'] ) ) {
			$clean['enabled_integrations'] = array();
			$valid_integrations = array( 'cf7', 'elementor', 'gravityforms', 'ninjaforms', 'wpforms', 'jetpack', 'formidable', 'forminator' );
			foreach ( $valid_integrations as $int_id ) {
				$clean['enabled_integrations'][ $int_id ] = ! empty( $new_settings['enabled_integrations'][ $int_id ] ) ? 1 : 0;
			}
		} else {
			$clean['enabled_integrations'] = $current['enabled_integrations'];
		}

		// Excluded fields.
		if ( isset( $new_settings['excluded_fields'] ) ) {
			$clean['excluded_fields'] = sanitize_textarea_field( $new_settings['excluded_fields'] );
		} else {
			$clean['excluded_fields'] = $current['excluded_fields'];
		}

		// Email notifications.
		if ( isset( $new_settings['email_notifications'] ) ) {
			$clean['email_notifications'] = ! empty( $new_settings['email_notifications'] ) ? 1 : 0;
		} else {
			$clean['email_notifications'] = $current['email_notifications'] ?? 0;
		}

		// Notification email (supports single or comma-separated emails).
		if ( isset( $new_settings['notification_email'] ) ) {
			$raw_emails   = explode( ',', (string) $new_settings['notification_email'] );
			$valid_emails = array();
			foreach ( $raw_emails as $em ) {
				$sanitized = sanitize_email( trim( $em ) );
				if ( is_email( $sanitized ) ) {
					$valid_emails[] = $sanitized;
				}
			}
			$clean['notification_email'] = ! empty( $valid_emails ) ? implode( ', ', $valid_emails ) : sanitize_email( get_option( 'admin_email', '' ) );
		} else {
			$clean['notification_email'] = $current['notification_email'] ?? get_option( 'admin_email', '' );
		}

		// Notification subject.
		if ( isset( $new_settings['notification_subject'] ) ) {
			$clean['notification_subject'] = sanitize_text_field( $new_settings['notification_subject'] );
		} else {
			$clean['notification_subject'] = $current['notification_subject'] ?? '[{site_name}] New Form Submission: {form_name}';
		}

		// Debug log.
		if ( isset( $new_settings['debug_log'] ) ) {
			$clean['debug_log'] = ! empty( $new_settings['debug_log'] ) ? 1 : 0;
		} else {
			$clean['debug_log'] = $current['debug_log'];
		}

		// Delete data on uninstall.
		if ( isset( $new_settings['delete_data_on_uninstall'] ) ) {
			$clean['delete_data_on_uninstall'] = ! empty( $new_settings['delete_data_on_uninstall'] ) ? 1 : 0;
		} else {
			$clean['delete_data_on_uninstall'] = $current['delete_data_on_uninstall'];
		}

		self::$settings = $clean;
		return update_option( self::OPTION_KEY, $clean );
	}
}
