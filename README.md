# AB Form Entries Manager

**AB Form Entries Manager** is a free, modern, zero-configuration WordPress plugin that aggregates form submissions from all major form builders and stores them centrally in a beautiful, responsive WordPress admin dashboard.

---

## Highlights

- **Universal Central Inbox**: View, search, filter, and manage submissions across all supported form builders.
- **Supported Integrations**:
  1. **WPForms (Lite & Pro)** - Stores entries for free without requiring paid WPForms entry storage addons.
  2. **Contact Form 7** - Reliable database backup for CF7 submissions.
  3. **Forminator Forms** - Automatically captures custom form submissions and choices.
  4. **Elementor Pro Forms** - Seamlessly captures submissions from Elementor form widgets.
  5. **Gravity Forms** - Complete support for single and multi-input fields.
  6. **Ninja Forms** - Automatically logs submissions and action execution data.
  7. **Formidable Forms** - Captures entries from both Lite and Pro editions.
  8. **Jetpack Forms** - Captures contact form blocks and feedback messages.
- **Modern Design & Layout**: Clean SaaS-grade interface with tabbed settings, iOS-style toggle switches, live previews, and responsive layouts.
- **Email Notifications**: Instant email summaries on new submissions with customizable recipients and subject lines.
- **In-Plugin Documentation**: Complete interactive guide, builder walkthroughs, and troubleshooting checklist right inside the WordPress dashboard.
- **Security & Privacy**:
  - Private CPT (`abfem_entry`) inaccessible to unauthenticated or frontend queries.
  - CSV Formula Injection mitigation (`=`, `+`, `-`, `@`, `\t`, `\r` escaped).
  - Built-in WordPress Core Privacy (GDPR) Personal Data Exporter & Eraser integration.
  - IP Address Anonymization option (IPv4 & IPv6).
  - Automated Daily Data Retention cleanup option.
  - Excludes sensitive fields (passwords, credit cards, CVVs, tokens, secrets).
- **100% Free**: No license restrictions, no upsell prompts, no Pro locks.

---

## Architecture & Code Structure

```
ab-form-entries-manager/
├── ab-form-entries-manager.php       # Bootstrap, constants, activation/deactivation hooks
├── uninstall.php                      # Clean uninstall routines
├── README.md                          # Technical and usage documentation
├── readme.txt                         # WordPress.org standard metadata
├── includes/
│   ├── class-plugin.php               # Central plugin container and lifecycle manager
│   ├── class-activator.php            # Activation tasks (CPT registration, defaults)
│   ├── class-deactivator.php          # Deactivation tasks (cron & cache cleanup)
│   ├── class-capabilities.php         # Capability permissions checker
│   ├── class-settings.php             # Plugin settings management and sanitization
│   ├── class-storage.php              # CPT persistence, indexed queries, bulk ops, stats
│   ├── class-submission-service.php   # Ingest pipeline: normalization, duplicate check, privacy
│   ├── class-admin.php                # Admin menus, asset enqueuing, action dispatchers, AJAX
│   ├── class-admin-list-table.php     # Submissions dashboard list table renderer
│   ├── class-detail-view.php          # Single submission detail view renderer
│   ├── class-csv-exporter.php         # RFC 4180 CSV export with formula injection prevention
│   ├── class-privacy.php              # WP Privacy Personal Data Exporter & Eraser
│   ├── class-integrations-manager.php # Integrations registry and forms discovery
│   └── integrations/
│       ├── interface-integration.php  # Integration adapter interface
│       ├── class-base-integration.php # Base abstract class with common normalization
│       ├── class-contact-form-7.php   # Contact Form 7 adapter
│       ├── class-elementor.php        # Elementor Pro Forms adapter
│       ├── class-forminator.php       # Forminator Forms adapter
│       ├── class-formidable-forms.php # Formidable Forms adapter
│       ├── class-gravity-forms.php    # Gravity Forms adapter
│       ├── class-jetpack-forms.php    # Jetpack Forms adapter
│       ├── class-ninja-forms.php      # Ninja Forms adapter
│       └── class-wpforms.php          # WPForms adapter
├── admin/
│   ├── views/                         # Clean admin HTML view templates
│   │   ├── dashboard.php              # Submissions list and metrics
│   │   ├── forms.php                  # Discovered forms directory
│   │   ├── integrations.php           # Integrations status directory
│   │   ├── export.php                 # CSV export configuration
│   │   ├── settings.php               # Modern tabbed settings panel
│   │   └── guide.php                  # In-plugin instructions & setup guide
│   ├── css/
│   │   └── admin.css                  # Modern, accessible admin stylesheet
│   └── js/
│       └── admin.js                   # Interactivity: tabs, presets, AJAX tools, bulk actions
├── languages/
│   └── ab-form-entries-manager.pot    # Translation template
└── tests/
    ├── bootstrap.php                  # Mock environment bootstrap for standalone testing
    └── test-suite.php                 # Comprehensive unit & functional test suite
```

