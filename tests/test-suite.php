<?php
/**
 * Comprehensive Unit and Functional Test Suite.
 *
 * @package AB_Form_Entries_Manager
 */

// phpcs:ignoreFile

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once __DIR__ . '/bootstrap.php';

class ABFEM_Test_Suite {

	private $passed = 0;
	private $failed = 0;

	public function run() {
		echo "========================================================\n";
		echo "  AB Form Entries Manager - Verification & Test Suite   \n";
		echo "========================================================\n\n";

		$this->test_plugin_constants();
		$this->test_settings_manager();
		$this->test_submission_ingestion_and_storage();
		$this->test_duplicate_prevention();
		$this->test_sensitive_field_filtering();
		$this->test_ip_anonymization();
		$this->test_read_status_and_bulk_operations();
		$this->test_csv_formula_injection_defense();
		$this->test_csv_export_columns_and_formatting();
		$this->test_privacy_gdpr_exporter_and_eraser();
		$this->test_integrations_adapters();
		$this->test_admin_hooks_and_action_links();
		$this->test_admin_menu_highlight_filters();
		$this->test_dashboard_metabox_and_notifications();
		$this->test_ui_standards_and_regressions();

		echo "\n========================================================\n";
		echo sprintf( "Results: %d Passed, %d Failed\n", $this->passed, $this->failed );
		echo "========================================================\n";

		if ( $this->failed > 0 ) {
			exit( 1 );
		}
	}

	private function assert( $condition, $test_name ) {
		if ( $condition ) {
			$this->passed++;
			echo " [PASS] " . $test_name . "\n";
		} else {
			$this->failed++;
			echo " [FAIL] " . $test_name . "\n";
		}
	}

	private function test_plugin_constants() {
		echo "Running Plugin Setup & Constants Tests...\n";
		$this->assert( defined( 'ABFEM_VERSION' ) && '1.0.0' === ABFEM_VERSION, 'ABFEM_VERSION constant is defined and equal to 1.0.0' );
		$this->assert( defined( 'ABFEM_POST_TYPE' ) && 'abfem_entry' === ABFEM_POST_TYPE, 'ABFEM_POST_TYPE is set to abfem_entry' );
		$this->assert( class_exists( 'ABFEM_Plugin' ), 'ABFEM_Plugin class is loaded' );
	}

	private function test_settings_manager() {
		echo "\nRunning Settings Manager Tests...\n";
		$defaults = ABFEM_Settings::get_defaults();
		$this->assert( 20 === $defaults['entries_per_page'], 'Default entries per page is 20' );
		$this->assert( 0 === $defaults['retention_days'], 'Default retention days is 0 (disabled)' );
		$this->assert( 1 === $defaults['enabled_integrations']['cf7'], 'Contact Form 7 integration is enabled by default' );
		$this->assert( 1 === $defaults['enabled_integrations']['wpforms'], 'WPForms integration is enabled by default' );

		// Test saving.
		ABFEM_Settings::save( array(
			'entries_per_page'     => 50,
			'anonymize_ip'         => 1,
			'enabled_integrations' => array(
				'cf7'     => 1,
				'wpforms' => 0,
			),
		) );

		$this->assert( 50 === ABFEM_Settings::get( 'entries_per_page' ), 'Updated entries_per_page persists as 50' );
		$this->assert( 1 === ABFEM_Settings::get( 'anonymize_ip' ), 'Updated anonymize_ip persists as 1' );
		$this->assert( true === ABFEM_Settings::is_integration_enabled( 'cf7' ), 'CF7 remains enabled' );
		$this->assert( false === ABFEM_Settings::is_integration_enabled( 'wpforms' ), 'WPForms disabled successfully in settings' );

		// Test email notification sanitization (single and multiple).
		ABFEM_Settings::save( array(
			'notification_email' => 'admin@example.com, not-an-email, support@example.com',
		) );
		$this->assert( 'admin@example.com, support@example.com' === ABFEM_Settings::get( 'notification_email' ), 'Multiple notification emails sanitized and filtered' );

		// Reset settings to defaults for subsequent tests.
		ABFEM_Settings::save( ABFEM_Settings::get_defaults() );
	}

