/**
 * MCPWP Admin JavaScript
 *
 * All admin-page JS lives here — no inline <script> blocks in partials.
 * PHP data is passed via wp_localize_script('spai-admin', 'spaiAdmin', {...}).
 *
 * @package MumegaMCP
 */

// PostHog analytics — single canonical init, reads from spaiAdmin.
(function() {
	var cfg = (typeof spaiAdmin !== 'undefined' && spaiAdmin.posthogToken) ? spaiAdmin : null;
	if (!cfg || !cfg.posthogToken) return;
	!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
	posthog.init(cfg.posthogToken, { api_host: cfg.posthogHost, defaults: '2026-01-30' });
})();

(function($) {
	'use strict';

	// Guard: spaiAdmin must be defined (localized by PHP on every MCPWP page).
	if (typeof spaiAdmin === 'undefined') return;

	var ajaxUrl = spaiAdmin.ajaxUrl;
	var nonce   = spaiAdmin.nonce;
	var strings = spaiAdmin.strings;

	// ── Clipboard helpers ────────────────────────────────────────────────────

	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function(resolve, reject) {
			var textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.style.position = 'fixed';
			textarea.style.opacity  = '0';
			document.body.appendChild(textarea);
			textarea.select();
			try {
				var ok = document.execCommand('copy');
				document.body.removeChild(textarea);
				ok ? resolve() : reject();
			} catch (err) {
				document.body.removeChild(textarea);
				reject(err);
			}
		});
	}

	function showCopyFeedback(btn) {
		var originalHtml = btn.html();
		btn.html('<span class="dashicons dashicons-yes"></span> ' + strings.copied);
		btn.prop('disabled', true);
		setTimeout(function() {
			btn.html(originalHtml);
			btn.prop('disabled', false);
		}, 2000);
	}

	function escHtml(str) {
		if (!str) return '';
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	// ── Copy buttons ─────────────────────────────────────────────────────────

	function initCopyButtons() {
		$('.spai-copy-btn').on('click', function() {
			var btn  = $(this);
			var text = btn.data('copy') || $('#spai-api-key').val();
			copyToClipboard(text).then(function() {
				showCopyFeedback(btn);
				if (window.posthog) {
					posthog.capture('api_key_copied', { source: 'setup_page' });
				}
			}).catch(function() {
				alert(strings.copyFailed);
			});
		});

		$('.spai-copy-code-btn').on('click', function() {
			var btn      = $(this);
			var targetId = btn.data('target');
			var text     = $('#' + targetId).text();
			copyToClipboard(text).then(function() {
				showCopyFeedback(btn);
			}).catch(function() {
				alert(strings.copyFailed);
			});
		});
	}

	// ── Regenerate confirm ───────────────────────────────────────────────────

	function initRegenerateConfirm() {
		$('.spai-regenerate-btn').on('click', function(e) {
			if (!confirm(strings.confirm)) {
				e.preventDefault();
			}
		});
	}

	// ── Test connection ──────────────────────────────────────────────────────

	function initTestConnection() {
		$('#spai-test-btn').on('click', function() {
			var btn     = $(this);
			var result  = $('#spai-test-result');
			var success = result.find('.spai-test-success');
			var error   = result.find('.spai-test-error');
			var details = result.find('.spai-test-details');

			btn.prop('disabled', true);
			btn.html('<span class="dashicons dashicons-update spai-spin"></span> ' + strings.testing);
			result.show();
			success.hide();
			error.hide();
			details.hide();
			result.removeClass('is-success is-error');

			$.ajax({
				url:  ajaxUrl,
				type: 'POST',
				data: { action: 'spai_test_connection', nonce: nonce },
				success: function(response) {
					btn.prop('disabled', false);
					btn.html('<span class="dashicons dashicons-yes-alt"></span> Test Connection');
					if (response.success) {
						success.show().find('.spai-test-message').text(
							strings.connected + ' ' + response.data.site_name
						);
						details.show().html(
							'<table>' +
							'<tr><td>Site:</td><td>'    + escHtml(response.data.site_name)     + '</td></tr>' +
							'<tr><td>WordPress:</td><td>' + escHtml(response.data.wp_version)  + '</td></tr>' +
							'<tr><td>PHP:</td><td>'      + escHtml(response.data.php_version)  + '</td></tr>' +
							'<tr><td>Plugin:</td><td>v'  + escHtml(response.data.plugin_version) + '</td></tr>' +
							'<tr><td>REST URL:</td><td>' + escHtml(response.data.rest_url)     + '</td></tr>' +
							'</table>'
						);
						result.addClass('is-success');
						if (window.posthog) {
							posthog.capture('connection_tested', { result: 'success' });
						}
					} else {
						error.show().find('.spai-test-message').text(
							strings.testFailed + ': ' + (response.data ? response.data.message : 'Unknown error')
						);
						result.addClass('is-error');
						if (window.posthog) {
							posthog.capture('connection_tested', { result: 'failure' });
						}
					}
				},
				error: function() {
					btn.prop('disabled', false);
					btn.html('<span class="dashicons dashicons-yes-alt"></span> Test Connection');
					error.show().find('.spai-test-message').text(strings.testFailed);
					result.addClass('is-error');
					if (window.posthog) {
						posthog.capture('connection_tested', { result: 'failure' });
					}
				}
			});
		});
	}

	// ── Dismiss welcome banner ────────────────────────────────────────────────

	function initDismissWelcome() {
		$('#spai-dismiss-welcome').on('click', function() {
			var btn = $(this);
			btn.prop('disabled', true);
			if (window.posthog) {
				posthog.capture('welcome_banner_dismissed');
			}
			$.ajax({
				url:  ajaxUrl,
				type: 'POST',
				data: { action: 'spai_dismiss_welcome', nonce: nonce },
				success: function() {
					$('#spai-welcome').slideUp(300);
				},
				error: function() {
					$('#spai-welcome').slideUp(300);
				}
			});
		});
	}

	// ── Upgrade link tracking ─────────────────────────────────────────────────

	function initUpgradeTracking() {
		$(document).on('click', 'a[href*="mcpwp.net/pricing"], a[href*="mcpwp.net/account"]', function() {
			if (window.posthog) {
				posthog.capture('upgrade_link_clicked', { href: $(this).attr('href') });
			}
		});
	}

	// ── Setup page: role-based key UI ─────────────────────────────────────────

	function initRoleKeyUI() {
		var roleSelect = document.getElementById('spai_scoped_key_role');
		if (!roleSelect) return;

		var customDiv  = document.getElementById('spai-custom-categories');
		var previewDiv = document.getElementById('spai-role-preview');
		var previewCat = document.getElementById('spai-role-preview-categories');
		var checkboxes = document.querySelectorAll('.spai-category-checkbox');
		var catLabels  = spaiAdmin.catLabels || {};

		function updateRoleUI() {
			var sel  = roleSelect.options[roleSelect.selectedIndex];
			var role = sel.value;
			var cats = JSON.parse(sel.getAttribute('data-categories') || '[]');

			if (role === 'custom') {
				if (customDiv) customDiv.style.display = 'block';
				if (previewDiv) previewDiv.style.display = 'none';
			} else if (role === 'admin') {
				if (customDiv) customDiv.style.display = 'none';
				if (previewDiv) previewDiv.style.display = 'block';
				if (previewCat) previewCat.textContent = (strings && strings.allCategoriesLabel) ? strings.allCategoriesLabel : '';
			} else {
				if (customDiv) customDiv.style.display = 'none';
				if (previewDiv) previewDiv.style.display = 'block';
				if (previewCat) {
					var labels = cats.map(function(c) { return catLabels[c] || c; });
					previewCat.textContent = labels.join(', ');
				}
			}
			if (role !== 'custom') {
				checkboxes.forEach(function(cb) {
					cb.checked = cats.indexOf(cb.value) !== -1;
				});
			}
		}

		roleSelect.addEventListener('change', updateRoleUI);
		updateRoleUI();

		// Track scoped key creation.
		var createBtn = document.querySelector('form [name="spai_create_scoped_key"]');
		if (createBtn) {
			createBtn.closest('form').addEventListener('submit', function() {
				if (window.posthog) {
					posthog.capture('scoped_key_created', {
						role: roleSelect ? roleSelect.value : 'unknown'
					});
				}
			});
		}

		// Track scoped key revocations.
		document.querySelectorAll('[name="spai_revoke_scoped_key"]').forEach(function(btn) {
			btn.closest('form').addEventListener('submit', function() {
				if (window.posthog) {
					posthog.capture('scoped_key_revoked');
				}
			});
		});
	}

	// ── Setup page: connect-your-AI tabs ─────────────────────────────────────

	function initConnectTabs() {
		var tabNav = document.getElementById('spai-connect-tabs');
		if (!tabNav) return;

		var tabs   = tabNav.querySelectorAll('.spai-inner-tab');
		var panels = document.querySelectorAll('.spai-inner-tab-content');

		tabs.forEach(function(tab) {
			tab.addEventListener('click', function(e) {
				e.preventDefault();
				var target = this.getAttribute('data-tab');

				tabs.forEach(function(t) { t.classList.remove('nav-tab-active'); });
				this.classList.add('nav-tab-active');

				panels.forEach(function(p) {
					p.style.display = p.id === 'spai-tab-' + target ? '' : 'none';
				});
				if (window.posthog) {
					posthog.capture('ai_client_tab_switched', { client: target });
				}
			});
		});
	}

	// ── Tools page: category toggle ──────────────────────────────────────────

	function initToolsPage() {
		if (!$('.spai-tools-page').length) return;

		var toolsNonce = spaiAdmin.toolsNonce;

		$(document).on('change', '.spai-category-toggle', function() {
			var $cb       = $(this);
			var category  = $cb.data('category');
			var enabled   = $cb.is(':checked') ? '1' : '0';
			var $card     = $cb.closest('.spai-tool-card');
			var $status   = $card.find('.spai-tool-status');

			if (enabled === '1') {
				$card.removeClass('is-disabled').addClass('is-enabled');
			} else {
				$card.removeClass('is-enabled').addClass('is-disabled');
			}

			$status.text('Saving...').css('color', '#64748b').prop('hidden', false);

			$.post(ajaxUrl, {
				action:   'spai_toggle_tool_category',
				nonce:    toolsNonce,
				category: category,
				enabled:  enabled
			}, function(response) {
				if (response.success) {
					$status.text(enabled === '1' ? 'Enabled' : 'Disabled').css('color', '#20b86f');
					if (window.posthog) {
						posthog.capture('tool_category_toggled', { category: category, enabled: enabled === '1' });
					}
					setTimeout(function() { $status.prop('hidden', true); }, 1500);
				} else {
					$status.text(response.data.message || 'Error').css('color', '#d63638');
					$cb.prop('checked', enabled !== '1');
					$card.toggleClass('is-disabled', enabled === '1').toggleClass('is-enabled', enabled !== '1');
				}
			}).fail(function() {
				$status.text('Request failed').css('color', '#d63638');
				$cb.prop('checked', enabled !== '1');
				$card.toggleClass('is-disabled', enabled === '1').toggleClass('is-enabled', enabled !== '1');
			});
		});
	}

	// ── Integrations page: save/remove/test ──────────────────────────────────

	function initIntegrations() {
		if (!$('.spai-integrations-page').length) return;

		var intNonce = spaiAdmin.integrationsNonce;

		function setStatusText(status, text, isSuccess) {
			status.text(text)
				.removeClass('spai-status-active spai-status-inactive')
				.addClass(isSuccess ? 'spai-status-active' : 'spai-status-inactive');
		}

		$(document).on('click', '.spai-update-key-toggle', function() {
			var form = $(this).closest('.spai-integration-key-form');
			form.find('.spai-multi-field-inputs, .spai-integration-key-input').toggleClass('is-hidden');
			form.find('.spai-save-integration').toggleClass('is-hidden');
		});

		$(document).on('click', '.spai-save-integration', function() {
			var btn      = $(this);
			var form     = btn.closest('.spai-integration-key-form');
			var provider = form.data('provider');
			var isMulti  = form.data('multi-field') === 1 || form.data('multi-field') === '1';
			var status   = form.find('.spai-integration-status');
			var data     = { action: 'spai_save_integration_key', nonce: intNonce, provider: provider };

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
				var msg = response.success
					? strings.saved
					: (response.data && response.data.message ? response.data.message : strings.saveFailed);
				setStatusText(status, msg, response.success);
				if (response.success) setTimeout(function() { location.reload(); }, 800);
			});
		});

		$(document).on('click', '.spai-remove-integration', function() {
			if (!confirm(strings.confirmRemove)) return;
			var btn      = $(this);
			var provider = btn.data('provider');
			btn.prop('disabled', true).text(strings.removing);
			$.post(ajaxUrl, { action: 'spai_remove_integration_key', nonce: intNonce, provider: provider }, function(response) {
				if (response.success) {
					location.reload();
				} else {
					btn.prop('disabled', false).text('Remove');
				}
			});
		});

		$(document).on('click', '.spai-test-integration', function() {
			var btn      = $(this);
			var provider = btn.data('provider');
			var status   = btn.closest('.spai-integration-key-form').find('.spai-integration-status');
			btn.prop('disabled', true).text(strings.testing);
			$.post(ajaxUrl, { action: 'spai_test_integration', nonce: intNonce, provider: provider }, function(response) {
				btn.prop('disabled', false).text('Test Connection');
				var msg = response.success
					? (response.data && response.data.message ? response.data.message : strings.connected)
					: (response.data && response.data.message ? response.data.message : strings.testFailed);
				setStatusText(status, msg, response.success);
			});
		});
	}

	// ── Chat page ─────────────────────────────────────────────────────────────

	function initChat() {
		var messagesEl = document.getElementById('spai-chat-messages');
		if (!messagesEl) return;

		var inputEl    = document.getElementById('spai-chat-input');
		var sendBtn    = document.getElementById('spai-chat-send');
		var clearBtn   = document.getElementById('spai-chat-clear');
		var confirmBar = document.getElementById('spai-confirm-bar');
		var confirmTxt = document.getElementById('spai-confirm-text');
		var confirmYes = document.getElementById('spai-confirm-yes');
		var confirmNo  = document.getElementById('spai-confirm-no');

		if (!inputEl || !sendBtn) return;

		var chatAjaxNonce = spaiAdmin.nonce;
		var streamOk      = !!spaiAdmin.streamOk;
		var chatGreeting  = spaiAdmin.chatGreeting || '';

		// Restore history from the hidden data element if present.
		var historyEl = document.getElementById('spai-chat-history-data');
		var history   = [];
		if (historyEl) {
			try { history = JSON.parse(historyEl.textContent || '[]'); } catch (e) { history = []; }
		}
		if (history.length) {
			history.forEach(function(msg) {
				if (msg.role && msg.content) addMessage(msg.role, msg.content, true);
			});
		}

		var pendingDestructive = null;

		function addMessage(role, text, silent) {
			var div  = document.createElement('div');
			div.className = 'spai-chat-msg spai-chat-' + role;
			div.style.marginBottom = '16px';

			var name = document.createElement('strong');
			name.textContent = role === 'user' ? 'You' : 'MCPWP';
			name.style.color = role === 'user' ? '#1d2327' : '#2271b1';

			var p = document.createElement('p');
			p.style.margin     = '4px 0 0';
			p.style.whiteSpace = 'pre-wrap';
			p.textContent      = text;

			div.appendChild(name);
			div.appendChild(p);
			messagesEl.appendChild(div);
			if (!silent) messagesEl.scrollTop = messagesEl.scrollHeight;
			return p;
		}

		function addStreamingMessage() {
			var div  = document.createElement('div');
			div.className = 'spai-chat-msg spai-chat-assistant';
			div.style.marginBottom = '16px';

			var name = document.createElement('strong');
			name.textContent = 'MCPWP';
			name.style.color = '#2271b1';

			var p = document.createElement('p');
			p.style.margin     = '4px 0 0';
			p.style.whiteSpace = 'pre-wrap';
			p.textContent      = '';

			div.appendChild(name);
			div.appendChild(p);
			messagesEl.appendChild(div);
			return p;
		}

		function addToolResult(toolCall, result) {
			var div = document.createElement('div');
			div.className = 'spai-chat-msg spai-chat-tool';
			div.style.cssText = 'margin-bottom:16px;padding:10px;background:#f0f6fc;border-radius:6px;font-family:monospace;font-size:12px;';

			var label = document.createElement('strong');
			label.textContent = '⚡ ' + toolCall.tool;
			label.style.color = '#2271b1';

			var pre = document.createElement('pre');
			pre.style.cssText = 'margin:8px 0 0;white-space:pre-wrap;font-size:11px;';
			pre.textContent   = typeof result === 'string' ? result : JSON.stringify(result, null, 2).substring(0, 500);

			div.appendChild(label);
			div.appendChild(pre);
			messagesEl.appendChild(div);
			messagesEl.scrollTop = messagesEl.scrollHeight;
		}

		function executeTool(toolCall, confirmed) {
			var formData = new FormData();
			formData.append('action',    'spai_chat_execute_tool');
			formData.append('nonce',     chatAjaxNonce);
			formData.append('tool',      toolCall.tool);
			formData.append('arguments', JSON.stringify(toolCall.arguments || {}));
			if (confirmed) formData.append('confirmed', 'true');

			return fetch(ajaxUrl, { method: 'POST', body: formData })
				.then(function(resp) { return resp.json(); })
				.then(function(data) {
					if (data.success && data.data && data.data.needs_confirmation) {
						return { needs_confirmation: true };
					}
					if (!data.success) {
						return { error: (data.data && data.data.message) ? data.data.message : 'Tool execution failed' };
					}
					return data.data;
				})
				.catch(function(err) { return { error: err.message }; });
		}

		function showConfirmBar(tool, onConfirm, onCancel) {
			var names = {
				wp_delete_page: 'delete a page', wp_delete_post: 'delete a post',
				wp_delete_media: 'delete a media file', wp_delete_all_drafts: 'delete ALL drafts',
				wp_delete_menu: 'delete a menu', wp_delete_menu_item: 'delete a menu item',
				wp_delete_webhook: 'delete a webhook', wp_delete_content: 'delete content',
				wp_delete_custom_css: 'delete custom CSS', wp_delete_term: 'delete a term',
				wp_revoke_api_key: 'revoke an API key', wp_rollback_approval: 'rollback a change'
			};
			if (confirmTxt) {
				confirmTxt.textContent = 'Are you sure you want to ' + (names[tool] || tool) + '? This cannot be undone.';
			}
			if (confirmBar) confirmBar.style.display = 'flex';

			function cleanup() {
				if (confirmBar) confirmBar.style.display = 'none';
				if (confirmYes) confirmYes.removeEventListener('click', onYes);
				if (confirmNo)  confirmNo.removeEventListener('click', onNo);
			}
			function onYes() { cleanup(); onConfirm(); }
			function onNo()  { cleanup(); onCancel(); }
			if (confirmYes) confirmYes.addEventListener('click', onYes);
			if (confirmNo)  confirmNo.addEventListener('click', onNo);
		}

		function handleToolCall(toolCall) {
			addMessage('assistant', 'Running: ' + toolCall.tool + '...');
			return executeTool(toolCall, false).then(function(result) {
				if (result && result.needs_confirmation) {
					return new Promise(function(resolve) {
						showConfirmBar(
							toolCall.tool,
							function() {
								executeTool(toolCall, true).then(function(r) {
									addToolResult(toolCall, r);
									var summary = typeof r === 'object'
										? (r.error ? 'Error: ' + r.error : 'Success')
										: String(r).substring(0, 200);
									history.push({ role: 'assistant', content: 'Executed ' + toolCall.tool + '. Result: ' + summary });
									saveHistory();
									resolve();
								});
							},
							function() {
								addMessage('assistant', 'Cancelled: ' + toolCall.tool);
								resolve();
							}
						);
					});
				}
				addToolResult(toolCall, result);
				var summary = typeof result === 'object'
					? (result.error ? 'Error: ' + result.error : 'Success')
					: String(result).substring(0, 200);
				history.push({ role: 'assistant', content: 'Executed ' + toolCall.tool + '. Result: ' + summary });
				saveHistory();
			});
		}

		function sendStreaming(message) {
			var formData = new FormData();
			formData.append('action',  'spai_chat_stream');
			formData.append('nonce',   chatAjaxNonce);
			formData.append('message', message);
			formData.append('history', JSON.stringify(history.slice(-10)));

			var targetP  = addStreamingMessage();
			var fullText = '';

			return fetch(ajaxUrl, { method: 'POST', body: formData })
				.then(function(resp) {
					if (!resp.ok || !resp.body) throw new Error('Stream failed');
					var reader = resp.body.getReader();
					var dec    = new TextDecoder();
					var buf    = '';

					function pump() {
						return reader.read().then(function(ref) {
							if (ref.done) return;
							buf += dec.decode(ref.value, { stream: true });
							var lines = buf.split('\n');
							buf = lines.pop();
							lines.forEach(function(line) {
								if (!line.startsWith('data: ')) return;
								var payload = line.slice(6).trim();
								if (payload === '[DONE]') return;
								try {
									var chunk = JSON.parse(payload);
									if (chunk.error) { targetP.textContent = '⚠️ ' + chunk.error; return; }
									if (chunk.token) {
										fullText += chunk.token;
										targetP.textContent = fullText;
										messagesEl.scrollTop = messagesEl.scrollHeight;
									}
								} catch (e) { /* ignore parse errors */ }
							});
							return pump();
						});
					}
					return pump();
				})
				.catch(function(err) {
					targetP.textContent = '⚠️ Stream error: ' + err.message;
				})
				.then(function() {
					if (!fullText) return;
					history.push({ role: 'assistant', content: fullText });
					saveHistory();
					var match = fullText.match(/\{[\s\S]*?"tool"\s*:\s*"[\s\S]*?\}/);
					if (match) {
						try {
							var tc = JSON.parse(match[0]);
							if (tc.tool) return handleToolCall(tc);
						} catch (e) { /* ignore */ }
					}
				});
		}

		function sendMessage() {
			var message = inputEl.value.trim();
			if (!message) return;

			inputEl.value    = '';
			inputEl.disabled = true;
			sendBtn.disabled = true;

			addMessage('user', message);
			history.push({ role: 'user', content: message });

			var done;
			if (streamOk) {
				done = sendStreaming(message);
			} else {
				var formData = new FormData();
				formData.append('action',  'spai_chat');
				formData.append('nonce',   chatAjaxNonce);
				formData.append('message', message);
				formData.append('history', JSON.stringify(history.slice(-10)));

				done = fetch(ajaxUrl, { method: 'POST', body: formData })
					.then(function(resp) { return resp.json(); })
					.then(function(ajaxResp) {
						if (!ajaxResp.success) {
							throw new Error((ajaxResp.data && ajaxResp.data.message) ? ajaxResp.data.message : 'Chat failed');
						}
						var data = ajaxResp.data;
						if (data.tool_call) {
							return handleToolCall(data.tool_call);
						} else if (data.response) {
							addMessage('assistant', data.response);
							history.push({ role: 'assistant', content: data.response });
							saveHistory();
						} else if (data.error) {
							addMessage('assistant', '⚠️ ' + data.error);
						}
					});
			}

			Promise.resolve(done)
				.catch(function(err) {
					addMessage('assistant', '⚠️ Error: ' + err.message);
				})
				.then(function() {
					inputEl.disabled = false;
					sendBtn.disabled = false;
					inputEl.focus();
				});
		}

		function saveHistory() {
			var fd = new FormData();
			fd.append('action',  'spai_chat_save_history');
			fd.append('nonce',   chatAjaxNonce);
			fd.append('history', JSON.stringify(history));
			fetch(ajaxUrl, { method: 'POST', body: fd });
		}

		if (clearBtn) {
			clearBtn.addEventListener('click', function() {
				if (!confirm(strings.clearHistory || 'Clear all chat history?')) return;
				history = [];
				while (messagesEl.firstChild) messagesEl.removeChild(messagesEl.firstChild);
				var div  = document.createElement('div');
				div.className = 'spai-chat-msg spai-chat-assistant';
				var name = document.createElement('strong');
				name.textContent = 'MCPWP';
				name.style.color = '#2271b1';
				var p = document.createElement('p');
				p.style.whiteSpace = 'pre-wrap';
				p.textContent = chatGreeting;
				div.appendChild(name);
				div.appendChild(p);
				messagesEl.appendChild(div);

				var fd = new FormData();
				fd.append('action', 'spai_chat_clear_history');
				fd.append('nonce',  chatAjaxNonce);
				fetch(ajaxUrl, { method: 'POST', body: fd });
			});
		}

		sendBtn.addEventListener('click', sendMessage);
		inputEl.addEventListener('keydown', function(e) {
			if ('Enter' === e.key) sendMessage();
		});
	}

	// ── Initialize ────────────────────────────────────────────────────────────

	$(document).ready(function() {
		initCopyButtons();
		initRegenerateConfirm();
		initTestConnection();
		initDismissWelcome();
		initUpgradeTracking();
		initIntegrations();
		initToolsPage();
		initRoleKeyUI();
		initConnectTabs();
		initChat();
	});

})(jQuery);
