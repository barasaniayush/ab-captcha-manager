<?php
/**
 * Admin menus, controllers, and action handlers.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Admin
 */
class ABFEM_Admin {

	/**
	 * Hook suffixes for registered admin pages.
	 *
	 * @var array
	 */
	private $hook_suffixes = array();

	/**
	 * Integrations manager instance.
	 *
	 * @var ABFEM_Integrations_Manager
	 */
	private $integrations_manager;

	/**
	 * Constructor.
	 *
	 * @param ABFEM_Integrations_Manager $integrations_manager Integrations manager.
	 */
	public function __construct( ABFEM_Integrations_Manager $integrations_manager ) {
		$this->integrations_manager = $integrations_manager;
	}

	/**
	 * Initialize admin hooks and action listeners.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_redirect_legacy_pages' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_node' ), 80 );

		// Admin dashboard metabox widget and notifications on /wp-admin/index.php.
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
		add_action( 'admin_notices', array( $this, 'display_dashboard_notices' ) );
		add_action( 'wp_ajax_abfem_dismiss_dashboard_notice', array( $this, 'ajax_dismiss_dashboard_notice' ) );

		// Ensure the AB Form Entries admin menu stays open and properly highlighted.
		add_filter( 'parent_file', array( $this, 'fix_parent_menu_highlight' ) );
		add_filter( 'submenu_file', array( $this, 'fix_submenu_highlight' ), 10, 2 );

		// Plugin list table links.
		add_filter( 'plugin_action_links_' . ABFEM_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );

		// AJAX handlers for settings and diagnostics tools.
		add_action( 'wp_ajax_abfem_generate_sample_entry', array( $this, 'ajax_generate_sample_entry' ) );
		add_action( 'wp_ajax_abfem_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_abfem_clear_cache', array( $this, 'ajax_clear_cache' ) );

		// Action handlers for POST requests.
		add_action( 'admin_post_abfem_bulk_action', array( $this, 'handle_bulk_action' ) );
		add_action( 'admin_post_abfem_toggle_read', array( $this, 'handle_toggle_read' ) );
		add_action( 'admin_post_abfem_delete_entry', array( $this, 'handle_delete_entry' ) );
		add_action( 'admin_post_abfem_export_single', array( $this, 'handle_export_single' ) );
		add_action( 'admin_post_abfem_export_csv', array( $this, 'handle_export_csv' ) );
		add_action( 'admin_post_abfem_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Register plugin admin menus.
	 */
	public function register_admin_menus() {
		$cap = ABFEM_Capabilities::get_capability();

		// Badge with unread count in main menu title.
		$menu_title   = __( 'AB Form Entries', 'ab-form-entries-manager' );
		$unread_count = ABFEM_Storage::get_unread_count();
		if ( $unread_count > 0 && ABFEM_Settings::get( 'menu_unread_badge', 1 ) ) {
			$menu_title .= sprintf(
				' <span class="update-plugins count-%1$d" style="background:#ef4444;color:#fff;border-radius:10px;padding:0 6px;font-size:10px;font-weight:700;display:inline-block;line-height:16px;height:16px;"><span class="plugin-count">%1$s</span></span>',
				$unread_count,
				number_format_i18n( $unread_count )
			);
		}

		// Main Menu: AB Form Entries.
		$main_hook = add_menu_page(
			__( 'AB Form Entries', 'ab-form-entries-manager' ),
			$menu_title,
			$cap,
			'abfem-submissions',
			array( $this, 'render_submissions_page' ),
			'dashicons-feedback',
			26
		);
		$this->hook_suffixes[] = $main_hook;

		// Submenu 1: All Submissions.
		$sub1 = add_submenu_page(
			'abfem-submissions',
			__( 'All Submissions', 'ab-form-entries-manager' ),
			__( 'All Submissions', 'ab-form-entries-manager' ),
			$cap,
			'abfem-submissions',
			array( $this, 'render_submissions_page' )
		);
		$this->hook_suffixes[] = $sub1;

		// Submenu 2: Forms.
		$sub2 = add_submenu_page(
			'abfem-submissions',
			__( 'Forms', 'ab-form-entries-manager' ),
			__( 'Forms', 'ab-form-entries-manager' ),
			$cap,
			'abfem-forms',
			array( $this, 'render_forms_page' )
		);
		$this->hook_suffixes[] = $sub2;

		// Submenu 3: Export.
		$sub3 = add_submenu_page(
			'abfem-submissions',
			__( 'Export', 'ab-form-entries-manager' ),
			__( 'Export', 'ab-form-entries-manager' ),
			$cap,
			'abfem-export',
			array( $this, 'render_export_page' )
		);
		$this->hook_suffixes[] = $sub3;

		// Submenu 4: Settings.
		$sub4 = add_submenu_page(
			'abfem-submissions',
			__( 'Settings', 'ab-form-entries-manager' ),
			__( 'Settings', 'ab-form-entries-manager' ),
			$cap,
			'abfem-settings',
			array( $this, 'render_settings_page' )
		);
		$this->hook_suffixes[] = $sub4;

		// Submenu 5: Instructions & Guide.
		$sub5 = add_submenu_page(
			'abfem-submissions',
			__( 'Instructions & Guide', 'ab-form-entries-manager' ),
			__( 'Instructions & Guide', 'ab-form-entries-manager' ),
			$cap,
			'abfem-guide',
			array( $this, 'render_guide_page' )
		);
		$this->hook_suffixes[] = $sub5;

		// Hidden route for legacy abfem-integrations URL redirecting cleanly to Settings -> Integrations.
		$sub_int = add_submenu_page(
			null,
			__( 'Integrations', 'ab-form-entries-manager' ),
			__( 'Integrations', 'ab-form-entries-manager' ),
			$cap,
			'abfem-integrations',
			array( $this, 'render_integrations_page' )
		);
		$this->hook_suffixes[] = $sub_int;
	}

