/**
 * AB Form Entries Manager - Admin JavaScript
 * Modern interactivity: tab routing, presets, test actions, and AJAX helpers.
 */
(function ($) {
	'use strict';

	$(function () {
		// =====================================================================
		// 1. Settings Tab Switching & URL Sync
		// =====================================================================
		function switchTab(tabId) {
			if (!tabId) {
				return;
			}
			tabId = tabId.toString().replace('#tab-', '').replace('#', '');

			var $targetBtn = $('.abfem-tab-btn[data-tab="' + tabId + '"]');
			var $targetPane = $('#tab-' + tabId);

			if ($targetPane.length) {
				$('.abfem-tab-btn').removeClass('abfem-tab-active');
				$('.abfem-tab-pane').removeClass('abfem-pane-active');

				$targetBtn.addClass('abfem-tab-active');
				$targetPane.addClass('abfem-pane-active');

				var $activeTabInput = $('#abfem-active-tab-input');
				if ($activeTabInput.length) {
					$activeTabInput.val(tabId);
				}

				// Update URL with tab param and hash without full page reload.
				if (window.history && window.history.replaceState) {
					try {
						var url = new URL(window.location.href);
						url.searchParams.set('tab', tabId);
						url.hash = 'tab-' + tabId;
						window.history.replaceState(null, '', url.toString());
					} catch (err) {
						var cleanUrl = window.location.href.split('#')[0];
						if (cleanUrl.indexOf('tab=') !== -1) {
							cleanUrl = cleanUrl.replace(/([?&])tab=[^&]+/, '$1tab=' + tabId);
						} else {
							cleanUrl += (cleanUrl.indexOf('?') !== -1 ? '&' : '?') + 'tab=' + tabId;
						}
						window.history.replaceState(null, '', cleanUrl + '#tab-' + tabId);
					}
				}
			}
		}

		$(document).on('click', '.abfem-tab-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var tabId = $btn.attr('data-tab') || $btn.data('tab');
			if (!tabId) {
				var href = $btn.attr('href') || '';
				var match = href.match(/[?&]tab=([^&#]+)/);
				if (match && match[1]) {
					tabId = match[1];
				} else {
					tabId = href.replace(/.*#tab-/, '').replace(/.*#/, '');
				}
			}
			switchTab(tabId);
		});

		// Check hash or URL query param on page load.
		var initialTab = '';
		if (window.location.hash) {
			initialTab = window.location.hash.replace('#tab-', '').replace('#', '');
		} else if (window.location.search) {
			var searchMatch = window.location.search.match(/[?&]tab=([^&#]+)/);
			if (searchMatch && searchMatch[1]) {
				initialTab = decodeURIComponent(searchMatch[1]);
			}
		}
		if (initialTab && $('#tab-' + initialTab).length) {
			switchTab(initialTab);
		}

		// Handle browser back and forward buttons.
		window.addEventListener('popstate', function () {
			var popTab = '';
			if (window.location.hash) {
				popTab = window.location.hash.replace('#tab-', '').replace('#', '');
			} else if (window.location.search) {
				var m = window.location.search.match(/[?&]tab=([^&#]+)/);
				if (m && m[1]) {
					popTab = decodeURIComponent(m[1]);
				}
			}
			if (popTab && $('#tab-' + popTab).length) {
				switchTab(popTab);
			}
		});

		// =====================================================================
		// 2. Sensitive Fields Quick-Add Presets
		// =====================================================================
		$(document).on('click', '.abfem-preset-btn', function (e) {
			e.preventDefault();
			var presetText = $(this).data('preset');
			if (!presetText) {
				return;
			}

			var $textarea = $('#excluded_fields');
			var currentVal = $textarea.val();
			var currentLines = currentVal.split(/\r?\n/).map(function (line) {
				return line.trim();
			}).filter(Boolean);

			var newLines = presetText.split(/\r?\n/).map(function (line) {
				return line.trim();
			}).filter(Boolean);

			var addedCount = 0;
			newLines.forEach(function (pattern) {
				if (currentLines.indexOf(pattern) === -1) {
					currentLines.push(pattern);
					addedCount++;
				}
			});

			$textarea.val(currentLines.join('\n'));

			// Temporary visual feedback on button.
			var $btn = $(this);
			var originalText = $btn.text();
			$btn.text(addedCount > 0 ? '✓ Added' : 'Already Included');
			setTimeout(function () {
				$btn.text(originalText);
			}, 1500);
		});

		// =====================================================================
		// 3. Live Date Format Previewer
		// =====================================================================
		$('#date_format').on('change', function () {
			var format = $(this).val();
			var $preview = $('#abfem-date-preview');
			var now = new Date();

			if (format === 'iso') {
				var iso = now.toISOString().replace('T', ' ').substring(0, 19);
				$preview.text(iso);
			} else if (format === 'human') {
				$preview.text('Just now (e.g. 2 minutes ago)');
			} else {
				$preview.text(now.toLocaleDateString() + ' ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
			}
		});

		// =====================================================================
		// 4. AJAX: Generate Sample Submission
		// =====================================================================
		$(document).on('click', '#abfem-btn-generate-sample', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $feedback = $('#abfem-sample-feedback');

			if ($btn.prop('disabled')) {
				return;
			}

			$btn.prop('disabled', true);
			$feedback.removeClass('is-error is-success').text(abfemData.generating || 'Generating sample entry...');

			$.ajax({
				url: abfemData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'abfem_generate_sample_entry',
					nonce: abfemData.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res.success && res.data) {
						var linkHtml = '<a href="' + res.data.view_url + '" target="_blank" style="margin-left:6px;font-weight:600;text-decoration:underline;">View Submission #' + res.data.entry_id + ' &rarr;</a>';
						$feedback.addClass('is-success').html('✓ ' + res.data.message + linkHtml);
					} else {
						var msg = (res.data && res.data.message) ? res.data.message : 'Error generating sample entry.';
						$feedback.addClass('is-error').text('✕ ' + msg);
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					$feedback.addClass('is-error').text('✕ Server communication error.');
				}
			});
		});

		// =====================================================================
		// 5. AJAX: Send Test Email
		// =====================================================================
		$(document).on('click', '#abfem-btn-test-email', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $feedback = $('#abfem-test-email-feedback');
			var email = $('#notification_email').val();

			if ($btn.prop('disabled')) {
				return;
			}

			$btn.prop('disabled', true);
			$feedback.removeClass('is-error is-success').text(abfemData.sending || 'Sending test email...');

			$.ajax({
				url: abfemData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'abfem_send_test_email',
					email: email,
					nonce: abfemData.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res.success && res.data) {
						$feedback.addClass('is-success').text('✓ ' + res.data.message);
					} else {
						var msg = (res.data && res.data.message) ? res.data.message : 'Failed to send test email.';
						$feedback.addClass('is-error').text('✕ ' + msg);
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					$feedback.addClass('is-error').text('✕ Communication error.');
				}
			});
		});

		// =====================================================================
		// 6. AJAX: Clear Cache & Counters
		// =====================================================================
		$(document).on('click', '#abfem-btn-clear-cache', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $feedback = $('#abfem-cache-feedback');

			if ($btn.prop('disabled')) {
				return;
			}

			$btn.prop('disabled', true);
			$feedback.removeClass('is-error is-success').text(abfemData.clearing || 'Clearing cache...');

			$.ajax({
				url: abfemData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'abfem_clear_cache',
					nonce: abfemData.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res.success && res.data) {
						$feedback.addClass('is-success').text('✓ ' + res.data.message);
					} else {
						$feedback.addClass('is-error').text('✕ Could not clear cache.');
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					$feedback.addClass('is-error').text('✕ Communication error.');
				}
			});
		});

		// =====================================================================
		// 7. Copy System Report to Clipboard
		// =====================================================================
		$(document).on('click', '#abfem-btn-copy-report', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var report = "=== AB Form Entries Manager System Status ===\n";

			$('#abfem-system-table tr').each(function () {
				var key = $(this).find('td').first().text().trim();
				var val = $(this).find('td').last().text().trim();
				report += key + ": " + val + "\n";
			});

			report += "Generated: " + (new Date()).toUTCString() + "\n";

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(report).then(function () {
					var oldText = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes"></span> ' + (abfemData.copySuccess || 'Copied!'));
					setTimeout(function () {
						$btn.html(oldText);
					}, 2000);
				});
			} else {
				alert(report);
			}
		});

		// =====================================================================
		// 8. Guide & Form Builders Accordion Interaction
		// =====================================================================
		$(document).on('click', '.abfem-accordion-header', function () {
			var $item = $(this).closest('.abfem-accordion-item');
			var $content = $(this).next('.abfem-accordion-content');
			$item.toggleClass('abfem-accordion-open');
			$content.slideToggle(180);
		});

		// =====================================================================
		// 8.1 Interactive FAQ Accordion List
		// =====================================================================
		$(document).on('click', '.abfem-faq-trigger', function (e) {
			e.preventDefault();
			var $trigger = $(this);
			var $item = $trigger.closest('.abfem-faq-item');
			var $content = $item.find('.abfem-faq-content');
			var isOpen = $item.hasClass('abfem-faq-open');

			if (isOpen) {
				$item.removeClass('abfem-faq-open');
				$trigger.attr('aria-expanded', 'false');
				$content.slideUp(180);
			} else {
				$item.addClass('abfem-faq-open');
				$trigger.attr('aria-expanded', 'true');
				$content.slideDown(180);
			}
		});

		// =====================================================================
		// 9. Table Row Checkboxes & Bulk Action Guard
		// =====================================================================
		var $selectAllTop = $('#cb-select-all-1');
		var $selectAllBottom = $('#cb-select-all-2');
		var $rowCheckboxes = $('.abfem-submissions-table tbody input[type="checkbox"]');

		function updateSelectAllState() {
			var total = $rowCheckboxes.length;
			var checked = $rowCheckboxes.filter(':checked').length;
			var isAll = total > 0 && total === checked;

			$selectAllTop.prop('checked', isAll);
			$selectAllBottom.prop('checked', isAll);
		}

		$selectAllTop.add($selectAllBottom).on('change', function () {
			var isChecked = $(this).is(':checked');
			$rowCheckboxes.prop('checked', isChecked);
			$selectAllTop.prop('checked', isChecked);
			$selectAllBottom.prop('checked', isChecked);
		});

		$rowCheckboxes.on('change', function () {
			updateSelectAllState();
		});

		$('#abfem-bulk-form').on('submit', function (e) {
			var actionTop = $('#bulk-action-selector-top').val();
			var actionBottom = $('#bulk-action-selector-bottom').val();
			var action = actionTop !== '-1' ? actionTop : actionBottom;

			if (action === '-1') {
				return;
			}

			var checkedCount = $rowCheckboxes.filter(':checked').length;
			if (checkedCount === 0) {
				alert(abfemData.noSelected || 'Please select at least one submission.');
				e.preventDefault();
				return false;
			}

			if (action === 'delete') {
				if (!confirm(abfemData.confirmDelete || 'Are you sure you want to permanently delete the selected submission(s)? This action cannot be undone.')) {
					e.preventDefault();
					return false;
				}
			}
		});

		$('#abfem-date-preset').on('change', function () {
			if ($(this).val()) {
				$(this).closest('form').submit();
			}
		});

		// =====================================================================
		// 10. Dashboard Notice Dismissal
		// =====================================================================
		$(document).on('click', '.abfem-dashboard-notice .notice-dismiss', function () {
			var $notice = $(this).closest('.abfem-dashboard-notice');
			var latestId = $notice.data('latest-id') || 0;
			var unread = $notice.data('unread') || 0;

			if (window.abfemData && window.abfemData.ajaxUrl && window.abfemData.dismissNoticeNonce) {
				$.post(window.abfemData.ajaxUrl, {
					action: 'abfem_dismiss_dashboard_notice',
					nonce: window.abfemData.dismissNoticeNonce,
					latest_id: latestId,
					unread: unread
				});
			}
		});
	});
})(jQuery);
