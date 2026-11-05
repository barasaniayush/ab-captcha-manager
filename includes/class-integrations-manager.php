<?php
/**
 * Integrations registry and coordinator.
 *
 * @package AB_Form_Entries_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ABFEM_Integrations_Manager
 */
class ABFEM_Integrations_Manager {

	/**
	 * Registered integration instances.
	 *
	 * @var ABFEM_Integration_Interface[]
	 */
	private $integrations = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_integrations();
	}

	/**
	 * Register default supported integrations.
	 */
	private function register_default_integrations() {
		$classes = array(
			'cf7'          => 'ABFEM_Contact_Form_7',
			'elementor'    => 'ABFEM_Elementor',
			'gravityforms' => 'ABFEM_Gravity_Forms',
			'ninjaforms'   => 'ABFEM_Ninja_Forms',
			'wpforms'      => 'ABFEM_WPForms',
			'jetpack'      => 'ABFEM_Jetpack_Forms',
			'formidable'   => 'ABFEM_Formidable_Forms',
			'forminator'   => 'ABFEM_Forminator',
		);

		foreach ( $classes as $id => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->integrations[ $id ] = new $class_name();
			}
		}
	}

	/**
	 * Initialize all active integrations.
	 */
	public function init() {
		foreach ( $this->integrations as $id => $integration ) {
			if ( $integration->is_active() && ABFEM_Settings::is_integration_enabled( $id ) ) {
				$integration->init();
			}
		}
	}

	/**
	 * Get all registered integrations.
	 *
	 * @return ABFEM_Integration_Interface[]
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 * Get specific integration by identifier.
	 *
	 * @param string $id Integration key.
	 * @return ABFEM_Integration_Interface|null
	 */
	public function get_integration( $id ) {
		return $this->integrations[ $id ] ?? null;
	}

	/**
	 * Count active, ready integrations.
	 *
	 * @return int
	 */
	public function get_active_count() {
		$count = 0;
		foreach ( $this->integrations as $id => $integration ) {
			if ( $integration->is_active() && ABFEM_Settings::is_integration_enabled( $id ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Gather all discovered forms across all integrations.
	 *
	 * @return array
	 */
	public function get_all_forms() {
		$forms_map = array();

		// 1. Gather forms from active plugin APIs.
		foreach ( $this->integrations as $int_id => $integration ) {
			if ( ! $integration->is_active() ) {
				continue;
			}

			$builder_forms = $integration->get_forms();
			if ( is_array( $builder_forms ) ) {
				foreach ( $builder_forms as $f ) {
					$key = $int_id . '_' . $f['id'];
					$forms_map[ $key ] = array(
						'form_id'            => (string) $f['id'],
						'form_name'          => $f['name'],
						'source_plugin'      => $int_id,
						'source_plugin_name' => $integration->get_name(),
						'submission_count'   => 0,
						'latest_submission'  => '',
						'status'             => 'active',
					);
				}
			}
		}

		// 2. Gather recorded submissions summary to merge actual counts and detect any forms from historical entries.
		$stored_summary = ABFEM_Storage::get_all_forms_summary();
		foreach ( $stored_summary as $summary ) {
			$int_id   = $summary['source_plugin'];
			$form_id  = $summary['form_id'];
			$key      = $int_id . '_' . $form_id;

			$plugin_instance = $this->get_integration( $int_id );
			$plugin_name     = $plugin_instance ? $plugin_instance->get_name() : ucfirst( $int_id );

			if ( isset( $forms_map[ $key ] ) ) {
				$forms_map[ $key ]['submission_count']  = (int) $summary['submission_count'];
				$forms_map[ $key ]['latest_submission'] = $summary['latest_submission'];
				if ( ! empty( $summary['form_name'] ) && __( 'Untitled Form', 'ab-form-entries-manager' ) !== $summary['form_name'] ) {
					$forms_map[ $key ]['form_name'] = $summary['form_name'];
				}
			} else {
				/* translators: %s: Form identifier */
				$fallback_form_name = sprintf( __( 'Form #%s', 'ab-form-entries-manager' ), $form_id );

				$forms_map[ $key ] = array(
					'form_id'            => (string) $form_id,
					'form_name'          => ! empty( $summary['form_name'] ) ? $summary['form_name'] : $fallback_form_name,
					'source_plugin'      => $int_id,
					'source_plugin_name' => $plugin_name,
					'submission_count'   => (int) $summary['submission_count'],
					'latest_submission'  => $summary['latest_submission'],
					'status'             => ( $plugin_instance && $plugin_instance->is_active() ) ? 'active' : 'inactive',
				);
			}
		}

		return array_values( $forms_map );
	}
}