	/**
	 * Ensure the AB Form Entries admin menu stays open and active for all plugin views,
	 * preventing WordPress core's Settings menu from being accidentally highlighted.
	 *
	 * @param string $parent_file The parent file for the current admin screen.
	 * @return string
	 */
	public function fix_parent_menu_highlight( $parent_file ) {
		global $plugin_page;
		if ( in_array( $plugin_page, array( 'abfem-submissions', 'abfem-forms', 'abfem-export', 'abfem-settings', 'abfem-guide' ), true ) ) {
			return 'abfem-submissions';
		}
		return $parent_file;
	}

	/**
	 * Ensure the correct submenu item under AB Form Entries is highlighted as current.
	 *
	 * @param string $submenu_file The submenu file.
	 * @param string $parent_file  The parent file.
	 * @return string
	 */
	public function fix_submenu_highlight( $submenu_file, $parent_file ) {
		global $plugin_page;
		if ( in_array( $plugin_page, array( 'abfem-submissions', 'abfem-forms', 'abfem-export', 'abfem-settings', 'abfem-guide' ), true ) ) {
			return $plugin_page;
		}
		return $submenu_file;
	}

	/**
	 * Add custom action links on the plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$cap = ABFEM_Capabilities::get_capability();
		if ( ! current_user_can( $cap ) ) {
			return $links;
		}

		$custom_links = array(
			'settings'    => sprintf( '<a href="%s" style="font-weight:600;color:#2271b1;">%s</a>', esc_url( admin_url( 'admin.php?page=abfem-settings' ) ), esc_html__( 'Settings', 'ab-form-entries-manager' ) ),
			'submissions' => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=abfem-submissions' ) ), esc_html__( 'Submissions', 'ab-form-entries-manager' ) ),
			'guide'       => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=abfem-guide' ) ), esc_html__( 'Instructions', 'ab-form-entries-manager' ) ),
		);

		return array_merge( $custom_links, $links );
	}

	/**
	 * Add custom meta links on the plugins page.
	 *
	 * @param array  $links Existing plugin row meta links.
	 * @param string $file  Plugin file basename.
	 * @return array
	 */
	public function add_plugin_row_meta( $links, $file ) {
		if ( ABFEM_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		$row_meta = array(
			'guide'  => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=abfem-guide' ) ), esc_html__( 'Instructions & Guide', 'ab-form-entries-manager' ) ),
			'export' => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=abfem-export' ) ), esc_html__( 'CSV Export', 'ab-form-entries-manager' ) ),
		);

		return array_merge( $links, $row_meta );
	}

	/**
	 * Add quick shortcut to the WordPress admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 */
	public function add_admin_bar_node( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! ABFEM_Capabilities::can_manage() || ! ABFEM_Settings::get( 'admin_bar_menu', 1 ) ) {
			return;
		}

		$unread = ABFEM_Storage::get_unread_count();
		$title  = '<span class="ab-icon dashicons dashicons-feedback" style="font-size:18px;margin-top:2px;"></span><span class="ab-label">' . esc_html__( 'Form Entries', 'ab-form-entries-manager' ) . '</span>';
		if ( $unread > 0 ) {
			$title .= sprintf( ' <span class="ab-item" style="background:#ef4444;color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700;">%d</span>', $unread );
		}

		$wp_admin_bar->add_node( array(
			'id'    => 'abfem-node',
			'title' => $title,
			'href'  => admin_url( 'admin.php?page=abfem-submissions' ),
		) );

		$wp_admin_bar->add_node( array(
			'id'     => 'abfem-submissions-node',
			'parent' => 'abfem-node',
			'title'  => esc_html__( 'All Submissions', 'ab-form-entries-manager' ),
			'href'   => admin_url( 'admin.php?page=abfem-submissions' ),
		) );

		$wp_admin_bar->add_node( array(
			'id'     => 'abfem-forms-node',
			'parent' => 'abfem-node',
			'title'  => esc_html__( 'Forms', 'ab-form-entries-manager' ),
			'href'   => admin_url( 'admin.php?page=abfem-forms' ),
		) );

		$wp_admin_bar->add_node( array(
			'id'     => 'abfem-export-node',
			'parent' => 'abfem-node',
			'title'  => esc_html__( 'Export to CSV', 'ab-form-entries-manager' ),
			'href'   => admin_url( 'admin.php?page=abfem-export' ),
		) );

		$wp_admin_bar->add_node( array(
			'id'     => 'abfem-settings-node',
			'parent' => 'abfem-node',
			'title'  => esc_html__( 'Settings', 'ab-form-entries-manager' ),
			'href'   => admin_url( 'admin.php?page=abfem-settings' ),
		) );

		$wp_admin_bar->add_node( array(
			'id'     => 'abfem-guide-node',
			'parent' => 'abfem-node',
			'title'  => esc_html__( 'Instructions & Guide', 'ab-form-entries-manager' ),
			'href'   => admin_url( 'admin.php?page=abfem-guide' ),
		) );
	}

