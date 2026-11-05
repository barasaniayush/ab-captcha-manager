=== AB Form Entries Manager ===
Contributors: ayushbarasani
Donate link: https://ayushbarasani.com.np/
Tags: forms, entries, submissions, contact form 7, wpforms
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Centralized form entries manager for Contact Form 7, WPForms, Forminator, Elementor, Gravity Forms, and more in one modern dashboard.

== Description ==

**AB Form Entries Manager** is a free, lightweight, zero-configuration WordPress plugin that automatically captures form submissions from all your active form builders and stores them centrally in a modern, responsive WordPress admin dashboard.

Never miss a lead or customer inquiry again. Even if your form builder doesn't store submissions (like Contact Form 7 or WPForms Lite without paid add-ons) or your mail server fails to deliver notification emails, AB Form Entries Manager guarantees every submission is captured safely, reliably, and privately in your database.

### Supported Form Builders

1. **WPForms (Lite & Pro)** - Captures submissions directly from WPForms Lite without requiring expensive paid entry storage licenses.
2. **Contact Form 7** - Full support for text, email, select, checkboxes, and uploaded file metadata.
3. **Forminator Forms** - Automatically captures custom form submissions, choices, text inputs, and submitter info.
4. **Elementor Pro Forms** - Seamlessly captures submissions from the native Elementor Form widget.
5. **Gravity Forms** - Complete support for single and multi-input fields (Name, Address, etc.).
6. **Ninja Forms** - Automatically logs submissions and action execution data.
7. **Formidable Forms** - Captures entries from both Lite and Pro editions.
8. **Jetpack Forms** - Captures contact form blocks and feedback messages.

### Key Features

* **Zero-Configuration Setup**: Install and activate. The plugin instantly detects active form builders and starts capturing entries automatically.
* **Modern SaaS-Grade Interface**: Fast, sleek, responsive layout with tabbed settings, iOS-style toggle switches, live previews, and dark mode compatible header.
* **Smart Metrics Dashboard**: Real-time summary counts for Total Submissions, Today's Submissions, Unread Submissions, and Active Form Integrations.
* **Real-Time Search & Multi-Filtering**: Search by ID, submitter name, email, form title, or submitted field contents. Filter by form builder, specific form ID, date range, or read/unread status.
* **Comprehensive Detail Screen**: Dedicated view with all submitted fields, labels, field types, source URL, IP address, user agent, and next/previous entry navigation.
* **Robust CSV Export**: Export all entries, selected entries, filtered results, or single submissions. Fully protected against CSV Formula Injection and encoded with UTF-8 BOM for Microsoft Excel, Google Sheets, and Numbers.
* **Email Notification System**: Receive instant email summaries whenever a new submission is captured, with customizable subject lines and recipient lists.
* **Privacy & GDPR Compliant**: Submitter IP address anonymization, configurable automated daily data retention purge (30, 60, 90, 180, 365 days), excluded sensitive fields (passwords, credit cards), and WordPress core Personal Data Exporter & Eraser integration.
* **Built-In Instructions & Documentation**: Complete in-plugin setup guide, builder walkthroughs, and FAQ accessible anytime under **AB Form Entries &rarr; Instructions & Guide**.
* **100% Free**: No license keys, no upsells, no locked features, and no external third-party dependencies.

== Installation ==

1. Upload the `ab-form-entries-manager` folder to your `/wp-content/plugins/` directory, or install the ZIP file via **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Access **AB Form Entries** or **Settings > AB Form Entries** from your WordPress admin sidebar.
4. Any active supported form plugins will automatically begin capturing new submissions immediately without extra configuration.

== Frequently Asked Questions ==

= Does this require paid versions of form builders? =
No. It works seamlessly with free versions like Contact Form 7, WPForms Lite, and Forminator, as well as premium builders like Gravity Forms and Elementor Pro.

= Where are form submissions stored? =
Submissions are stored securely as private records (`abfem_entry` custom post type) in your WordPress database. They are completely inaccessible from the frontend.

= Can visitors view my entries? =
No. Entries are strictly private and queryable only by administrators with `manage_options` capability (customizable via filter).

= Where can I find the plugin settings? =
You can find settings under **AB Form Entries > Settings** in your WordPress admin menu or by clicking the **Settings** action link on the Plugins page.

= Does it prevent duplicate submissions? =
Yes. An intelligent hash check prevents duplicate entries if a user double-clicks the submit button within a 15-second debounce window.

= How does the CSV export protect against spreadsheet formula injection? =
Any cell starting with `=`, `+`, `-`, `@`, `\t`, or `\r` is automatically sanitized by prepending a single quote `'` in accordance with OWASP security guidelines.

== Screenshots ==

1. Submissions Dashboard with metrics, search bar, and filters.
2. Modern Settings panel with tabbed navigation and toggle controls.
3. In-plugin Instructions & Setup Guide with builder-specific instructions.
4. Submission detail view with dynamic field breakdown and metadata.
5. Discovered Forms overview list.
6. Form Builder Integrations status grid.
7. CSV Export configuration screen.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Added support for Contact Form 7, WPForms, Forminator, Elementor Pro Forms, Gravity Forms, Ninja Forms, Formidable Forms, and Jetpack Forms.
* Modernized plugin layout and responsive design with tabbed navigation and toggle switches.
* Added comprehensive in-plugin Instructions & Setup Guide.
* Added plugin action links and native WordPress Settings menu registration.
* Implemented admin email notifications for new submissions.
* Added diagnostic testing tools: sample submission generator and test email dispatcher.
* Server-side filtered list, single entry detail screen, and CSV exporter with formula injection defense.
* Implemented GDPR Personal Data Exporter and Eraser integration.
