<?php

/**
 * Forms overview list view template.
 *
 * @package AB_Form_Entries_Manager
 */

if (! defined('ABSPATH')) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wrap abfem-wrap abfem-forms-wrap">
	<!-- Modern Brand Header -->
	<div class="abfem-modern-header">
		<div class="abfem-header-left">
			<div class="abfem-logo-icon">
				<span class="dashicons dashicons-category"></span>
			</div>
			<div class="abfem-header-title-box">
				<div class="abfem-title-row">
					<h1 class="abfem-header-title"><?php esc_html_e('Discovered Forms', 'ab-form-entries-manager'); ?></h1>
					<span class="abfem-version-pill"><?php echo esc_html('v' . ABFEM_VERSION); ?></span>
				</div>
				<p class="abfem-subtitle"><?php esc_html_e('Forms automatically detected across all your installed form builder plugins.', 'ab-form-entries-manager'); ?></p>
			</div>
		</div>

		<div class="abfem-header-actions">
			<a href="<?php echo esc_url(admin_url('admin.php?page=abfem-submissions')); ?>" class="button button-secondary abfem-btn-modern">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e('All Submissions', 'ab-form-entries-manager'); ?>
			</a>
			<a href="<?php echo esc_url(admin_url('admin.php?page=abfem-settings')); ?>" class="button button-primary abfem-btn-modern">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Settings', 'ab-form-entries-manager'); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<table class="wp-list-table widefat fixed striped table-view-list abfem-forms-table">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-id" style="width: 120px;"><?php esc_html_e('S.N.', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-name"><?php esc_html_e('Form Name', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-plugin" style="width: 170px;"><?php esc_html_e('Form Builder', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-count" style="width: 130px;"><?php esc_html_e('Submissions', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-recent" style="width: 180px;"><?php esc_html_e('Latest Submission', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-status" style="width: 110px;"><?php esc_html_e('Status', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-actions" style="width: 150px;"><?php esc_html_e('Actions', 'ab-form-entries-manager'); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php if (empty($all_forms)) : ?>
				<tr class="no-items">
					<td colspan="7">
						<div class="abfem-empty-state">
							<span class="dashicons dashicons-category abfem-empty-icon"></span>
							<h3><?php esc_html_e('No forms discovered yet', 'ab-form-entries-manager'); ?></h3>
							<p class="abfem-empty-desc"><?php esc_html_e('Make sure at least one supported form plugin is active (Contact Form 7, WPForms, Elementor Pro, Gravity Forms, Ninja Forms, Forminator, Jetpack, or Formidable).', 'ab-form-entries-manager'); ?></p>
							<a href="<?php echo esc_url(admin_url('admin.php?page=abfem-settings&tab=integrations')); ?>" class="button button-primary">
								<span class="dashicons dashicons-networking"></span> <?php esc_html_e('View Supported Integrations', 'ab-form-entries-manager'); ?>
							</a>
						</div>
					</td>
				</tr>
			<?php else : ?>
				<?php
				$counter = 1;
				foreach ($all_forms as $form) :
					$filter_url = add_query_arg(
						array(
							'page'          => 'abfem-submissions',
							'form_id'       => $form['form_id'],
							'source_plugin' => $form['source_plugin'],
						),
						admin_url('admin.php')
					);
				?>
					<tr>
						<td class="column-id">
							<code><?php echo esc_html($counter); ?></code>
						</td>

						<td class="column-name">
							<strong>
								<a href="<?php echo esc_url($filter_url); ?>" class="row-title">
									<?php echo esc_html($form['form_name']); ?>
								</a>
							</strong>
						</td>

						<td class="column-plugin">
							<span class="abfem-badge abfem-badge-<?php echo esc_attr($form['source_plugin']); ?>">
								<?php echo esc_html($form['source_plugin_name'] ?? ucfirst($form['source_plugin'])); ?>
							</span>
						</td>

						<td class="column-count">
							<span class="abfem-count-pill"><?php echo esc_html(number_format_i18n($form['submission_count'])); ?></span>
						</td>

						<td class="column-recent">
							<?php if (! empty($form['latest_submission'])) :
								$time = strtotime($form['latest_submission']);
								echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $time));
							?>
								<div class="abfem-muted">(<?php echo esc_html(human_time_diff($time, time()) . ' ' . __('ago', 'ab-form-entries-manager')); ?>)</div>
							<?php else : ?>
								<span class="abfem-muted"><?php esc_html_e('None yet', 'ab-form-entries-manager'); ?></span>
							<?php endif; ?>
						</td>

						<td class="column-status">
							<?php if ('active' === $form['status']) : ?>
								<span class="abfem-status-pill abfem-status-read"><?php esc_html_e('Active', 'ab-form-entries-manager'); ?></span>
							<?php else : ?>
								<span class="abfem-status-pill abfem-status-inactive"><?php esc_html_e('Inactive', 'ab-form-entries-manager'); ?></span>
							<?php endif; ?>
						</td>

						<td class="column-actions">
							<a href="<?php echo esc_url($filter_url); ?>" class="button button-small button-secondary">
								<?php esc_html_e('View Submissions', 'ab-form-entries-manager'); ?>
							</a>
						</td>
					</tr>
					<?php $counter++; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>

		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-id"><?php esc_html_e('Form ID', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-name"><?php esc_html_e('Form Name', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-plugin"><?php esc_html_e('Form Builder', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-count"><?php esc_html_e('Submissions', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-recent"><?php esc_html_e('Latest Submission', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'ab-form-entries-manager'); ?></th>
				<th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'ab-form-entries-manager'); ?></th>
			</tr>
		</tfoot>
	</table>
</div>