	/**
	 * Enqueue CSS and JavaScript assets on plugin admin pages and the WordPress dashboard.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		// Only enqueue on our plugin pages or on the main WordPress dashboard.
		$is_plugin_page = false;
		if ( in_array( $hook_suffix, $this->hook_suffixes, true ) ) {
			$is_plugin_page = true;
		} elseif ( isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'abfem-' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_plugin_page = true;
		} elseif ( 'index.php' === $hook_suffix ) {
			$is_plugin_page = true;
		}

		if ( ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'abfem-admin',
			ABFEM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			ABFEM_VERSION
		);

		wp_enqueue_script(
			'abfem-admin',
			ABFEM_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			ABFEM_VERSION,
			true
		);

		wp_localize_script(
			'abfem-admin',
			'abfemData',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'abfem_admin_nonce' ),
				'dismissNoticeNonce' => wp_create_nonce( 'abfem_dismiss_notice_nonce' ),
				'confirmDelete'      => __( 'Are you sure you want to permanently delete the selected submission(s)? This action cannot be undone.', 'ab-form-entries-manager' ),
				'noSelected'         => __( 'Please select at least one submission to perform this action.', 'ab-form-entries-manager' ),
				'copySuccess'        => __( 'System report copied to clipboard!', 'ab-form-entries-manager' ),
				'generating'         => __( 'Generating sample entry...', 'ab-form-entries-manager' ),
				'sending'            => __( 'Sending test email...', 'ab-form-entries-manager' ),
				'clearing'           => __( 'Clearing cache...', 'ab-form-entries-manager' ),
			)
		);
	}

	/**
	 * Display dismissible admin notices.
	 */
	public function display_admin_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['page'] ) || 0 !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'abfem-' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['abfem_notice'] ) ? sanitize_key( wp_unslash( $_GET['abfem_notice'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count  = isset( $_GET['count'] ) ? absint( wp_unslash( $_GET['count'] ) ) : 0;

		if ( empty( $notice ) ) {
			return;
		}

		$message = '';
		$type    = 'success';

		switch ( $notice ) {
			case 'read_marked':
				/* translators: %d: Number of entries */
				$message = sprintf( esc_html( _n( '%d submission marked as read.', '%d submissions marked as read.', $count, 'ab-form-entries-manager' ) ), $count );
				break;
			case 'unread_marked':
				/* translators: %d: Number of entries */
				$message = sprintf( esc_html( _n( '%d submission marked as unread.', '%d submissions marked as unread.', $count, 'ab-form-entries-manager' ) ), $count );
				break;
			case 'deleted':
				/* translators: %d: Number of entries */
				$message = sprintf( esc_html( _n( '%d submission permanently deleted.', '%d submissions permanently deleted.', $count, 'ab-form-entries-manager' ) ), $count );
				break;
			case 'single_deleted':
				$message = esc_html__( 'Submission permanently deleted.', 'ab-form-entries-manager' );
				break;
			case 'settings_saved':
				$message = esc_html__( 'Settings successfully saved.', 'ab-form-entries-manager' );
				break;
			case 'no_selection':
				$message = esc_html__( 'No submissions were selected.', 'ab-form-entries-manager' );
				$type    = 'warning';
				break;
			case 'action_failed':
				$message = esc_html__( 'An error occurred while processing the request.', 'ab-form-entries-manager' );
				$type    = 'error';
				break;
		}

		if ( ! empty( $message ) ) {
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
	}

	/**
	 * Render submissions page (or detail screen if action=view).
	 */
	public function render_submissions_page() {
		ABFEM_Capabilities::check_manage_permission();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$entry_id = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0;

		// Extract filter parameters to preserve across views.
		$filter_params = $this->extract_filter_params_from_request();

		if ( 'view' === $action && $entry_id > 0 ) {
			ABFEM_Detail_View::render( $entry_id, $filter_params );
			return;
		}

		// Prepare list table.
		$all_forms = $this->integrations_manager->get_all_forms();
		$list_table = new ABFEM_Admin_List_Table( $filter_params );
		$active_count = $this->integrations_manager->get_active_count();

		require_once ABFEM_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render Forms page.
	 */
	public function render_forms_page() {
		ABFEM_Capabilities::check_manage_permission();
		$all_forms = $this->integrations_manager->get_all_forms();
		require_once ABFEM_PLUGIN_DIR . 'admin/views/forms.php';
	}

	/**
	 * Early redirect for legacy pages before headers are sent.
	 */
	public function maybe_redirect_legacy_pages() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'abfem-integrations' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=abfem-settings&tab=integrations' ) );
			exit;
		}
	}

	/**
	 * Redirect legacy Integrations page to Settings -> Integrations tab.
	 */
	public function render_integrations_page() {
		ABFEM_Capabilities::check_manage_permission();
		$target_url = admin_url( 'admin.php?page=abfem-settings&tab=integrations' );
		if ( ! headers_sent() ) {
			wp_safe_redirect( $target_url );
			exit;
		}
		echo '<script>window.location.replace(' . wp_json_encode( $target_url ) . ');</script>';
		echo '<div class="wrap abfem-wrap"><p>' . esc_html__( 'Redirecting to Form Integrations settings...', 'ab-form-entries-manager' ) . ' <a href="' . esc_url( $target_url ) . '">' . esc_html__( 'Click here if you are not redirected.', 'ab-form-entries-manager' ) . '</a></p></div>';
	}

	/**
	 * Render Export page.
	 */
	public function render_export_page() {
		ABFEM_Capabilities::check_manage_permission();
		$all_forms = $this->integrations_manager->get_all_forms();
		require_once ABFEM_PLUGIN_DIR . 'admin/views/export.php';
	}

	/**
	 * Render Settings page.
	 */
	public function render_settings_page() {
		ABFEM_Capabilities::check_manage_permission();
		$settings     = ABFEM_Settings::get_all();
		$integrations = $this->integrations_manager->get_integrations();
		require_once ABFEM_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render Instructions & Guide page.
	 */
	public function render_guide_page() {
		ABFEM_Capabilities::check_manage_permission();
		require ABFEM_PLUGIN_DIR . 'admin/views/guide.php';
	}

	/**
	 * Extract filter parameters from GET or POST request.
	 *
	 * @return array
	 */
	private function extract_filter_params_from_request() {
		$params = array();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		$keys = array( 'search', 'source_plugin', 'form_id', 'status', 'date_preset', 'date_from', 'date_to', 'orderby', 'order', 'page', 'paged' );

		foreach ( $keys as $k ) {
			if ( isset( $_GET[ $k ] ) && '' !== $_GET[ $k ] ) {
				$params[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) );
			} elseif ( isset( $_POST[ 'filter_' . $k ] ) && '' !== $_POST[ 'filter_' . $k ] ) {
				$params[ $k ] = sanitize_text_field( wp_unslash( $_POST[ 'filter_' . $k ] ) );
			}
		}

		if ( isset( $params['paged'] ) ) {
			$params['page'] = absint( $params['paged'] );
			unset( $params['paged'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

		return $params;
	}

	/**
	 * Handle Bulk Action POST submission.
	 */
	public function handle_bulk_action() {
		ABFEM_Capabilities::check_manage_permission();
		check_admin_referer( 'abfem_bulk_action_nonce', 'nonce' );

		$action = '-1';
		if ( ! empty( $_POST['doaction2'] ) && ! empty( $_POST['bulk_action2'] ) && '-1' !== $_POST['bulk_action2'] ) {
			$action = sanitize_key( wp_unslash( $_POST['bulk_action2'] ) );
		} elseif ( ! empty( $_POST['bulk_action'] ) && '-1' !== $_POST['bulk_action'] ) {
			$action = sanitize_key( wp_unslash( $_POST['bulk_action'] ) );
		} elseif ( ! empty( $_POST['bulk_action2'] ) && '-1' !== $_POST['bulk_action2'] ) {
			$action = sanitize_key( wp_unslash( $_POST['bulk_action2'] ) );
		}

		$entry_ids = isset( $_POST['entry_ids'] ) && is_array( $_POST['entry_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['entry_ids'] ) ) : array();
		$entry_ids = array_filter( $entry_ids );

		$filter_params = $this->extract_filter_params_from_request();
		$redirect_url  = add_query_arg( array_merge( array( 'page' => 'abfem-submissions' ), $filter_params ), admin_url( 'admin.php' ) );

		if ( empty( $entry_ids ) ) {
			wp_safe_redirect( add_query_arg( 'abfem_notice', 'no_selection', $redirect_url ) );
			exit;
		}

		switch ( $action ) {
			case 'mark_read':
				$count = ABFEM_Storage::bulk_update_read_status( $entry_ids, 1 );
				wp_safe_redirect( add_query_arg( array( 'abfem_notice' => 'read_marked', 'count' => $count ), $redirect_url ) );
				exit;

			case 'mark_unread':
				$count = ABFEM_Storage::bulk_update_read_status( $entry_ids, 0 );
				wp_safe_redirect( add_query_arg( array( 'abfem_notice' => 'unread_marked', 'count' => $count ), $redirect_url ) );
				exit;

			case 'delete':
				$count = ABFEM_Storage::bulk_delete_entries( $entry_ids );
				wp_safe_redirect( add_query_arg( array( 'abfem_notice' => 'deleted', 'count' => $count ), $redirect_url ) );
				exit;

			case 'export_csv':
				ABFEM_CSV_Exporter::export( array( 'include' => $entry_ids ) );
				exit;

			default:
				wp_safe_redirect( $redirect_url );
				exit;
		}
	}

	/**
	 * Handle Toggle Read status action.
	 */
	public function handle_toggle_read() {
		ABFEM_Capabilities::check_manage_permission();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$entry_id = isset( $_REQUEST['entry_id'] ) ? absint( wp_unslash( $_REQUEST['entry_id'] ) ) : 0;
		check_admin_referer( 'abfem_toggle_read_' . $entry_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_read  = isset( $_REQUEST['is_read'] ) ? absint( wp_unslash( $_REQUEST['is_read'] ) ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$redirect = isset( $_REQUEST['redirect'] ) ? sanitize_key( wp_unslash( $_REQUEST['redirect'] ) ) : 'list';

		ABFEM_Storage::update_read_status( $entry_id, $is_read );

		$filter_params = $this->extract_filter_params_from_request();

		if ( 'detail' === $redirect ) {
			$url = add_query_arg( array_merge( array( 'page' => 'abfem-submissions', 'action' => 'view', 'entry_id' => $entry_id, 'abfem_notice' => $is_read ? 'read_marked' : 'unread_marked', 'count' => 1 ), $filter_params ), admin_url( 'admin.php' ) );
		} else {
			$url = add_query_arg( array_merge( array( 'page' => 'abfem-submissions', 'abfem_notice' => $is_read ? 'read_marked' : 'unread_marked', 'count' => 1 ), $filter_params ), admin_url( 'admin.php' ) );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Handle single entry deletion.
	 */
	public function handle_delete_entry() {
		ABFEM_Capabilities::check_manage_permission();

		$entry_id = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0;
		check_admin_referer( 'abfem_delete_entry_' . $entry_id );

		ABFEM_Storage::delete_entry( $entry_id, true );

		$filter_params = $this->extract_filter_params_from_request();
		$url = add_query_arg( array_merge( array( 'page' => 'abfem-submissions', 'abfem_notice' => 'single_deleted' ), $filter_params ), admin_url( 'admin.php' ) );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Handle single entry CSV export.
	 */
	public function handle_export_single() {
		ABFEM_Capabilities::check_manage_permission();

		$entry_id = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0;
		check_admin_referer( 'abfem_export_single_' . $entry_id );

		ABFEM_CSV_Exporter::export( array( 'entry_id' => $entry_id ) );
		exit;
	}

	/**
	 * Handle Export page CSV download form.
	 */
	public function handle_export_csv() {
		ABFEM_Capabilities::check_manage_permission();
		check_admin_referer( 'abfem_export_nonce', 'nonce' );

		$args = array();

		if ( ! empty( $_POST['source_plugin'] ) ) {
			$args['source_plugin'] = sanitize_key( wp_unslash( $_POST['source_plugin'] ) );
		}
		if ( ! empty( $_POST['form_id'] ) ) {
			$args['form_id'] = sanitize_text_field( wp_unslash( $_POST['form_id'] ) );
		}
		if ( ! empty( $_POST['status'] ) ) {
			$args['status'] = sanitize_key( wp_unslash( $_POST['status'] ) );
		}
		if ( ! empty( $_POST['date_from'] ) ) {
			$args['date_from'] = sanitize_text_field( wp_unslash( $_POST['date_from'] ) );
		}
		if ( ! empty( $_POST['date_to'] ) ) {
			$args['date_to'] = sanitize_text_field( wp_unslash( $_POST['date_to'] ) );
		}

		ABFEM_CSV_Exporter::export( $args );
		exit;
	}

	/**
	 * Handle saving settings form.
	 */
	public function handle_save_settings() {
		ABFEM_Capabilities::check_manage_permission();
		check_admin_referer( 'abfem_settings_nonce', 'nonce' );

		$raw_settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized inside ABFEM_Settings::save.
		ABFEM_Settings::save( $raw_settings );

		// Update daily retention cleanup schedule if retention changed.
		$retention = absint( $raw_settings['retention_days'] ?? 0 );
		$timestamp = wp_next_scheduled( 'abfem_daily_retention_cleanup' );

		if ( $retention > 0 && ! $timestamp ) {
			wp_schedule_event( time(), 'daily', 'abfem_daily_retention_cleanup' );
		} elseif ( 0 === $retention && $timestamp ) {
			wp_unschedule_event( $timestamp, 'abfem_daily_retention_cleanup' );
		}

		$active_tab = ! empty( $_POST['active_tab'] ) ? sanitize_key( wp_unslash( $_POST['active_tab'] ) ) : 'general';
		wp_safe_redirect( add_query_arg( array( 'page' => 'abfem-settings', 'tab' => $active_tab, 'abfem_notice' => 'settings_saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * AJAX handler: Generate a realistic sample submission for diagnostic testing.
	 */
	public function ajax_generate_sample_entry() {
		ABFEM_Capabilities::check_manage_permission();
		check_ajax_referer( 'abfem_admin_nonce', 'nonce' );

		$active_plugin = 'wpforms';
		$active_name   = 'WPForms';
		foreach ( $this->integrations_manager->get_integrations() as $id => $int ) {
			if ( $int->is_active() ) {
				$active_plugin = $id;
				$active_name   = $int->get_name();
				break;
			}
		}

		$first_names = array( 'Sarah', 'Alex', 'Emily', 'Marcus', 'Olivia', 'David', 'Jessica' );
		$last_names  = array( 'Connor', 'Chen', 'Johnson', 'Miller', 'Smith', 'Taylor', 'Davis' );
		$fn = $first_names[ array_rand( $first_names ) ];
		$ln = $last_names[ array_rand( $last_names ) ];
		$full_name = $fn . ' ' . $ln;
		$email     = strtolower( $fn . '.' . $ln ) . '@example.com';

		$sample_data = array(
			'source_plugin'      => $active_plugin,
			'source_plugin_name' => $active_name,
			'source_form_id'     => (string) wp_rand( 10, 99 ),
			'source_form_name'   => __( 'Contact & Inquiry Form', 'ab-form-entries-manager' ),
			'source_entry_id'    => 'sample_' . time() . '_' . wp_rand( 100, 999 ),
			'source_url'         => home_url( '/contact-us/' ),
			'submitter_name'     => $full_name,
			'submitter_email'    => $email,
			'submitter_ip'       => '192.168.1.42',
			'fields'             => array(
				array(
					'name'  => 'full_name',
					'label' => __( 'Full Name', 'ab-form-entries-manager' ),
					'type'  => 'text',
					'value' => $full_name,
				),
				array(
					'name'  => 'email',
					'label' => __( 'Email Address', 'ab-form-entries-manager' ),
					'type'  => 'email',
					'value' => $email,
				),
				array(
					'name'  => 'phone',
					'label' => __( 'Phone Number', 'ab-form-entries-manager' ),
					'type'  => 'tel',
					'value' => '+1 (555) 019-' . wp_rand( 1000, 9999 ),
				),
				array(
					'name'  => 'inquiry_type',
					'label' => __( 'Subject / Inquiry', 'ab-form-entries-manager' ),
					'type'  => 'text',
					'value' => __( 'Customer Question & Demonstration', 'ab-form-entries-manager' ),
				),
				array(
					'name'  => 'message',
					'label' => __( 'Message', 'ab-form-entries-manager' ),
					'type'  => 'textarea',
					'value' => __( 'Hello! This is an automatically generated test submission created from the AB Form Entries Manager settings diagnostics panel.', 'ab-form-entries-manager' ),
				),
			),
		);

		$entry_id = ABFEM_Submission_Service::process_submission( $sample_data );
		if ( ! $entry_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create test entry. Please check debug logs.', 'ab-form-entries-manager' ) ) );
		}

		delete_transient( 'abfem_counts' );

		wp_send_json_success( array(
			'entry_id' => $entry_id,
			'view_url' => admin_url( 'admin.php?page=abfem-submissions&action=view&entry_id=' . $entry_id ),
			/* translators: %d: Submission ID */
			'message'  => sprintf( __( 'Test submission #%d successfully created! Click to view.', 'ab-form-entries-manager' ), $entry_id ),
		) );
	}

	/**
	 * AJAX handler: Send test email notification.
	 */
	public function ajax_send_test_email() {
		ABFEM_Capabilities::check_manage_permission();
		check_ajax_referer( 'abfem_admin_nonce', 'nonce' );

		$to = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( empty( $to ) || ! is_email( $to ) ) {
			$to = ABFEM_Settings::get( 'notification_email', get_option( 'admin_email' ) );
		}

		if ( empty( $to ) || ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address specified.', 'ab-form-entries-manager' ) ) );
		}

		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		/* translators: %s: Site name */
		$subject   = sprintf( __( '[%s] AB Form Entries Manager: Test Email Notification', 'ab-form-entries-manager' ), $site_name );
		/* translators: 1: Site name, 2: Home URL, 3: Current timestamp */
		$body      = sprintf(
			__( "Hello!\n\nThis is a verification test email sent from AB Form Entries Manager on %1\$s (%2\$s).\n\nYour WordPress email notification system is working properly!\n\nSent at: %3\$s", 'ab-form-entries-manager' ),
			$site_name,
			home_url(),
			current_time( 'mysql' )
		);

		$sent = wp_mail( $to, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
		if ( $sent ) {
			/* translators: %s: Recipient email address */
			wp_send_json_success( array( 'message' => sprintf( __( 'Test email successfully dispatched to %s!', 'ab-form-entries-manager' ), $to ) ) );
		} else {
			/* translators: %s: Recipient email address */
			wp_send_json_error( array( 'message' => sprintf( __( 'wp_mail() could not deliver to %s. Please check your SMTP mail server settings.', 'ab-form-entries-manager' ), $to ) ) );
		}
	}

	/**
	 * AJAX handler: Clear cached transients.
	 */
	public function ajax_clear_cache() {
		ABFEM_Capabilities::check_manage_permission();
		check_ajax_referer( 'abfem_admin_nonce', 'nonce' );

		delete_transient( 'abfem_counts' );
		wp_send_json_success( array( 'message' => __( 'Cache and counters cleared successfully!', 'ab-form-entries-manager' ) ) );
	}

	/**
	 * Register the AB Form Entries dashboard widget (metabox) on /wp-admin/index.php.
	 */
	public function register_dashboard_widget() {
		if ( ! ABFEM_Capabilities::can_manage() || ! ABFEM_Settings::get( 'dashboard_widget', 1 ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'abfem_dashboard_widget',
			__( 'AB Form Entries — Submissions Overview', 'ab-form-entries-manager' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render the AB Form Entries dashboard widget content.
	 */
	public function render_dashboard_widget() {
		if ( ! ABFEM_Capabilities::can_manage() ) {
			return;
		}

		$counts               = ABFEM_Storage::get_counts();
		$active_builder_count = $this->integrations_manager->get_active_count();
		$entries_query        = ABFEM_Storage::get_entries(
			array(
				'page'     => 1,
				'per_page' => 5,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		$recent_entries       = ! empty( $entries_query['entries'] ) ? $entries_query['entries'] : array();

		require_once ABFEM_PLUGIN_DIR . 'admin/views/dashboard-widget.php';
	}

	/**
	 * Display submission notification banner on /wp-admin/index.php.
	 */
	public function display_dashboard_notices() {
		global $pagenow;

		// Only show on the main WordPress admin dashboard (/wp-admin/index.php).
		if ( 'index.php' !== $pagenow ) {
			return;
		}

		if ( ! ABFEM_Capabilities::can_manage() || ! ABFEM_Settings::get( 'dashboard_notice', 1 ) ) {
			return;
		}

		$unread_count = ABFEM_Storage::get_unread_count();
		if ( $unread_count <= 0 ) {
			return;
		}

		// Check if this notification was already dismissed for the current latest entry.
		$latest_unread_id = ABFEM_Storage::get_latest_unread_entry_id();
		$dismissed_id     = (int) get_user_meta( get_current_user_id(), 'abfem_dismissed_latest_id', true );

		if ( $dismissed_id && $latest_unread_id > 0 && $latest_unread_id <= $dismissed_id ) {
			return;
		}

		require_once ABFEM_PLUGIN_DIR . 'admin/views/dashboard-notice.php';
	}

	/**
	 * AJAX handler to dismiss the dashboard notice for the current latest unread entry.
	 */
	public function ajax_dismiss_dashboard_notice() {
		check_ajax_referer( 'abfem_dismiss_notice_nonce', 'nonce' );

		if ( ! ABFEM_Capabilities::can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'ab-form-entries-manager' ) ), 403 );
		}

		$latest_id = isset( $_POST['latest_id'] ) ? absint( $_POST['latest_id'] ) : 0;
		if ( ! $latest_id ) {
			$latest_id = ABFEM_Storage::get_latest_unread_entry_id();
		}

		if ( $latest_id > 0 ) {
			update_user_meta( get_current_user_id(), 'abfem_dismissed_latest_id', $latest_id );
		}

		wp_send_json_success( array( 'dismissed' => true, 'latest_id' => $latest_id ) );
	}
}
