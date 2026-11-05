<?php

/**
 * Plugin Name:       AB Form Entries Manager
 * Description:       Universal, zero-configuration form submission manager for WordPress. Automatically collects, manages, filters, and exports entries from Contact Form 7, WPForms, Forminator, Elementor Pro Forms, Gravity Forms, Ninja Forms, Formidable Forms, and Jetpack Forms in one modern dashboard.
 * Version:           1.0.0
 * Author:            Ayush Barasani
 * Author URI:        https://ayushbarasani.com.np
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ab-form-entries-manager
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 *
 * @package AB_Form_Entries_Manager
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Core plugin constants.
 */
define('ABFEM_VERSION', '1.0.0');
define('ABFEM_PLUGIN_FILE', __FILE__);
define('ABFEM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABFEM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABFEM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ABFEM_POST_TYPE', 'abfem_entry');

/**
 * Autoloader for AB Form Entries Manager classes.
 *
 * @param string $class_name Class name being requested.
 */
spl_autoload_register(function ($class_name) {
	if (0 !== strpos($class_name, 'ABFEM_')) {
		return;
	}

	$file_name = 'class-' . strtolower(str_replace('_', '-', substr($class_name, 6))) . '.php';

	// Check includes/ directory first.
	$file_path = ABFEM_PLUGIN_DIR . 'includes/' . $file_name;
	if (file_exists($file_path)) {
		require_once $file_path;
		return;
	}

	// Check includes/integrations/ directory next.
	$integration_path = ABFEM_PLUGIN_DIR . 'includes/integrations/' . $file_name;
	if (file_exists($integration_path)) {
		require_once $integration_path;
		return;
	}
});

/**
 * Load plugin interfaces and dependencies.
 */
require_once ABFEM_PLUGIN_DIR . 'includes/integrations/interface-integration.php';
require_once ABFEM_PLUGIN_DIR . 'includes/integrations/class-base-integration.php';

/**
 * Plugin activation hook.
 */
register_activation_hook(__FILE__, function () {
	require_once ABFEM_PLUGIN_DIR . 'includes/class-activator.php';
	ABFEM_Activator::activate();
});

/**
 * Plugin deactivation hook.
 */
register_deactivation_hook(__FILE__, function () {
	require_once ABFEM_PLUGIN_DIR . 'includes/class-deactivator.php';
	ABFEM_Deactivator::deactivate();
});

/**
 * Main plugin access function.
 *
 * @return ABFEM_Plugin
 */
function abfem()
{
	return ABFEM_Plugin::instance();
}

/**
 * Initialize the plugin on plugins_loaded.
 */
add_action('plugins_loaded', function () {
	abfem()->init();
}, 5);

// Ensure execution if plugins_loaded has already fired (e.g., late inclusion or CLI).
if (function_exists('did_action') && did_action('plugins_loaded')) {
	abfem()->init();
}
