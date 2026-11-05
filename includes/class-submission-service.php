<?php
/**
 * Ingestion and processing service for form submissions.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Submission_Service
 */
class ABFEM_Submission_Service {

	/**
	 * Process and store an incoming form submission.
	 *
	 * @param array $payload Normalized submission data from an adapter.
	 * @return int|false Entry ID if saved, false on duplicate or error.
	 */
	public static function process_submission( array $payload ) {
		try {
			// Check if source integration is enabled in settings.
			$plugin_id = ! empty( $payload['source_plugin'] ) ? sanitize_key( $payload['source_plugin'] ) : '';
			if ( ! empty( $plugin_id ) && ! ABFEM_Settings::is_integration_enabled( $plugin_id ) ) {
				self::log( sprintf( 'Skipping submission: integration "%s" is disabled in settings.', $plugin_id ) );
				return false;
			}

			// Sanitize and normalize payload.
			$normalized = self::sanitize_payload( $payload );

			// Compute duplicate detection hash.
			$hash_content = sprintf(
				'%s|%s|%s|%s',
				$normalized['source_plugin'],
				$normalized['source_form_id'],
				$normalized['submitter_email'],
				wp_json_encode( $normalized['fields'] )
			);
			$hash = md5( $hash_content );
			$normalized['hash'] = $hash;

			// Check for duplicate within 15-second window.
			if ( ABFEM_Storage::is_duplicate( $hash, 15 ) ) {
				self::log( sprintf( 'Duplicate submission prevented for form ID "%s".', $normalized['source_form_id'] ) );
				return false;
			}

			// Store entry.
			$entry_id = ABFEM_Storage::create_entry( $normalized );

			if ( is_wp_error( $entry_id ) ) {
				self::log( 'Failed to store submission: ' . $entry_id->get_error_message() );
				return false;
			}

			self::log( sprintf( 'Successfully stored submission #%d for %s form "%s".', $entry_id, $normalized['source_plugin_name'], $normalized['source_form_name'] ) );

			/**
			 * Fires immediately after an AB Form entry has been created and saved.
			 *
			 * @param int   $entry_id   The newly created post ID.
			 * @param array $normalized The normalized submission data.
			 */
			do_action( 'abfem_submission_saved', $entry_id, $normalized );

			return $entry_id;

		} catch ( \Throwable $e ) {
			// Catch any unexpected exceptions to guarantee the form submission process never breaks for the end-user.
			self::log( 'Exception in process_submission: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Sanitize and structure the submission payload.
	 *
	 * @param array $raw Raw input array.
	 * @return array
	 */
	private static function sanitize_payload( array $raw ) {
		$clean = array(
			'source_plugin'      => sanitize_key( $raw['source_plugin'] ?? 'custom' ),
			'source_plugin_name' => sanitize_text_field( $raw['source_plugin_name'] ?? 'Custom Form' ),
			'source_form_id'     => sanitize_text_field( (string) ( $raw['source_form_id'] ?? '' ) ),
			'source_form_name'   => sanitize_text_field( $raw['source_form_name'] ?? __( 'Untitled Form', 'ab-form-entries-manager' ) ),
			'source_entry_id'    => sanitize_text_field( (string) ( $raw['source_entry_id'] ?? '' ) ),
			'source_url'         => esc_url_raw( $raw['source_url'] ?? ( isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' ) ),
			'submitter_name'     => sanitize_text_field( $raw['submitter_name'] ?? '' ),
			'submitter_email'    => sanitize_email( $raw['submitter_email'] ?? '' ),
			'submitter_ip'       => self::get_sanitized_ip( $raw['submitter_ip'] ?? '' ),
			'user_agent'         => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'fields'             => array(),
		);

		// Get excluded field patterns.
		$excluded_patterns = self::get_excluded_field_patterns();

		// Sanitize field data.
		$fields = ! empty( $raw['fields'] ) && is_array( $raw['fields'] ) ? $raw['fields'] : array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$name  = sanitize_text_field( $field['name'] ?? '' );
			$label = sanitize_text_field( $field['label'] ?? ( ! empty( $name ) ? ucwords( str_replace( array( '-', '_' ), ' ', $name ) ) : __( 'Field', 'ab-form-entries-manager' ) ) );
			$type  = sanitize_key( $field['type'] ?? 'text' );
			$val   = $field['value'] ?? '';

			// Skip excluded sensitive fields.
			if ( self::is_field_excluded( $name, $label, $excluded_patterns ) ) {
				continue;
			}

			// Clean the value based on structure.
			$cleaned_val = self::clean_field_value( $val, $type );

			// Infer submitter name or email if empty.
			if ( empty( $clean['submitter_email'] ) && ( 'email' === $type || false !== stripos( $name, 'email' ) || false !== stripos( $label, 'email' ) ) ) {
				if ( is_string( $cleaned_val ) && is_email( $cleaned_val ) ) {
					$clean['submitter_email'] = sanitize_email( $cleaned_val );
				}
			}

			if ( empty( $clean['submitter_name'] ) && ( false !== stripos( $name, 'name' ) || false !== stripos( $label, 'name' ) ) ) {
				if ( is_string( $cleaned_val ) && ! empty( $cleaned_val ) && ! is_email( $cleaned_val ) ) {
					$clean['submitter_name'] = sanitize_text_field( $cleaned_val );
				}
			}

			$clean['fields'][] = array(
				'name'  => $name,
				'label' => $label,
				'type'  => $type,
				'value' => $cleaned_val,
			);
		}

		return $clean;
	}

	/**
	 * Clean and format field values (arrays, scalars, multiline, etc.).
	 *
	 * @param mixed  $val  Raw value.
	 * @param string $type Field type.
	 * @return mixed
	 */
	public static function clean_field_value( $val, $type = 'text' ) {
		if ( is_array( $val ) ) {
			$cleaned = array();
			foreach ( $val as $k => $v ) {
				$cleaned[ sanitize_text_field( (string) $k ) ] = self::clean_field_value( $v, $type );
			}
			return $cleaned;
		}

		if ( 'textarea' === $type ) {
			return sanitize_textarea_field( (string) $val );
		}

		if ( 'url' === $type ) {
			return esc_url_raw( (string) $val );
		}

		if ( 'email' === $type ) {
			return sanitize_email( (string) $val );
		}

		return sanitize_text_field( (string) $val );
	}

	/**
	 * Determine if a field name or label matches excluded sensitive field patterns.
	 *
	 * @param string $name     Field name.
	 * @param string $label    Field label.
	 * @param array  $patterns Excluded patterns.
	 * @return bool
	 */
	private static function is_field_excluded( $name, $label, array $patterns ) {
		$name_lower  = strtolower( $name );
		$label_lower = strtolower( $label );

		foreach ( $patterns as $pat ) {
			if ( empty( $pat ) ) {
				continue;
			}
			if ( false !== strpos( $name_lower, $pat ) || false !== strpos( $label_lower, $pat ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieve compiled list of excluded field patterns.
	 *
	 * @return array
	 */
	private static function get_excluded_field_patterns() {
		$raw = ABFEM_Settings::get( 'excluded_fields', '' );
		$lines = explode( "\n", str_replace( "\r", '', (string) $raw ) );
		$patterns = array();

		foreach ( $lines as $line ) {
			$trimmed = trim( strtolower( $line ) );
			if ( ! empty( $trimmed ) ) {
				$patterns[] = $trimmed;
			}
		}

		// Ensure baseline sensitive defaults are always guarded.
		$baseline = array( 'password', 'pass', 'pwd', 'credit_card', 'cc_number', 'cvc', 'cvv', 'token', 'secret_key' );
		return array_unique( array_merge( $patterns, $baseline ) );
	}

	/**
	 * Get sanitized and optionally anonymized IP address.
	 *
	 * @param string $ip Raw IP if provided.
	 * @return string
	 */
	private static function get_sanitized_ip( $ip = '' ) {
		if ( empty( $ip ) ) {
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
				// Take first IP if comma-separated.
				if ( false !== strpos( $ip, ',' ) ) {
					$parts = explode( ',', $ip );
					$ip = trim( $parts[0] );
				}
			} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}
		}

		if ( empty( $ip ) ) {
			return '';
		}

		// Check anonymize setting.
		if ( ABFEM_Settings::get( 'anonymize_ip', 0 ) ) {
			if ( function_exists( 'wp_privacy_anonymize_ip' ) ) {
				return wp_privacy_anonymize_ip( $ip );
			}
			// Fallback anonymization for IPv4: zero out last octet.
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$parts = explode( '.', $ip );
				$parts[3] = '0';
				return implode( '.', $parts );
			}
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Write to debug log if debugging is enabled.
	 *
	 * @param string $message Log message.
	 */
	public static function log( $message ) {
		if ( ABFEM_Settings::get( 'debug_log', 0 ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[AB Form Entries Manager] ' . $message );
		}
	}
}
