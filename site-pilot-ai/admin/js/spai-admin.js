/**
 * MCPWP Admin JavaScript
 *
 * @package MumegaMCP
 */

// PostHog analytics initialization
(function() {
	var cfg = (typeof spaiAdmin !== 'undefined' && spaiAdmin.posthogToken) ? spaiAdmin
		: (typeof spaiIntegrations !== 'undefined' && spaiIntegrations.posthogToken) ? spaiIntegrations
		: null;
	if (!cfg) return;
	!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
	posthog.init(cfg.posthogToken, { api_host: cfg.posthogHost, defaults: '2026-01-30' });
})();

(function($) {
	'use strict';

	/**
	 * Copy to clipboard functionality
	 */
	function initCopyButtons() {
		// API key copy buttons
		$('.spai-copy-btn').on('click', function() {
			var btn = $(this);
			var text = btn.data('copy') || $('#spai-api-key').val();

			copyToClipboard(text).then(function() {
				showCopyFeedback(btn);
				if (window.posthog) {
					posthog.capture('api_key_copied', { source: 'setup_page' });
				}
			}).catch(function() {
				alert(spaiAdmin.strings.copyFailed);
			});
		});

		// Code block copy buttons
		$('.spai-copy-code-btn').on('click', function() {
			var btn = $(this);
			var targetId = btn.data('target');
			var text = $('#' + targetId).text();

			copyToClipboard(text).then(function() {
				showCopyFeedback(btn);
			}).catch(function() {
				alert(spaiAdmin.strings.copyFailed);
			});
		});
	}

	/**
	 * Show copy feedback on button
	 */
	function showCopyFeedback(btn) {
		var originalHtml = btn.html();
		btn.html('<span class="dashicons dashicons-yes"></span> ' + spaiAdmin.strings.copied);
		btn.prop('disabled', true);
		setTimeout(function() {
			btn.html(originalHtml);
			btn.prop('disabled', false);
		}, 2000);
	}

	/**
	 * Copy text to clipboard
	 */
	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}

		return new Promise(function(resolve, reject) {
			var textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.style.position = 'fixed';
			textarea.style.opacity = '0';
			document.body.appendChild(textarea);
			textarea.select();

			try {
				var successful = document.execCommand('copy');
				document.body.removeChild(textarea);
				if (successful) {
					resolve();
				} else {
					reject();
				}
			} catch (err) {
				document.body.removeChild(textarea);
				reject(err);
			}
		});
	}

	/**
	 * Confirm regenerate key
	 */
	function initRegenerateConfirm() {
		$('.spai-regenerate-btn').on('click', function(e) {
			if (!confirm(spaiAdmin.strings.confirm)) {
				e.preventDefault();
			}
		});
	}

	/**
	 * Test connection
	 */
	function initTestConnection() {
		$('#spai-test-btn').on('click', function() {
			var btn = $(this);
			var result = $('#spai-test-result');
			var success = result.find('.spai-test-success');
			var error = result.find('.spai-test-error');
			var details = result.find('.spai-test-details');

			// Show loading state
			btn.prop('disabled', true);
			btn.html('<span class="dashicons dashicons-update spai-spin"></span> ' + spaiAdmin.strings.testing);
			result.show();
			success.hide();
			error.hide();
			details.hide();

			$.ajax({
				url: spaiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'spai_test_connection',
					nonce: spaiAdmin.nonce
				},
				success: function(response) {
					btn.prop('disabled', false);
					btn.html('<span class="dashicons dashicons-yes-alt"></span> Test Connection');

					if (response.success) {
						success.show().find('.spai-test-message').text(
							spaiAdmin.strings.connected + ' ' + response.data.site_name
						);
						details.show().html(
							'<table>' +
							'<tr><td>Site:</td><td>' + escHtml(response.data.site_name) + '</td></tr>' +
							'<tr><td>WordPress:</td><td>' + escHtml(response.data.wp_version) + '</td></tr>' +
							'<tr><td>PHP:</td><td>' + escHtml(response.data.php_version) + '</td></tr>' +
							'<tr><td>Plugin:</td><td>v' + escHtml(response.data.plugin_version) + '</td></tr>' +
							'<tr><td>REST URL:</td><td>' + escHtml(response.data.rest_url) + '</td></tr>' +
							'</table>'
						);
						result.css('background', '#d4edda');
						if (window.posthog) {
							posthog.capture('connection_tested', { result: 'success' });
						}
					} else {
						error.show().find('.spai-test-message').text(
							spaiAdmin.strings.testFailed + ': ' + (response.data ? response.data.message : 'Unknown error')
						);
						result.css('background', '#f8d7da');
						if (window.posthog) {
							posthog.capture('connection_tested', { result: 'failure' });
						}
					}
				},
				error: function() {
					btn.prop('disabled', false);
					btn.html('<span class="dashicons dashicons-yes-alt"></span> Test Connection');
					error.show().find('.spai-test-message').text(spaiAdmin.strings.testFailed);
					result.css('background', '#f8d7da');
					if (window.posthog) {
						posthog.capture('connection_tested', { result: 'failure' });
					}
				}
			});
		});
	}

	/**
	 * Dismiss welcome banner
	 */
	function initDismissWelcome() {
		$('#spai-dismiss-welcome').on('click', function() {
			var btn = $(this);
			btn.prop('disabled', true);

			if (window.posthog) {
				posthog.capture('welcome_banner_dismissed');
			}

			$.ajax({
				url: spaiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'spai_dismiss_welcome',
					nonce: spaiAdmin.nonce
				},
				success: function() {
					$('#spai-welcome').slideUp(300);
				},
				error: function() {
					$('#spai-welcome').slideUp(300);
				}
			});
		});
	}

	/**
	 * Track upgrade link clicks
	 */
	function initUpgradeTracking() {
		$(document).on('click', 'a[href*="mcpwp.net/pricing"], a[href*="mcpwp.net/account"]', function() {
			if (window.posthog) {
				posthog.capture('upgrade_link_clicked', { href: $(this).attr('href') });
			}
		});
	}

	/**
	 * Escape HTML for safe insertion
	 */
	function escHtml(str) {
		if (!str) return '';
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	/**
	 * Integrations page — save, remove, test, update-key toggle
	 */
	function initIntegrations() {
		if (!$('.spai-integrations-page').length || typeof spaiIntegrations === 'undefined') return;

		var ajaxUrl = spaiIntegrations.ajaxUrl;
		var nonce   = spaiIntegrations.nonce;
		var strings = spaiIntegrations.strings;

		// Toggle update-key form
		$(document).on('click', '.spai-update-key-toggle', function() {
			var form = $(this).closest('.spai-integration-key-form');
			form.find('.spai-multi-field-inputs, .spai-integration-key-input').toggleClass('is-hidden');
			form.find('.spai-save-integration').toggleClass('is-hidden');
		});

		// Save integration
		$(document).on('click', '.spai-save-integration', function() {
			var btn = $(this);
			var form = btn.closest('.spai-integration-key-form');
			var provider = form.data('provider');
			var isMulti = form.data('multi-field') === 1 || form.data('multi-field') === '1';
			var status = form.find('.spai-integration-status');
			var data = { action: 'spai_save_integration_key', nonce: nonce, provider: provider };

			if (isMulti) {
				data.config = {};
				form.find('.spai-config-field').each(function() {
					data.config[$(this).data('field')] = $(this).val();
				});
			} else {
				data.key = form.find('.spai-integration-key-input').val();
			}

			btn.prop('disabled', true).text(strings.saving);
			$.post(ajaxUrl, data, function(response) {
				btn.prop('disabled', false).text(strings.saved || 'Save');
				status.text(response.success ? strings.saved : (response.data && response.data.message ? response.data.message : strings.saveFailed));
				if (response.success) setTimeout(function() { location.reload(); }, 800);
			});
		});

		// Remove integration
		$(document).on('click', '.spai-remove-integration', function() {
			if (!confirm(strings.confirmRemove)) return;
			var btn = $(this);
			var provider = btn.data('provider');
			btn.prop('disabled', true).text(strings.removing);
			$.post(ajaxUrl, { action: 'spai_remove_integration_key', nonce: nonce, provider: provider }, function(response) {
				if (response.success) location.reload();
				else btn.prop('disabled', false).text('Remove');
			});
		});

		// Test integration
		$(document).on('click', '.spai-test-integration', function() {
			var btn = $(this);
			var provider = btn.data('provider');
			var status = btn.closest('.spai-integration-key-form').find('.spai-integration-status');
			btn.prop('disabled', true).text(strings.testing);
			$.post(ajaxUrl, { action: 'spai_test_integration', nonce: nonce, provider: provider }, function(response) {
				btn.prop('disabled', false).text('Test Connection');
				status.text(response.success ? strings.connected : (response.data && response.data.message ? response.data.message : strings.testFailed));
			});
		});
	}

	/**
	 * Initialize
	 */
	$(document).ready(function() {
		initCopyButtons();
		initRegenerateConfirm();
		initTestConnection();
		initDismissWelcome();
		initUpgradeTracking();
		initIntegrations();
	});

})(jQuery);