---

## Supported Form Integrations

| Form Builder | Supported Versions | Integration Hook | Status Detection |
| :--- | :--- | :--- | :--- |
| **WPForms** | Lite & Pro 1.5+ | `wpforms_process_complete` | `wpforms()` helper or `WPForms` class |
| **Contact Form 7** | 5.0+ | `wpcf7_mail_sent` | `WPCF7_ContactForm` class |
| **Forminator** | 1.15+ | `forminator_custom_form_submit_before_set_fields` | `Forminator` class |
| **Elementor Pro** | 3.0+ | `elementor_pro/forms/new_record` | `\ElementorPro\Plugin` / `ELEMENTOR_PRO_VERSION` |
| **Gravity Forms** | 2.4+ | `gform_after_submission` | `GFForms` / `GFAPI` class |
| **Ninja Forms** | 3.0+ | `ninja_forms_after_submission` | `Ninja_Forms` class |
| **Formidable Forms**| Lite & Pro 4.0+ | `frm_after_create_entry` | `FrmForm` / `FrmEntry` class |
| **Jetpack Forms** | 9.0+ | `grunion_pre_message_sent` | `Jetpack_Forms` / `Grunion_Contact_Form` |

---

## Plugin Settings & Navigation

Settings and instructions are discoverable in multiple locations:
1. **Sidebar Menu**: `AB Form Entries > Settings`
2. **Settings Menu**: `Settings > AB Form Entries`
3. **Plugins Screen**: `Settings | Submissions | Instructions` action links directly on the plugin row
4. **Documentation**: `AB Form Entries > Instructions & Guide` or via the "Instructions & Guide" tab in Settings

### Settings Tabs
- **General**: Pagination size, live date preview format, auto-mark read on view, unread badge counter in menu, admin bar shortcut.
- **Form Integrations**: Status cards and individual capture switches for all 8 supported form builders.
- **Privacy & GDPR**: IP address anonymization, automatic data retention purge schedule, WP core Privacy Exporter/Eraser integration.
- **Field Security**: Sensitive field blacklist with quick-add preset buttons (passwords, credit cards, tokens, SSN), uninstallation data purge policy.
- **Notifications**: Instant admin email alerts on submission, custom email subject line, and live test email dispatcher.
- **Instructions & Guide**: Comprehensive step-by-step documentation, architecture diagram, builder walkthroughs, and FAQ.
- **System Status & Diagnostics**: Live environment table, sample submission generator, and cache flush tools.

---

## CSV Export & Security

The CSV export engine implements security controls:
1. **Formula Injection Guard**: Automatically sanitizes cells starting with `=`, `+`, `-`, `@`, `\t`, or `\r` by prepending a single quote `'` (OWASP compliance).
2. **UTF-8 Byte Order Mark (BOM)**: Includes `\xEF\xBB\xBF` so Microsoft Excel and Apple Numbers render non-ASCII characters without encoding issues.
3. **Dynamic Headers**: Merges dynamic form field labels across all selected submissions so columns are cleanly aligned.
