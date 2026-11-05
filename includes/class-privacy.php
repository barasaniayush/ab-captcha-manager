<?php
/**
 * WordPress Core Privacy (GDPR) Exporter and Eraser integration.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Privacy
 */
class ABFEM_Privacy {

	/**
	 * Register privacy hooks.
	 */
	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * Register personal data exporter.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['ab-form-entries-manager'] = array(
			'exporter_friendly_name' => __( 'AB Form Entries', 'ab-form-entries-manager' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Export personal data for an email address.
	 *
	 * @param string $email_address Submitter email.
	 * @param int    $page          Pagination page.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		$data_to_export = array();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_abfem_submitter_email'
				 WHERE p.post_type = %s AND p.post_status = 'publish' AND pm.meta_value = %s
				 LIMIT 100 OFFSET %d",
				ABFEM_POST_TYPE,
				$email_address,
				( $page - 1 ) * 100
			)
		);

		if ( ! empty( $entries ) ) {
			foreach ( $entries as $entry_id ) {
				$entry = ABFEM_Storage::get_entry( $entry_id );
				if ( ! $entry ) {
					continue;
				}

				$item_data = array(
					array(
						'name'  => __( 'Submission ID', 'ab-form-entries-manager' ),
						'value' => $entry['id'],
					),
					array(
						'name'  => __( 'Date', 'ab-form-entries-manager' ),
						'value' => $entry['date'],
					),
					array(
						'name'  => __( 'Form Name', 'ab-form-entries-manager' ),
						'value' => $entry['form_name'],
					),
					array(
						'name'  => __( 'Source Plugin', 'ab-form-entries-manager' ),
						'value' => $entry['source_plugin_name'],
					),
				);

				if ( ! empty( $entry['fields'] ) && is_array( $entry['fields'] ) ) {
					foreach ( $entry['fields'] as $f ) {
						$val = is_array( $f['value'] ) ? implode( ', ', $f['value'] ) : (string) $f['value'];
						$item_data[] = array(
							'name'  => $f['label'],
							'value' => $val,
						);
					}
				}

				$data_to_export[] = array(
					'group_id'    => 'abfem_entries',
					'group_label' => __( 'Form Submissions', 'ab-form-entries-manager' ),
					'item_id'     => 'abfem_entry_' . $entry['id'],
					'data'        => $item_data,
				);
			}
		}

		$done = count( $entries ) < 100;
		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Register personal data eraser.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['ab-form-entries-manager'] = array(
			'eraser_friendly_name' => __( 'AB Form Entries', 'ab-form-entries-manager' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Erase personal data for an email address.
	 *
	 * @param string $email_address Submitter email.
	 * @param int    $page          Pagination page.
	 * @return array
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_abfem_submitter_email'
				 WHERE p.post_type = %s AND p.post_status = 'publish' AND pm.meta_value = %s
				 LIMIT 100 OFFSET %d",
				ABFEM_POST_TYPE,
				$email_address,
				( $page - 1 ) * 100
			)
		);

		$items_removed = 0;
		if ( ! empty( $entries ) ) {
			foreach ( $entries as $entry_id ) {
				if ( ABFEM_Storage::delete_entry( (int) $entry_id, true ) ) {
					$items_removed++;
				}
			}
		}

		$done = count( $entries ) < 100;
		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => $done,
		);
	}
}
