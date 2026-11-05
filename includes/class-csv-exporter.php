<?php
/**
 * CSV Exporter for form submissions.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_CSV_Exporter
 */
class ABFEM_CSV_Exporter {

	/**
	 * Run the CSV export and send the download to the browser.
	 *
	 * @param array $args Query/filter parameters.
	 */
	public static function export( array $args = array() ) {
		ABFEM_Capabilities::check_manage_permission();

		// Set memory limit and time limit if permitted.
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.setTimeLimit_setTimeLimit
			@set_time_limit( 300 );
		}

		$filename = sprintf( 'abfem-submissions-%s.csv', gmdate( 'Y-m-d-His' ) );

		// Clean any previous output buffer to avoid corrupting CSV.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Send download HTTP headers.
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );
		if ( ! $output ) {
			exit;
		}

		// Write UTF-8 BOM for Microsoft Excel compatibility.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $output, "\xEF\xBB\xBF" );

		// Step 1: Collect entries.
		if ( ! empty( $args['entry_id'] ) ) {
			$single      = ABFEM_Storage::get_entry( absint( $args['entry_id'] ) );
			$all_entries = $single ? array( $single ) : array();
		} else {
			// Query entries with filter parameters (up to 5000 at once).
			$query_args             = $args;
			$query_args['per_page'] = 5000;
			$query_args['page']     = 1;
			$result                 = ABFEM_Storage::get_entries( $query_args );
			$all_entries            = $result['entries'];
		}

		if ( empty( $all_entries ) ) {
			fputcsv( $output, array( __( 'No submissions found matching criteria.', 'ab-form-entries-manager' ) ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $output );
			exit;
		}

		// Generate formatted and sanitized CSV rows.
		$rows = self::generate_csv_rows( $all_entries );

		foreach ( $rows as $row ) {
			fputcsv( $output, $row );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Build sanitized CSV rows array from submission entry records.
	 *
	 * - Generates sequential serial numbers (S.N.) starting at 1.
	 * - Excludes IP Address, Original Entry ID, and Form ID.
	 * - Extracts and dynamically includes all form fields.
	 *
	 * @param array $all_entries Array of entry records.
	 * @return array Array of rows where row 0 is the header row.
	 */
	public static function generate_csv_rows( array $all_entries ) {
		$rows = array();

		// Step 1: Discover all unique form fields across all entries.
		$dynamic_headers = array();
		foreach ( $all_entries as $entry ) {
			if ( ! empty( $entry['fields'] ) && is_array( $entry['fields'] ) ) {
				foreach ( $entry['fields'] as $key => $f ) {
					$label = '';
					if ( is_array( $f ) ) {
						if ( ! empty( $f['label'] ) ) {
							$label = trim( (string) $f['label'] );
						} elseif ( ! empty( $f['name'] ) ) {
							$label = ucwords( str_replace( array( '_', '-' ), ' ', trim( (string) $f['name'] ) ) );
						} elseif ( is_string( $key ) && ! is_numeric( $key ) ) {
							$label = ucwords( str_replace( array( '_', '-' ), ' ', trim( $key ) ) );
						}
					} else {
						if ( is_string( $key ) && ! is_numeric( $key ) ) {
							$label = ucwords( str_replace( array( '_', '-' ), ' ', trim( $key ) ) );
						} elseif ( is_numeric( $key ) ) {
							/* translators: %d: Field position number */
							$label = sprintf( __( 'Field %d', 'ab-form-entries-manager' ), (int) $key + 1 );
						}
					}

					if ( '' !== $label && ! in_array( $label, $dynamic_headers, true ) ) {
						$dynamic_headers[] = $label;
					}
				}
			}
		}

		// Step 2: Base prefix & suffix columns.
		// User detail fields are placed in the middle.
		// Form Name & Form Builder appear AFTER user detail.
		// Status and Source Page URL appear at the very end.
		$prefix_headers = array(
			__( 'S.N.', 'ab-form-entries-manager' ),
			__( 'Submission Date', 'ab-form-entries-manager' ),
		);

		$suffix_headers = array(
			__( 'Form Name', 'ab-form-entries-manager' ),
			__( 'Form Builder', 'ab-form-entries-manager' ),
			__( 'Status', 'ab-form-entries-manager' ),
			__( 'Source Page URL', 'ab-form-entries-manager' ),
		);

		$csv_headers = array_merge( $prefix_headers, $dynamic_headers, $suffix_headers );
		$rows[]      = array_map( array( __CLASS__, 'sanitize_cell' ), $csv_headers );

		// Step 3: Populate Data Rows with sequential serial number.
		$serial_number = 1;
		foreach ( $all_entries as $entry ) {
			$prefix_values = array(
				$serial_number++,
				$entry['date'],
			);

			// Map all dynamic form fields.
			$entry_field_map = array();
			if ( ! empty( $entry['fields'] ) && is_array( $entry['fields'] ) ) {
				foreach ( $entry['fields'] as $key => $f ) {
					$label = '';
					$val   = '';

					if ( is_array( $f ) ) {
						if ( ! empty( $f['label'] ) ) {
							$label = trim( (string) $f['label'] );
						} elseif ( ! empty( $f['name'] ) ) {
							$label = ucwords( str_replace( array( '_', '-' ), ' ', trim( (string) $f['name'] ) ) );
						} elseif ( is_string( $key ) && ! is_numeric( $key ) ) {
							$label = ucwords( str_replace( array( '_', '-' ), ' ', trim( $key ) ) );
						}
						$val = $f['value'] ?? '';
					} else {
						if ( is_string( $key ) && ! is_numeric( $key ) ) {
							$label = ucwords( str_replace( array( '_', '-' ), ' ', trim( $key ) ) );
						} elseif ( is_numeric( $key ) ) {
							/* translators: %d: Field position number */
							$label = sprintf( __( 'Field %d', 'ab-form-entries-manager' ), (int) $key + 1 );
						}
						$val = $f;
					}

					if ( is_array( $val ) ) {
						$val_str = implode( ', ', array_map( 'strval', $val ) );
					} else {
						$val_str = (string) $val;
					}

					if ( '' !== $label ) {
						$entry_field_map[ $label ] = $val_str;
					}
				}
			}

			$dynamic_values = array();
			foreach ( $dynamic_headers as $h ) {
				$dynamic_values[] = isset( $entry_field_map[ $h ] ) ? $entry_field_map[ $h ] : '';
			}

			$suffix_values = array(
				$entry['form_name'],
				! empty( $entry['source_plugin_name'] ) ? $entry['source_plugin_name'] : ucfirst( (string) $entry['source_plugin'] ),
				! empty( $entry['is_read'] ) ? __( 'Read', 'ab-form-entries-manager' ) : __( 'Unread', 'ab-form-entries-manager' ),
				$entry['source_url'],
			);

			$row    = array_merge( $prefix_values, $dynamic_values, $suffix_values );
			$rows[] = array_map( array( __CLASS__, 'sanitize_cell' ), $row );
		}

		return $rows;
	}

	/**
	 * Sanitize a single cell against CSV formula injection.
	 *
	 * Prepends single quote if the string begins with =, +, -, @, \t, or \r.
	 *
	 * @param mixed $value Cell value.
	 * @return string
	 */
	public static function sanitize_cell( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ', ', array_map( 'strval', $value ) );
		}

		$str = (string) $value;

		// Check for formula injection triggers: =, +, -, @, tab, carriage return.
		if ( '' !== $str && preg_match( '/^[=\+\-\@\t\r]/', $str ) ) {
			return "'" . $str;
		}

		return $str;
	}
}
