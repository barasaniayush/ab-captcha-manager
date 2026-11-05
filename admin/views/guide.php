<?php
/**
 * In-plugin User Instructions & Setup Guide view template.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$is_embedded = ! empty( $is_embedded_guide );

$active_builder_count = 0;
$integrations = abfem()->integrations_manager->get_integrations();
foreach ( $integrations as $int_id => $int ) {
	if ( $int->is_active() && ABFEM_Settings::is_integration_enabled( $int_id ) ) {
		$active_builder_count++;
	}
}
?>
<?php if ( ! $is_embedded ) : ?>
<div class="wrap abfem-wrap abfem-guide-wrap">

	<!-- Header with Branding -->
	<div class="abfem-modern-header">
		<div class="abfem-header-left">
			<div class="abfem-logo-icon">
				<span class="dashicons dashicons-book-alt"></span>
			</div>
			<div class="abfem-header-title-box">
				<div class="abfem-title-row">
					<h1 class="abfem-header-title"><?php esc_html_e( 'Instructions &amp; Setup Guide', 'ab-form-entries-manager' ); ?></h1>
					<span class="abfem-version-pill"><?php echo esc_html( 'v' . ABFEM_VERSION ); ?></span>
				</div>
				<p class="abfem-subtitle"><?php esc_html_e( 'Complete documentation, builder setup instructions, and workflows for AB Form Entries Manager.', 'ab-form-entries-manager' ); ?></p>
			</div>
		</div>

		<div class="abfem-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-feedback"></span> <?php esc_html_e( 'View Submissions', 'ab-form-entries-manager' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=abfem-settings' ) ); ?>" class="button button-primary abfem-btn-modern">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Plugin Settings', 'ab-form-entries-manager' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">
<?php endif; ?>

	<!-- Quick Start 3-Step Banner -->
	<div class="abfem-guide-banner">
		<div class="abfem-banner-badge">
			<span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Quick Start Overview', 'ab-form-entries-manager' ); ?>
		</div>
		<h2><?php esc_html_e( 'How AB Form Entries Manager Works', 'ab-form-entries-manager' ); ?></h2>
		<p><?php esc_html_e( 'AB Form Entries Manager is a zero-configuration, universal submission storage engine. You do not need to configure webhooks, database credentials, or manual endpoints. It automatically detects and hooks into all your active form plugins behind the scenes.', 'ab-form-entries-manager' ); ?></p>

		<div class="abfem-steps-grid">
			<div class="abfem-step-card">
				<div class="abfem-step-number">1</div>
				<div class="abfem-step-body">
					<h3><?php esc_html_e( 'Create or Use Any Form', 'ab-form-entries-manager' ); ?></h3>
					<p><?php esc_html_e( 'Build your forms normally in WPForms, Contact Form 7, Forminator, Elementor Pro, Gravity Forms, Ninja Forms, Jetpack, or Formidable Forms.', 'ab-form-entries-manager' ); ?></p>
				</div>
			</div>

			<div class="abfem-step-card">
				<div class="abfem-step-number">2</div>
				<div class="abfem-step-body">
					<h3><?php esc_html_e( 'Automatic Capture', 'ab-form-entries-manager' ); ?></h3>
					<p><?php esc_html_e( 'When a visitor submits a form on your site, our lightweight listener catches the data, filters sensitive fields, and securely stores the entry.', 'ab-form-entries-manager' ); ?></p>
				</div>
			</div>

			<div class="abfem-step-card">
				<div class="abfem-step-number">3</div>
				<div class="abfem-step-body">
					<h3><?php esc_html_e( 'Manage &amp; Export', 'ab-form-entries-manager' ); ?></h3>
					<p><?php esc_html_e( 'Search, filter, view complete submission details, mark read/unread, and export your data to formatted CSV files with formula injection protection.', 'ab-form-entries-manager' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- Supported Form Builders Section -->
	<div class="abfem-card abfem-guide-card">
		<div class="abfem-card-header">
			<h2><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Supported Form Builders &amp; Setup Instructions', 'ab-form-entries-manager' ); ?></h2>
			<span class="abfem-status-badge <?php echo esc_attr( $active_builder_count > 0 ? 'abfem-badge-success' : 'abfem-badge-warning' ); ?>">
				<span class="abfem-dot"></span>
				<?php
				/* translators: %d: count of active builders */
				echo esc_html( sprintf( _n( '%d Form Builder Active', '%d Form Builders Active', (int) $active_builder_count, 'ab-form-entries-manager' ), (int) $active_builder_count ) );
				?>
			</span>
		</div>
		<div class="abfem-card-body">
			<p class="abfem-section-intro">
				<?php esc_html_e( 'Click on any form builder below to view detailed setup steps and integration specifics:', 'ab-form-entries-manager' ); ?>
			</p>

			<div class="abfem-guide-accordion">

				<!-- WPForms -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-wpforms">WP</span>
							<strong>WPForms (Lite &amp; Pro)</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( ( class_exists( 'WPForms' ) || function_exists( 'wpforms' ) ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo ( class_exists( 'WPForms' ) || function_exists( 'wpforms' ) ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<div class="abfem-guide-callout abfem-callout-tip">
							<span class="dashicons dashicons-star-filled"></span>
							<div>
								<strong><?php esc_html_e( 'WPForms Lite Entry Storage Bonus:', 'ab-form-entries-manager' ); ?></strong>
								<?php esc_html_e( 'WPForms Lite does not provide entry storage in the free version. AB Form Entries Manager captures 100% of WPForms Lite submissions automatically without requiring a paid subscription!', 'ab-form-entries-manager' ); ?>
							</div>
						</div>
						<h4><?php esc_html_e( 'How to use:', 'ab-form-entries-manager' ); ?></h4>
						<ol class="abfem-guide-steps">
							<li><?php esc_html_e( 'Create your form in WPForms under WP Admin &rarr; WPForms &rarr; Add New.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Embed the form onto any page or post using the WPForms block or shortcode.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'When submitted, AB Form Entries Manager catches the "wpforms_process_complete" event and records all fields, submitter name, email, and metadata.', 'ab-form-entries-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- Contact Form 7 -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-cf7">CF</span>
							<strong>Contact Form 7</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( ( defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' ) ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo ( defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' ) ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Contact Form 7 normally only emails submissions. With AB Form Entries Manager, all submissions are permanently backed up to your database.', 'ab-form-entries-manager' ); ?></p>
						<h4><?php esc_html_e( 'How to use:', 'ab-form-entries-manager' ); ?></h4>
						<ol class="abfem-guide-steps">
							<li><?php esc_html_e( 'Create your form in Contact &rarr; Contact Forms.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Use standard tags like [text* your-name], [email* your-email], [textarea your-message].', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Submissions are captured via the "wpcf7_mail_sent" hook after CF7 finishes mail processing.', 'ab-form-entries-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- Forminator -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-forminator">FM</span>
							<strong>Forminator Forms</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( class_exists( 'Forminator' ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo class_exists( 'Forminator' ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Full compatibility with Forminator Custom Forms, capturing inputs, selects, textareas, and email addresses seamlessly.', 'ab-form-entries-manager' ); ?></p>
						<ol class="abfem-guide-steps">
							<li><?php esc_html_e( 'Create your form in Forminator &rarr; Forms.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Publish and embed the Forminator shortcode on your website.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Entries are instantly captured and synchronized into your AB Form Entries dashboard.', 'ab-form-entries-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- Elementor Pro -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-elementor">EL</span>
							<strong>Elementor Pro Forms</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( ( defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( '\ElementorPro\Plugin' ) ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo ( defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( '\ElementorPro\Plugin' ) ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Direct integration with Elementor Pro\'s native Form widget.', 'ab-form-entries-manager' ); ?></p>
						<ol class="abfem-guide-steps">
							<li><?php esc_html_e( 'Drag the "Form" widget onto any page in Elementor.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Set field IDs and labels in the Form settings panel.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Submissions are captured via "elementor_pro/forms/new_record" automatically.', 'ab-form-entries-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- Gravity Forms -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-gravityforms">GF</span>
							<strong>Gravity Forms</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( class_exists( 'GFForms' ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo class_exists( 'GFForms' ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Hooks into Gravity Forms via "gform_after_submission", preserving multi-page entries, complex fields, and metadata.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<!-- Ninja Forms -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-ninjaforms">NF</span>
							<strong>Ninja Forms</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( ( class_exists( 'Ninja_Forms' ) || function_exists( 'Ninja_Forms' ) ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo ( class_exists( 'Ninja_Forms' ) || function_exists( 'Ninja_Forms' ) ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Captures submissions via "ninja_forms_after_submission" across all Ninja Forms modules.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<!-- Formidable Forms -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-formidable">FF</span>
							<strong>Formidable Forms</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( class_exists( 'FrmAppHelper' ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo class_exists( 'FrmAppHelper' ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Captures submissions via "frm_after_create_entry" for standard and custom Formidable forms.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<!-- Jetpack Forms -->
				<div class="abfem-accordion-item">
					<div class="abfem-accordion-header">
						<div class="abfem-accordion-title">
							<span class="abfem-integration-badge abfem-badge-jetpack">JP</span>
							<strong>Jetpack Forms</strong>
						</div>
						<span class="abfem-accordion-status <?php echo esc_attr( class_exists( 'Grunion_Contact_Form_Plugin' ) ? 'abfem-status-active' : 'abfem-status-inactive' ); ?>">
							<?php echo class_exists( 'Grunion_Contact_Form_Plugin' ) ? esc_html__( 'Active on this site', 'ab-form-entries-manager' ) : esc_html__( 'Not Active', 'ab-form-entries-manager' ); ?>
						</span>
					</div>
					<div class="abfem-accordion-content">
						<p><?php esc_html_e( 'Captures Jetpack block and shortcode form submissions via "grunion_pre_message_sent".', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

			</div>
		</div>
	</div>

	<!-- Dashboard Features & How-To Guide -->
	<div class="abfem-grid-two-columns">

		<!-- Left: Features -->
		<div class="abfem-card">
			<div class="abfem-card-header">
				<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Managing Submissions', 'ab-form-entries-manager' ); ?></h2>
			</div>
			<div class="abfem-card-body">
				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-search"></span></div>
					<div>
						<h4><?php esc_html_e( 'Real-Time Search &amp; Multi-Filtering', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Instantly search submissions by submitter name, email, form title, or submitted content. Filter by builder plugin, specific form ID, read/unread status, and date range presets (Today, Yesterday, Last 7 Days, Last 30 Days, This Month).', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-visibility"></span></div>
					<div>
						<h4><?php esc_html_e( 'Comprehensive Detail Screen', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Click on any entry title or "View" to open the single submission inspector. View all field values, submission metadata (date, IP address, user agent, referer URL), and navigate quickly using the Next &amp; Previous buttons.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-yes-alt"></span></div>
					<div>
						<h4><?php esc_html_e( 'Read / Unread Tracking &amp; Bulk Actions', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Keep track of which entries have been reviewed. Mark entries as read or unread individually or in bulk. Red badge counts in the admin menu alert you to unread submissions.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-download"></span></div>
					<div>
						<h4><?php esc_html_e( 'CSV Export with Formula Injection Defense', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Download filtered or complete submission datasets as clean CSV files. All cell contents starting with formula characters (=, +, -, @) are sanitized with apostrophe prefixing to protect against spreadsheet injection vulnerabilities.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Right: Privacy & Security -->
		<div class="abfem-card">
			<div class="abfem-card-header">
				<h2><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Privacy, GDPR &amp; Security', 'ab-form-entries-manager' ); ?></h2>
			</div>
			<div class="abfem-card-body">
				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-admin-site"></span></div>
					<div>
						<h4><?php esc_html_e( 'IP Address Anonymization', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Enable in Settings &rarr; Privacy &amp; GDPR. Zeros out the last octet for IPv4 (e.g., 192.168.1.0) and masks IPv6 addresses prior to database insertion.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-clock"></span></div>
					<div>
						<h4><?php esc_html_e( 'Automatic Retention Cleanup', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Configure automatic purging (30, 60, 90, 180, or 365 days). A daily scheduled background cron automatically deletes entries older than the configured threshold.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-lock"></span></div>
					<div>
						<h4><?php esc_html_e( 'Sensitive Field Blacklist', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Sensitive fields matching patterns such as passwords, cvc, cvv, credit_card, token, and secret are permanently ignored and never stored in the database.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<div class="abfem-feature-item">
					<div class="abfem-feature-icon"><span class="dashicons dashicons-id"></span></div>
					<div>
						<h4><?php esc_html_e( 'WordPress Core GDPR Exporter &amp; Eraser', 'ab-form-entries-manager' ); ?></h4>
						<p><?php esc_html_e( 'Fully integrated with Tools &rarr; Export Personal Data and Tools &rarr; Erase Personal Data. When a user requests their personal data or erasure under GDPR, AB Form Entries Manager exports or erases their submissions automatically.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>
			</div>
		</div>

	</div>

	<!-- Troubleshooting & FAQ Section (Interactive Accordion List) -->
	<div class="abfem-card abfem-faq-card">
		<div class="abfem-card-header">
			<h2>
				<span class="dashicons dashicons-editor-help"></span>
				<?php esc_html_e( 'Frequently Asked Questions &amp; Troubleshooting', 'ab-form-entries-manager' ); ?>
			</h2>
			<span class="abfem-card-hint"><?php esc_html_e( 'Click on any question to view answers and solutions', 'ab-form-entries-manager' ); ?></span>
		</div>
		<div class="abfem-card-body">
			<div class="abfem-faq-accordion" id="abfem-faq-accordion">

				<!-- Question 1 -->
				<div class="abfem-faq-item abfem-faq-open">
					<button type="button" class="abfem-faq-trigger" aria-expanded="true">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-lightbulb"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'Why are submissions from my form not appearing in the dashboard?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content" style="display: block;">
						<p><?php esc_html_e( 'If entries are not appearing, please check the following common causes:', 'ab-form-entries-manager' ); ?></p>
						<ul class="abfem-bullet-list">
							<li><strong><?php esc_html_e( 'Form Builder Integration Status:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'Ensure your form plugin is active on the site and enabled under Settings &rarr; Form Integrations.', 'ab-form-entries-manager' ); ?></li>
							<li><strong><?php esc_html_e( '15-Second Duplicate Protection:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'If you submit the exact same data within 15 seconds during testing, our duplicate protection filter ignores repeated submissions.', 'ab-form-entries-manager' ); ?></li>
							<li><strong><?php esc_html_e( 'Validation Errors:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'Only successfully validated and submitted forms trigger capture. If a visitor encounters an error or captcha failure, no submission is recorded.', 'ab-form-entries-manager' ); ?></li>
							<li><strong><?php esc_html_e( 'Test with Diagnostic Tool:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'Go to Settings &rarr; System Status &amp; Tools and click "Generate Sample Submission" to verify storage is working.', 'ab-form-entries-manager' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Question 2 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-email"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'Does this plugin replace email notifications sent by my form builder?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'No. All existing notification emails, customer auto-responders, and third-party CRM webhooks configured in your form builder continue to function exactly as normal.', 'ab-form-entries-manager' ); ?></p>
						<p><?php esc_html_e( 'AB Form Entries Manager runs alongside your existing forms as a dependable, central database safety net. In addition, you can optionally configure instant email notification alerts inside Settings &rarr; Notifications whenever a new submission is captured.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<!-- Question 3 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-admin-users"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'Who has permission to view and export form submissions?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'By default, only users with the "manage_options" capability (Administrators) can view, search, delete, or export entries.', 'ab-form-entries-manager' ); ?></p>
						<p><?php esc_html_e( 'Developers can customize this permission for custom roles (such as Editors or Shop Managers) using the WordPress capability filter:', 'ab-form-entries-manager' ); ?></p>
						<code>add_filter( 'abfem_manage_capability', function() { return 'edit_pages'; } );</code>
					</div>
				</div>

				<!-- Question 4 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-plus-alt"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'How do I test that submission capture is working correctly?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'You can quickly generate a simulated entry to verify everything is operational:', 'ab-form-entries-manager' ); ?></p>
						<ol class="abfem-guide-steps">
							<li><?php esc_html_e( 'Go to AB Form Entries &rarr; Settings and click on the "System Status &amp; Tools" tab.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Locate the "Diagnostic &amp; Developer Tools" card.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'Click the "Generate Sample Submission" button.', 'ab-form-entries-manager' ); ?></li>
							<li><?php esc_html_e( 'A complete test entry will be created immediately with a direct link to view it in the submissions inbox.', 'ab-form-entries-manager' ); ?></li>
						</ol>
					</div>
				</div>

				<!-- Question 5 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-clock"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'How does automatic data retention cleanup work?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'Under Settings &rarr; Privacy &amp; GDPR, you can set the Data Retention Policy to automatically delete submissions after 30, 60, 90, 180, or 365 days (or keep indefinitely).', 'ab-form-entries-manager' ); ?></p>
						<p><?php esc_html_e( 'A daily background WordPress cron event ("abfem_daily_cleanup") checks for entries past the threshold and permanently deletes both the post and associated meta records without slowing down visitor requests.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<!-- Question 6 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-shield"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'Is AB Form Entries Manager GDPR and CCPA compliant?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'Yes. AB Form Entries Manager includes first-class privacy safeguards:', 'ab-form-entries-manager' ); ?></p>
						<ul class="abfem-bullet-list">
							<li><strong><?php esc_html_e( 'IP Anonymization:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'Masks visitor IPv4 and IPv6 addresses prior to database storage.', 'ab-form-entries-manager' ); ?></li>
							<li><strong><?php esc_html_e( 'Core Privacy Tools:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'Hooks into WordPress Tools &rarr; Export Personal Data and Tools &rarr; Erase Personal Data.', 'ab-form-entries-manager' ); ?></li>
							<li><strong><?php esc_html_e( 'Sensitive Field Blacklist:', 'ab-form-entries-manager' ); ?></strong> <?php esc_html_e( 'Permanently drops credit card, CVV, password, and token fields so they never touch your database.', 'ab-form-entries-manager' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Question 7 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-download"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'How does CSV formula injection defense protect my spreadsheet?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'Formula injection (also known as CSV Injection) occurs when malicious users submit form inputs starting with =, +, -, @, or tab characters. When opened in Microsoft Excel or LibreOffice, the spreadsheet might execute those formulas or initiate remote data requests.', 'ab-form-entries-manager' ); ?></p>
						<p><?php esc_html_e( 'AB Form Entries Manager automatically prepends a single quotation mark to any vulnerable cell, neutralizing the formula while displaying the text accurately.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

				<!-- Question 8 -->
				<div class="abfem-faq-item">
					<button type="button" class="abfem-faq-trigger" aria-expanded="false">
						<span class="abfem-faq-icon"><span class="dashicons dashicons-trash"></span></span>
						<span class="abfem-faq-question"><?php esc_html_e( 'What happens to my submissions if I deactivate the plugin?', 'ab-form-entries-manager' ); ?></span>
						<span class="dashicons dashicons-arrow-down-alt2 abfem-faq-chevron"></span>
					</button>
					<div class="abfem-faq-content">
						<p><?php esc_html_e( 'Deactivating the plugin preserves all your submission data safely in the database. When you reactivate, everything is instantly available without loss.', 'ab-form-entries-manager' ); ?></p>
						<p><?php esc_html_e( 'If you choose to permanently delete (uninstall) the plugin, the "Delete data upon uninstall" option in Settings &rarr; Privacy controls whether stored entries and preferences are purged.', 'ab-form-entries-manager' ); ?></p>
					</div>
				</div>

			</div>
		</div>
	</div>

<?php if ( ! $is_embedded ) : ?>
</div>
<?php endif; ?>