	private function test_submission_ingestion_and_storage() {
		echo "\nRunning Ingestion and Storage Tests...\n";

		$payload = array(
			'source_plugin'      => 'cf7',
			'source_plugin_name' => 'Contact Form 7',
			'source_form_id'     => '42',
			'source_form_name'   => 'Contact Us Inquiry',
			'source_entry_id'    => 'cf7_sub_101',
			'source_url'         => 'https://example.com/contact-us',
			'submitter_name'     => 'Alice Wonderland',
			'submitter_email'    => 'alice@example.com',
			'submitter_ip'       => '192.168.1.55',
			'fields'             => array(
				array( 'name' => 'your-name', 'label' => 'Your Name', 'type' => 'text', 'value' => 'Alice Wonderland' ),
				array( 'name' => 'your-email', 'label' => 'Your Email', 'type' => 'email', 'value' => 'alice@example.com' ),
				array( 'name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'value' => 'Product Inquiry' ),
				array( 'name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'value' => "Hello!\nI have a question." ),
				array( 'name' => 'services', 'label' => 'Services', 'type' => 'checkbox', 'value' => array( 'Web Design', 'SEO' ) ),
			),
		);

		$entry_id = ABFEM_Submission_Service::process_submission( $payload );
		$this->assert( $entry_id > 0, sprintf( 'Submission successfully stored with post ID #%d', $entry_id ) );

		$entry = ABFEM_Storage::get_entry( $entry_id );
		$this->assert( null !== $entry, 'Entry fetched from storage' );
		$this->assert( 'cf7' === $entry['source_plugin'], 'Source plugin metadata preserved' );
		$this->assert( 'Contact Us Inquiry' === $entry['form_name'], 'Form name metadata preserved' );
		$this->assert( 'alice@example.com' === $entry['submitter_email'], 'Submitter email preserved' );
		$this->assert( 'Alice Wonderland' === $entry['submitter_name'], 'Submitter name preserved' );
		$this->assert( 0 === $entry['is_read'], 'New submission status is unread (0)' );
		$this->assert( 5 === count( $entry['fields'] ), 'All 5 submitted fields captured and persisted' );
	}

	private function test_duplicate_prevention() {
		echo "\nRunning Duplicate Prevention Tests...\n";

		$payload = array(
			'source_plugin'      => 'wpforms',
			'source_plugin_name' => 'WPForms',
			'source_form_id'     => '88',
			'source_form_name'   => 'Newsletter Signup',
			'submitter_name'     => 'Bob Builder',
			'submitter_email'    => 'bob@example.com',
			'fields'             => array(
				array( 'name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => 'bob@example.com' ),
			),
		);

		// First submission must succeed.
		$first_id = ABFEM_Submission_Service::process_submission( $payload );
		$this->assert( $first_id > 0, 'First submission accepted' );

		// Immediate identical second submission within 15 seconds must be caught.
		$second_id = ABFEM_Submission_Service::process_submission( $payload );
		$this->assert( false === $second_id, 'Immediate duplicate submission was blocked' );
	}

	private function test_sensitive_field_filtering() {
		echo "\nRunning Sensitive Field Protection Tests...\n";

		$payload = array(
			'source_plugin'      => 'custom',
			'source_plugin_name' => 'Custom Form',
			'source_form_id'     => '99',
			'source_form_name'   => 'Account Registration',
			'submitter_name'     => 'Charlie Brown',
			'submitter_email'    => 'charlie@example.com',
			'fields'             => array(
				array( 'name' => 'username', 'label' => 'Username', 'type' => 'text', 'value' => 'charlieb' ),
				array( 'name' => 'user_password', 'label' => 'Password', 'type' => 'password', 'value' => 'Secret123!' ),
				array( 'name' => 'credit_card_number', 'label' => 'Credit Card', 'type' => 'text', 'value' => '4111222233334444' ),
				array( 'name' => 'cvv', 'label' => 'Security Code', 'type' => 'text', 'value' => '999' ),
				array( 'name' => 'newsletter_optin', 'label' => 'Subscribe', 'type' => 'checkbox', 'value' => 'yes' ),
			),
		);

		$entry_id = ABFEM_Submission_Service::process_submission( $payload );
		$entry    = ABFEM_Storage::get_entry( $entry_id );

		$captured_field_names = array_column( $entry['fields'], 'name' );
		$this->assert( in_array( 'username', $captured_field_names, true ), 'Safe field "username" was captured' );
		$this->assert( ! in_array( 'user_password', $captured_field_names, true ), 'Sensitive field "user_password" was excluded' );
		$this->assert( ! in_array( 'credit_card_number', $captured_field_names, true ), 'Sensitive field "credit_card_number" was excluded' );
		$this->assert( ! in_array( 'cvv', $captured_field_names, true ), 'Sensitive field "cvv" was excluded' );
	}

	private function test_ip_anonymization() {
		echo "\nRunning IP Anonymization Tests...\n";

		// Turn on IP anonymization.
		ABFEM_Settings::save( array( 'anonymize_ip' => 1 ) );

		$payload = array(
			'source_plugin'      => 'cf7',
			'source_plugin_name' => 'Contact Form 7',
			'source_form_id'     => '101',
			'source_form_name'   => 'GDPR Test',
			'submitter_email'    => 'gdpr@example.com',
			'submitter_ip'       => '192.168.1.185',
			'fields'             => array(),
		);

		$entry_id = ABFEM_Submission_Service::process_submission( $payload );
		$entry    = ABFEM_Storage::get_entry( $entry_id );

		$this->assert( '192.168.1.0' === $entry['submitter_ip'], sprintf( 'IPv4 address was anonymized: %s -> 192.168.1.0', $entry['submitter_ip'] ) );

		// Turn off IP anonymization.
		ABFEM_Settings::save( array( 'anonymize_ip' => 0 ) );
	}

	private function test_read_status_and_bulk_operations() {
		echo "\nRunning Status and Bulk Operations Tests...\n";

		$payload = array(
			'source_plugin'    => 'cf7',
			'source_form_id'   => '201',
			'source_form_name' => 'Status Test',
			'submitter_email'  => 'status@example.com',
			'fields'           => array(),
		);

		$id1 = ABFEM_Submission_Service::process_submission( $payload );
		$payload['source_form_id'] = '202';
		$id2 = ABFEM_Submission_Service::process_submission( $payload );

		// Initially unread.
		$entry1 = ABFEM_Storage::get_entry( $id1 );
		$this->assert( 0 === $entry1['is_read'], 'Entry starts unread' );

		// Mark single read.
		ABFEM_Storage::update_read_status( $id1, 1 );
		$entry1 = ABFEM_Storage::get_entry( $id1 );
		$this->assert( 1 === $entry1['is_read'], 'Entry marked as read' );

		// Bulk mark unread.
		$updated_count = ABFEM_Storage::bulk_update_read_status( array( $id1, $id2 ), 0 );
		$this->assert( 2 === $updated_count, 'Bulk marked 2 entries as unread' );
		$entry1 = ABFEM_Storage::get_entry( $id1 );
		$this->assert( 0 === $entry1['is_read'], 'Entry is unread again' );

		// Mark unread idempotency: updating already unread entry to unread returns true.
		$idem_result = ABFEM_Storage::update_read_status( $id1, 0 );
		$this->assert( true === $idem_result, 'Updating already unread entry to unread succeeds' );

		// Test Detail View auto-mark read behavior vs just_marked_unread.
		// 1. Initial view auto-marks unread entry as read.
		ob_start();
		unset( $_GET['abfem_notice'] );
		ABFEM_Detail_View::render( $id1 );
		ob_end_clean();
		$entry1_viewed = ABFEM_Storage::get_entry( $id1 );
		$this->assert( 1 === $entry1_viewed['is_read'], 'Detail view auto-marks unread entry as read when viewed' );

		// 2. Mark as unread: when redirected to detail view with abfem_notice=unread_marked, status remains unread.
		ABFEM_Storage::update_read_status( $id1, 0 );
		$_GET['abfem_notice'] = 'unread_marked';
		ob_start();
		ABFEM_Detail_View::render( $id1 );
		$output = ob_get_clean();
		$entry1_after_unread = ABFEM_Storage::get_entry( $id1 );
		$this->assert( 0 === $entry1_after_unread['is_read'], 'Detail view preserves unread status when abfem_notice=unread_marked' );
		$this->assert( false !== strpos( $output, 'abfem-status-unread' ), 'Detail view renders Unread status pill' );
		$this->assert( false !== strpos( $output, 'Mark as Read' ), 'Detail view renders Mark as Read button' );
		unset( $_GET['abfem_notice'] );

		// Bulk delete.
		$del_count = ABFEM_Storage::bulk_delete_entries( array( $id1, $id2 ) );
		$this->assert( 2 === $del_count, 'Bulk deleted 2 entries' );
		$this->assert( null === ABFEM_Storage::get_entry( $id1 ), 'Deleted entry 1 is gone' );
		$this->assert( null === ABFEM_Storage::get_entry( $id2 ), 'Deleted entry 2 is gone' );
	}

	private function test_csv_formula_injection_defense() {
		echo "\nRunning CSV Formula Injection Defense Tests...\n";

		$malicious_inputs = array(
			'=cmd|\' /C calc\'!A0' => "'=cmd|' /C calc'!A0",
			'+SUM(A1:A10)'          => "'+SUM(A1:A10)",
			'-2+3*[calc]'          => "'-2+3*[calc]",
			'@SUM(1,2)'            => "'@SUM(1,2)",
			"\tformula"            => "'\tformula",
			"\rformula"            => "'\rformula",
			'Normal text'          => 'Normal text',
			'user@domain.com'      => 'user@domain.com',
		);

		foreach ( $malicious_inputs as $input => $expected ) {
			$sanitized = ABFEM_CSV_Exporter::sanitize_cell( $input );
			$this->assert( $sanitized === $expected, sprintf( 'Cell sanitized: "%s" => "%s"', addcslashes( $input, "\t\r\n" ), addcslashes( $sanitized, "\t\r\n" ) ) );
		}
	}

	private function test_csv_export_columns_and_formatting() {
		echo "\nRunning CSV Export Columns & Formatting Tests...\n";

		$mock_entries = array(
			array(
				'id'                 => 10,
				'date'               => '2026-10-29 10:00:00',
				'source_plugin'      => 'wpforms',
				'source_plugin_name' => 'WPForms',
				'form_name'          => 'Contact Form',
				'form_id'            => '1',
				'submitter_name'     => 'John Doe',
				'submitter_email'    => 'john@example.com',
				'is_read'            => 0,
				'source_url'         => 'https://example.com/contact',
				'source_entry_id'    => '999',
				'submitter_ip'       => '192.168.1.1',
				'fields'             => array(
					array( 'name' => 'phone', 'label' => 'Phone', 'value' => '1234567890' ),
					array( 'name' => 'comments', 'label' => 'Comments', 'value' => 'Hello there' ),
				),
			),
			array(
				'id'                 => 11,
				'date'               => '2026-10-29 10:05:00',
				'source_plugin'      => 'cf7',
				'source_plugin_name' => 'Contact Form 7',
				'form_name'          => 'Feedback',
				'form_id'            => '2',
				'submitter_name'     => 'Jane Doe',
				'submitter_email'    => 'jane@example.com',
				'is_read'            => 1,
				'source_url'         => 'https://example.com/feedback',
				'source_entry_id'    => '1000',
				'submitter_ip'       => '192.168.1.2',
				'fields'             => array(
					'rating'   => '5 Stars',
					'feedback' => 'Great work!',
				),
			),
		);

		$csv_rows = ABFEM_CSV_Exporter::generate_csv_rows( $mock_entries );
		$headers  = $csv_rows[0];

		// 1. Assert S.N. is the first header and Submission Date is second
		$this->assert( 'S.N.' === $headers[0], 'First CSV column is S.N. (Serial Number)' );
		$this->assert( 'Submission Date' === $headers[1], 'Second CSV column is Submission Date' );

		// 2. Assert removed and unwanted columns are absent
		$this->assert( ! in_array( 'IP Address', $headers, true ), 'IP Address is NOT in CSV headers' );
		$this->assert( ! in_array( 'Original Entry ID', $headers, true ), 'Original Entry ID is NOT in CSV headers' );
		$this->assert( ! in_array( 'Form ID', $headers, true ), 'Form ID is NOT in CSV headers' );
		$this->assert( ! in_array( 'Submission ID', $headers, true ), 'Submission ID is NOT in CSV headers' );
		$this->assert( ! in_array( 'Submitter Name', $headers, true ), 'Submitter Name is NOT in CSV headers' );
		$this->assert( ! in_array( 'Submitter Email', $headers, true ), 'Submitter Email is NOT in CSV headers' );

		// 3. Assert all dynamic fields are present in headers
		$this->assert( in_array( 'Phone', $headers, true ), 'Form field "Phone" included in CSV' );
		$this->assert( in_array( 'Comments', $headers, true ), 'Form field "Comments" included in CSV' );
		$this->assert( in_array( 'Rating', $headers, true ), 'Key-value field "Rating" included in CSV' );
		$this->assert( in_array( 'Feedback', $headers, true ), 'Key-value field "Feedback" included in CSV' );

		// 4. Assert suffix columns order: Form Name, Form Builder, Status, Source Page URL
		$num_cols = count( $headers );
		$this->assert( 'Form Name' === $headers[ $num_cols - 4 ], 'Form Name appears after user detail fields' );
		$this->assert( 'Form Builder' === $headers[ $num_cols - 3 ], 'Form Builder appears after user detail fields' );
		$this->assert( 'Status' === $headers[ $num_cols - 2 ], 'Status is the second-to-last CSV column' );
		$this->assert( 'Source Page URL' === $headers[ $num_cols - 1 ], 'Source Page URL is the last CSV column' );

		// 5. Assert row 1 and row 2 serial numbers
		$this->assert( '1' === (string) $csv_rows[1][0], 'Row 1 has sequential S.N. equal to 1' );
		$this->assert( '2' === (string) $csv_rows[2][0], 'Row 2 has sequential S.N. equal to 2' );

		// 6. Assert data values for dynamic fields
		$phone_idx = array_search( 'Phone', $headers, true );
		$this->assert( false !== $phone_idx && '1234567890' === $csv_rows[1][ $phone_idx ], 'Row 1 has correct Phone value' );

		$rating_idx = array_search( 'Rating', $headers, true );
		$this->assert( false !== $rating_idx && '5 Stars' === $csv_rows[2][ $rating_idx ], 'Row 2 has correct Rating value' );

		// 7. Assert data values for suffix columns on row 1
		$this->assert( 'Contact Form' === $csv_rows[1][ $num_cols - 4 ], 'Row 1 has correct Form Name value' );
		$this->assert( 'WPForms' === $csv_rows[1][ $num_cols - 3 ], 'Row 1 has correct Form Builder value' );
		$this->assert( 'Unread' === $csv_rows[1][ $num_cols - 2 ], 'Row 1 has correct Status value' );
		$this->assert( 'https://example.com/contact' === $csv_rows[1][ $num_cols - 1 ], 'Row 1 has correct Source Page URL value' );
	}

	private function test_privacy_gdpr_exporter_and_eraser() {
		echo "\nRunning Privacy GDPR Exporter & Eraser Tests...\n";

		$test_email = 'privacy.user@example.com';

		$payload = array(
			'source_plugin'    => 'cf7',
			'source_form_id'   => '301',
			'source_form_name' => 'Privacy Consent Form',
			'submitter_name'   => 'Privacy User',
			'submitter_email'  => $test_email,
			'fields'           => array(
				array( 'name' => 'interest', 'label' => 'Interest', 'type' => 'text', 'value' => 'Data Rights' ),
			),
		);

		$entry_id = ABFEM_Submission_Service::process_submission( $payload );
		$this->assert( $entry_id > 0, 'Created privacy test entry' );

		// Test export.
		$export_result = ABFEM_Privacy::export_personal_data( $test_email );
		$this->assert( ! empty( $export_result['data'] ), 'Export returned user personal data' );
		$this->assert( 'abfem_entry_' . $entry_id === $export_result['data'][0]['item_id'], 'Export matched submission ID' );

		// Test erase.
		$erase_result = ABFEM_Privacy::erase_personal_data( $test_email );
		$this->assert( 1 === $erase_result['items_removed'], 'Eraser removed 1 personal record' );
		$this->assert( null === ABFEM_Storage::get_entry( $entry_id ), 'Submission deleted after erasure request' );
	}

	private function test_integrations_adapters() {
		echo "\nRunning Integrations Adapters Tests...\n";

		$manager = new ABFEM_Integrations_Manager();
		$integrations = $manager->get_integrations();

		$expected_ids = array( 'cf7', 'elementor', 'gravityforms', 'ninjaforms', 'wpforms', 'jetpack', 'formidable', 'forminator' );

		foreach ( $expected_ids as $id ) {
			$this->assert( isset( $integrations[ $id ] ), sprintf( 'Integration adapter "%s" registered', $id ) );

			$adapter = $integrations[ $id ];
			$this->assert( $adapter instanceof ABFEM_Integration_Interface, sprintf( 'Adapter "%s" implements ABFEM_Integration_Interface', $id ) );
			$this->assert( ! empty( $adapter->get_name() ), sprintf( 'Adapter "%s" has display name: "%s"', $id, $adapter->get_name() ) );
			$this->assert( ! empty( $adapter->get_description() ), sprintf( 'Adapter "%s" has description', $id ) );
			$this->assert( ! empty( $adapter->get_notes() ), sprintf( 'Adapter "%s" has implementation notes', $id ) );

			// Ensure calling get_forms() without active plugins returns a safe array without fatal errors.
			$forms = $adapter->get_forms();
			$this->assert( is_array( $forms ), sprintf( 'Adapter "%s" get_forms() returns safe array', $id ) );
		}
	}

	private function test_admin_hooks_and_action_links() {
		echo "\nRunning Admin Hooks & Action Links Tests...\n";

		$admin = abfem()->admin;
		$this->assert( method_exists( $admin, 'add_plugin_action_links' ), 'Admin has add_plugin_action_links method' );
		$this->assert( method_exists( $admin, 'add_plugin_row_meta' ), 'Admin has add_plugin_row_meta method' );

		$links = $admin->add_plugin_action_links( array( 'deactivate' => '<a href="#">Deactivate</a>' ) );
		$this->assert( isset( $links['settings'] ), 'Action links contain Settings link' );
		$this->assert( isset( $links['submissions'] ), 'Action links contain Submissions link' );
		$this->assert( isset( $links['guide'] ), 'Action links contain Instructions link' );

		$meta = $admin->add_plugin_row_meta( array(), ABFEM_PLUGIN_BASENAME );
		$this->assert( isset( $meta['guide'] ), 'Row meta contains Instructions & Guide link' );
		$this->assert( isset( $meta['export'] ), 'Row meta contains CSV Export link' );

		$defaults = ABFEM_Settings::get_defaults();
		$this->assert( isset( $defaults['menu_unread_badge'] ), 'Settings defaults include menu_unread_badge' );
		$this->assert( isset( $defaults['admin_bar_menu'] ), 'Settings defaults include admin_bar_menu' );
		$this->assert( isset( $defaults['email_notifications'] ), 'Settings defaults include email_notifications' );
	}

	private function test_admin_menu_highlight_filters() {
		echo "\nRunning Admin Menu Highlighting Tests...\n";

		$admin = abfem()->admin;

		// Simulate global plugin page set to abfem-settings
		$GLOBALS['plugin_page'] = 'abfem-settings';

		$parent = $admin->fix_parent_menu_highlight( 'options-general.php' );
		$this->assert( 'abfem-submissions' === $parent, 'Parent file filtered to abfem-submissions when on abfem-settings' );

		$submenu = $admin->fix_submenu_highlight( 'options-general.php', $parent );
		$this->assert( 'abfem-settings' === $submenu, 'Submenu file filtered to abfem-settings' );

		// Simulate global plugin page set to abfem-export
		$GLOBALS['plugin_page'] = 'abfem-export';
		$parent_exp = $admin->fix_parent_menu_highlight( 'other.php' );
		$this->assert( 'abfem-submissions' === $parent_exp, 'Parent file filtered to abfem-submissions when on abfem-export' );
		$submenu_exp = $admin->fix_submenu_highlight( 'other.php', $parent_exp );
		$this->assert( 'abfem-export' === $submenu_exp, 'Submenu file filtered to abfem-export' );

		// Unset
		unset( $GLOBALS['plugin_page'] );
	}

	private function test_dashboard_metabox_and_notifications() {
		echo "\nRunning Admin Dashboard Metabox & Notification Tests...\n";

		$admin = abfem()->admin;

		// 1. Check Admin class methods.
		$this->assert( method_exists( $admin, 'register_dashboard_widget' ), 'Admin has register_dashboard_widget method' );
		$this->assert( method_exists( $admin, 'render_dashboard_widget' ), 'Admin has render_dashboard_widget method' );
		$this->assert( method_exists( $admin, 'display_dashboard_notices' ), 'Admin has display_dashboard_notices method' );
		$this->assert( method_exists( $admin, 'ajax_dismiss_dashboard_notice' ), 'Admin has ajax_dismiss_dashboard_notice method' );

		// 2. Check Storage latest unread method.
		$this->assert( method_exists( 'ABFEM_Storage', 'get_latest_unread_entry_id' ), 'ABFEM_Storage has get_latest_unread_entry_id method' );
		$latest_id = ABFEM_Storage::get_latest_unread_entry_id();
		$this->assert( is_int( $latest_id ), 'get_latest_unread_entry_id returns integer' );

		// 3. Check Settings defaults.
		$defaults = ABFEM_Settings::get_defaults();
		$this->assert( isset( $defaults['dashboard_widget'] ) && 1 === $defaults['dashboard_widget'], 'Settings defaults include dashboard_widget enabled' );
		$this->assert( isset( $defaults['dashboard_notice'] ) && 1 === $defaults['dashboard_notice'], 'Settings defaults include dashboard_notice enabled' );

		// 4. Check View templates.
		$notice_view = dirname( __DIR__ ) . '/admin/views/dashboard-notice.php';
		$this->assert( file_exists( $notice_view ), 'Dashboard notice view template exists' );
		$notice_content = file_get_contents( $notice_view );
		$this->assert( false !== strpos( $notice_content, 'abfem-dashboard-notice' ), 'Dashboard notice template contains .abfem-dashboard-notice' );
		$this->assert( false !== strpos( $notice_content, 'is-dismissible' ), 'Dashboard notice template is dismissible' );
		$this->assert( false !== strpos( $notice_content, 'data-latest-id' ), 'Dashboard notice template includes data-latest-id for dismissal tracking' );
		$this->assert( false !== strpos( $notice_content, 'View Unread Submissions' ), 'Dashboard notice template has View Unread Submissions link' );

		$widget_view = dirname( __DIR__ ) . '/admin/views/dashboard-widget.php';
		$this->assert( file_exists( $widget_view ), 'Dashboard widget view template exists' );
		$widget_content = file_get_contents( $widget_view );
		$this->assert( false !== strpos( $widget_content, 'abfem-dashboard-widget' ), 'Dashboard widget template contains .abfem-dashboard-widget' );
		$this->assert( false !== strpos( $widget_content, 'abfem-dw-stats-grid' ), 'Dashboard widget template has stats grid' );
		$this->assert( false !== strpos( $widget_content, 'abfem-dw-recent-list' ), 'Dashboard widget template has recent submissions list' );
		$this->assert( false !== strpos( $widget_content, 'Total Entries' ), 'Dashboard widget template displays Total Entries metric' );
		$this->assert( false !== strpos( $widget_content, 'Active Builders' ), 'Dashboard widget template displays Active Builders metric' );
		$this->assert( false !== strpos( $widget_content, 'abfem-dw-footer' ), 'Dashboard widget template contains .abfem-dw-footer' );
		$this->assert( false !== strpos( $widget_content, 'abfem-dw-footer-links' ), 'Dashboard widget template contains .abfem-dw-footer-links' );
		$this->assert( false !== strpos( $widget_content, 'abfem-dw-btn-primary' ), 'Dashboard widget template contains .abfem-dw-btn-primary' );
		$this->assert( false !== strpos( $widget_content, 'abfem-dw-btn-secondary' ), 'Dashboard widget template contains .abfem-dw-btn-secondary' );

		// 5. Check CSS rules.
		$css_content = file_get_contents( dirname( __DIR__ ) . '/admin/css/admin.css' );
		$this->assert( false !== strpos( $css_content, '.abfem-dashboard-notice' ), 'Admin CSS includes .abfem-dashboard-notice' );
		$this->assert( false !== strpos( $css_content, '#abfem_dashboard_widget' ), 'Admin CSS includes #abfem_dashboard_widget' );
		$this->assert( false !== strpos( $css_content, '.abfem-dw-stats-grid' ), 'Admin CSS includes .abfem-dw-stats-grid' );
		$this->assert( false !== strpos( $css_content, '.abfem-dw-footer' ), 'Admin CSS includes .abfem-dw-footer' );
		$this->assert( false !== strpos( $css_content, '.abfem-dw-footer-links' ), 'Admin CSS includes .abfem-dw-footer-links' );
		$this->assert( false !== strpos( $css_content, '.abfem-dw-btn-primary' ), 'Admin CSS includes .abfem-dw-btn-primary' );

		// 6. Check JS dismissal handler.
		$js_content = file_get_contents( dirname( __DIR__ ) . '/admin/js/admin.js' );
		$this->assert( false !== strpos( $js_content, 'abfem_dismiss_dashboard_notice' ), 'Admin JS handles notice dismissal via AJAX' );
	}

	private function test_ui_standards_and_regressions() {
		echo "\nRunning UI Standards, WPCS & Regression Tests...\n";

		$views = array(
			'admin/views/dashboard.php',
			'admin/views/export.php',
			'admin/views/forms.php',
			'admin/views/guide.php',
			'admin/views/integrations.php',
			'admin/views/settings.php',
			'includes/class-detail-view.php',
		);

		foreach ( $views as $view_rel ) {
			$path = dirname( __DIR__ ) . '/' . $view_rel;
			$content = file_get_contents( $path );

			// Assert no wp-heading-inline which causes notices to be pushed into headers.
			$this->assert(
				false === strpos( $content, 'wp-heading-inline' ),
				sprintf( 'View "%s" does NOT contain wp-heading-inline (prevents header notices)', $view_rel )
			);

			// Assert presence of abfem-header-title.
			$this->assert(
				false !== strpos( $content, 'abfem-header-title' ),
				sprintf( 'View "%s" uses abfem-header-title for modern branding', $view_rel )
			);
		}

		// Check FAQ Accordion in guide.php.
		$guide_content = file_get_contents( dirname( __DIR__ ) . '/admin/views/guide.php' );
		$this->assert( false !== strpos( $guide_content, 'abfem-faq-accordion' ), 'Guide template contains FAQ accordion container' );
		$this->assert( false !== strpos( $guide_content, 'abfem-faq-trigger' ), 'Guide template uses accessible accordion triggers' );
		$this->assert( false !== strpos( $guide_content, 'abfem-faq-content' ), 'Guide template has accordion content panels' );

		// Check Export Screen.
		$export_content = file_get_contents( dirname( __DIR__ ) . '/admin/views/export.php' );
		$this->assert( false !== strpos( $export_content, 'abfem-export-container' ), 'Export view has abfem-export-container' );
		$this->assert( false !== strpos( $export_content, 'abfem-export-card' ), 'Export view has abfem-export-card' );
		$this->assert( false !== strpos( $export_content, 'abfem-card-body' ), 'Export view wraps form in abfem-card-body' );
		$this->assert( false !== strpos( $export_content, 'abfem-date-inputs' ), 'Export view has date range styling classes' );

		// Check Center-aligned Empty State.
		$list_table_content = file_get_contents( dirname( __DIR__ ) . '/includes/class-admin-list-table.php' );
		$this->assert( false !== strpos( $list_table_content, 'abfem-empty-desc' ), 'List table empty state uses abfem-empty-desc for center alignment' );

		// Check Forminator is included in list table filter builders dropdown.
		$this->assert( false !== strpos( $list_table_content, "'forminator'" ), 'Forminator Forms option is included in submissions filter bar' );

		// Check Discovered Forms column order: Form ID before Form Name.
		$forms_content = file_get_contents( dirname( __DIR__ ) . '/admin/views/forms.php' );
		$id_pos   = strpos( $forms_content, 'manage-column column-id' );
		$name_pos = strpos( $forms_content, 'manage-column column-name' );
		$this->assert( false !== $id_pos && false !== $name_pos && $id_pos < $name_pos, 'Discovered Forms table displays Form ID before Form Name in header' );

		// Check CSS rules for forms table and detail actions.
		$css_content = file_get_contents( dirname( __DIR__ ) . '/admin/css/admin.css' );
		$this->assert( false !== strpos( $css_content, '.abfem-forms-table' ), 'Admin CSS includes styling for abfem-forms-table' );
		$this->assert( false !== strpos( $css_content, '.abfem-detail-actions' ), 'Admin CSS includes styling for abfem-detail-actions' );
		$this->assert( false !== strpos( $css_content, '.abfem-prev-next-nav' ), 'Admin CSS includes styling for abfem-prev-next-nav' );

		// Check Admin redirection method.
		$admin = abfem()->admin;
		$this->assert( method_exists( $admin, 'maybe_redirect_legacy_pages' ), 'Admin has maybe_redirect_legacy_pages method for integrations redirect' );
	}
}

$suite = new ABFEM_Test_Suite();
$suite->run();
