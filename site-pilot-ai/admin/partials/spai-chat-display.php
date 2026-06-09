<?php
/**
 * Chat tab — AI assistant built into WP Admin.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name   = get_bloginfo( 'name' );
$chat_model  = get_option( 'spai_chat_model', 'auto' );
$saved_hist  = get_option( 'spai_chat_history_' . get_current_user_id(), array() );
$manager     = Spai_Integration_Manager::get_instance();
$has_openai  = ! empty( $manager->get_provider_key( 'openai' ) );
$has_gemini  = ! empty( $manager->get_provider_key( 'gemini' ) );
$stream_ok   = $has_openai && ( 'openai' === $chat_model || 'auto' === $chat_model );

$model_labels = array(
	'auto'    => $has_openai ? 'GPT-4o mini' : ( $has_gemini ? 'Gemini 2.5 Flash' : 'Workers AI' ),
	'openai'  => 'GPT-4o mini',
	'gemini'  => 'Gemini 2.5 Flash',
	'workers' => 'Workers AI',
);
$model_label = $model_labels[ $chat_model ] ?? 'Workers AI';
?>
<div class="wrap spai-admin spai-chat-page">
	<h1 class="spai-header">
		<span class="spai-logo">
			<span class="dashicons dashicons-format-chat"></span>
		</span>
		<?php esc_html_e( 'MCPWP Chat', 'mumega-mcp' ); ?>
		<span class="spai-chat-model-badge"><?php echo esc_html( $model_label ); ?></span>
	</h1>
	<p class="description spai-page-intro"><?php esc_html_e( 'Ask your site for help, then keep meaningful changes inside the MCPWP approval and audit loop.', 'mumega-mcp' ); ?></p>

	<?php if ( ! $has_openai && ! $has_gemini ) : ?>
	<div class="spai-chat-safety" style="background:#fffbeb;border-bottom:1px solid #f6c90e;color:#92400e;">
		<span class="dashicons dashicons-info-outline"></span>
		<?php
		printf(
			/* translators: %s: link to integrations page */
			wp_kses(
				__( 'For the best results, <a href="%s">connect OpenAI or Gemini</a>.', 'mumega-mcp' ),
				array( 'a' => array( 'href' => array() ) )
			),
			esc_url( admin_url( 'admin.php?page=' . Spai_Integrations_Admin::PAGE_SLUG ) )
		);
		?>
	</div>
	<?php endif; ?>

	<div id="spai-chat-container" class="spai-chat-panel">
		<div class="spai-chat-toolbar">
			<div class="spai-chat-safety">
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Safety mode: review agent work in Control Room before applying high-impact changes.', 'mumega-mcp' ); ?>
			</div>
			<button type="button" id="spai-chat-clear" class="button button-link spai-chat-clear-btn">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Clear history', 'mumega-mcp' ); ?>
			</button>
		</div>
		<div id="spai-chat-messages" class="spai-chat-messages">
			<div class="spai-chat-msg spai-chat-assistant">
				<strong class="spai-chat-brand">MCPWP</strong>
				<p class="spai-chat-intro">
					<?php
					printf(
						/* translators: %s: site name */
						esc_html__( 'Hi! I can help you manage %s. Try: "Build a services page" or "List all pages" or "Add a testimonials section to the homepage."', 'mumega-mcp' ),
						esc_html( $site_name )
					);
					?>
				</p>
			</div>
		</div>

		<!-- Destructive confirmation banner (hidden by default) -->
		<div id="spai-confirm-bar" class="spai-confirm-bar" style="display:none;">
			<span class="dashicons dashicons-warning"></span>
			<span id="spai-confirm-text"></span>
			<button type="button" id="spai-confirm-yes" class="button button-primary button-small"><?php esc_html_e( 'Yes, proceed', 'mumega-mcp' ); ?></button>
			<button type="button" id="spai-confirm-no" class="button button-small"><?php esc_html_e( 'Cancel', 'mumega-mcp' ); ?></button>
		</div>

		<div class="spai-chat-composer">
			<input type="text" id="spai-chat-input" placeholder="<?php esc_attr_e( 'Try: \'Build a services page\' or \'List recent drafts\'...', 'mumega-mcp' ); ?>" />
			<button type="button" id="spai-chat-send" class="button button-primary">
				<?php esc_html_e( 'Send', 'mumega-mcp' ); ?>
			</button>
		</div>
	</div>
	<p class="spai-chat-note">
		<?php
		printf(
			/* translators: %s: model name */
			esc_html__( 'Powered by %s. Site operations remain auditable through WordPress and MCPWP activity logs.', 'mumega-mcp' ),
			esc_html( $model_label )
		);
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=site-pilot-ai-settings' ) ); ?>"><?php esc_html_e( 'Change model', 'mumega-mcp' ); ?></a>
	</p>
</div>

<style>
.spai-chat-model-badge {
	display: inline-block;
	font-size: 12px;
	font-weight: 400;
	background: #e8f0fe;
	color: #1a73e8;
	border-radius: 10px;
	padding: 2px 10px;
	margin-left: 10px;
	vertical-align: middle;
}
.spai-chat-toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 8px;
}
.spai-chat-clear-btn {
	color: #a00 !important;
	font-size: 12px;
	display: flex;
	align-items: center;
	gap: 4px;
}
.spai-chat-clear-btn .dashicons { font-size: 14px; width: 14px; height: 14px; }
.spai-confirm-bar {
	background: #fff8e1;
	border: 1px solid #f9a825;
	border-radius: 4px;
	padding: 10px 14px;
	margin-bottom: 8px;
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 13px;
}
.spai-confirm-bar .dashicons { color: #f9a825; flex-shrink: 0; }
</style>

<script>
(function() {
	const messagesEl  = document.getElementById('spai-chat-messages');
	const inputEl     = document.getElementById('spai-chat-input');
	const sendBtn     = document.getElementById('spai-chat-send');
	const clearBtn    = document.getElementById('spai-chat-clear');
	const confirmBar  = document.getElementById('spai-confirm-bar');
	const confirmText = document.getElementById('spai-confirm-text');
	const confirmYes  = document.getElementById('spai-confirm-yes');
	const confirmNo   = document.getElementById('spai-confirm-no');

	const ajaxUrl   = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	const ajaxNonce = <?php echo wp_json_encode( wp_create_nonce( 'spai_admin_nonce' ) ); ?>;
	const streamOk  = <?php echo $stream_ok ? 'true' : 'false'; ?>;

	// Restore history.
	let history = <?php echo wp_json_encode( array_values( $saved_hist ) ); ?>;
	if (history.length) {
		history.forEach(function(msg) {
			if (msg.role && msg.content) addMessage(msg.role, msg.content, true);
		});
	}

	// Pending destructive call state.
	let pendingDestructive = null;

	function addMessage(role, text, silent) {
		const div  = document.createElement('div');
		div.className = 'spai-chat-msg spai-chat-' + role;
		div.style.marginBottom = '16px';

		const name = document.createElement('strong');
		name.textContent  = role === 'user' ? 'You' : 'MCPWP';
		name.style.color  = role === 'user' ? '#1d2327' : '#2271b1';

		const p = document.createElement('p');
		p.style.margin      = '4px 0 0';
		p.style.whiteSpace  = 'pre-wrap';
		p.textContent       = text;

		div.appendChild(name);
		div.appendChild(p);
		messagesEl.appendChild(div);
		if (!silent) messagesEl.scrollTop = messagesEl.scrollHeight;
		return p; // returned for streaming updates
	}

	function addStreamingMessage() {
		const div  = document.createElement('div');
		div.className = 'spai-chat-msg spai-chat-assistant';
		div.style.marginBottom = '16px';

		const name = document.createElement('strong');
		name.textContent = 'MCPWP';
		name.style.color = '#2271b1';

		const p = document.createElement('p');
		p.style.margin     = '4px 0 0';
		p.style.whiteSpace = 'pre-wrap';
		p.textContent      = '';

		div.appendChild(name);
		div.appendChild(p);
		messagesEl.appendChild(div);
		return p;
	}

	function addToolResult(toolCall, result) {
		const div = document.createElement('div');
		div.className = 'spai-chat-msg spai-chat-tool';
		div.style.cssText = 'margin-bottom:16px;padding:10px;background:#f0f6fc;border-radius:6px;font-family:monospace;font-size:12px;';

		const label = document.createElement('strong');
		label.textContent = '⚡ ' + toolCall.tool;
		label.style.color = '#2271b1';

		const pre = document.createElement('pre');
		pre.style.cssText = 'margin:8px 0 0;white-space:pre-wrap;font-size:11px;';
		pre.textContent   = typeof result === 'string' ? result : JSON.stringify(result, null, 2).substring(0, 500);

		div.appendChild(label);
		div.appendChild(pre);
		messagesEl.appendChild(div);
		messagesEl.scrollTop = messagesEl.scrollHeight;
	}

	async function executeTool(toolCall, confirmed) {
		const formData = new FormData();
		formData.append('action', 'spai_chat_execute_tool');
		formData.append('nonce', ajaxNonce);
		formData.append('tool', toolCall.tool);
		formData.append('arguments', JSON.stringify(toolCall.arguments || {}));
		if (confirmed) formData.append('confirmed', 'true');

		try {
			const resp = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const data = await resp.json();

			if (data.success && data.data && data.data.needs_confirmation) {
				return { needs_confirmation: true };
			}
			if (!data.success) {
				return { error: data.data?.message || 'Tool execution failed' };
			}
			return data.data;
		} catch (err) {
			return { error: err.message };
		}
	}

	function showConfirmBar(tool, onConfirm, onCancel) {
		const names = {
			wp_delete_page: 'delete a page', wp_delete_post: 'delete a post',
			wp_delete_media: 'delete a media file', wp_delete_all_drafts: 'delete ALL drafts',
			wp_delete_menu: 'delete a menu', wp_delete_menu_item: 'delete a menu item',
			wp_delete_webhook: 'delete a webhook', wp_delete_content: 'delete content',
			wp_delete_custom_css: 'delete custom CSS', wp_delete_term: 'delete a term',
			wp_revoke_api_key: 'revoke an API key', wp_rollback_approval: 'rollback a change',
		};
		confirmText.textContent = 'Are you sure you want to ' + (names[tool] || tool) + '? This cannot be undone.';
		confirmBar.style.display = 'flex';

		function cleanup() {
			confirmBar.style.display = 'none';
			confirmYes.removeEventListener('click', onYes);
			confirmNo.removeEventListener('click', onNo);
		}
		function onYes() { cleanup(); onConfirm(); }
		function onNo()  { cleanup(); onCancel(); }
		confirmYes.addEventListener('click', onYes);
		confirmNo.addEventListener('click', onNo);
	}

	async function handleToolCall(toolCall) {
		addMessage('assistant', 'Running: ' + toolCall.tool + '...');
		let result = await executeTool(toolCall, false);

		if (result && result.needs_confirmation) {
			await new Promise(function(resolve) {
				showConfirmBar(
					toolCall.tool,
					async function() {
						result = await executeTool(toolCall, true);
						addToolResult(toolCall, result);
						const summary = typeof result === 'object'
							? (result.error ? 'Error: ' + result.error : 'Success')
							: String(result).substring(0, 200);
						history.push({ role: 'assistant', content: 'Executed ' + toolCall.tool + '. Result: ' + summary });
						saveHistory();
						resolve();
					},
					function() {
						addMessage('assistant', 'Cancelled: ' + toolCall.tool);
						resolve();
					}
				);
			});
		} else {
			addToolResult(toolCall, result);
			const summary = typeof result === 'object'
				? (result.error ? 'Error: ' + result.error : 'Success')
				: String(result).substring(0, 200);
			history.push({ role: 'assistant', content: 'Executed ' + toolCall.tool + '. Result: ' + summary });
			saveHistory();
		}
	}

	async function sendStreaming(message) {
		const formData = new FormData();
		formData.append('action', 'spai_chat_stream');
		formData.append('nonce', ajaxNonce);
		formData.append('message', message);
		formData.append('history', JSON.stringify(history.slice(-10)));

		const targetP = addStreamingMessage();
		let fullText  = '';

		try {
			const resp = await fetch(ajaxUrl, { method: 'POST', body: formData });
			if (!resp.ok || !resp.body) throw new Error('Stream failed');

			const reader = resp.body.getReader();
			const dec    = new TextDecoder();
			let buf      = '';

			while (true) {
				const { done, value } = await reader.read();
				if (done) break;
				buf += dec.decode(value, { stream: true });
				const lines = buf.split('\n');
				buf = lines.pop();
				for (const line of lines) {
					if (!line.startsWith('data: ')) continue;
					const payload = line.slice(6).trim();
					if (payload === '[DONE]') break;
					try {
						const chunk = JSON.parse(payload);
						if (chunk.error) { targetP.textContent = '⚠️ ' + chunk.error; return; }
						if (chunk.token) {
							fullText += chunk.token;
							targetP.textContent = fullText;
							messagesEl.scrollTop = messagesEl.scrollHeight;
						}
					} catch {}
				}
			}
		} catch (err) {
			targetP.textContent = '⚠️ Stream error: ' + err.message;
			return;
		}

		if (fullText) {
			history.push({ role: 'assistant', content: fullText });
			saveHistory();
			// Parse tool call from streamed response.
			const match = fullText.match(/\{[\s\S]*?"tool"\s*:\s*"[\s\S]*?\}/);
			if (match) {
				try {
					const tc = JSON.parse(match[0]);
					if (tc.tool) await handleToolCall(tc);
				} catch {}
			}
		}
	}

	async function sendMessage() {
		const message = inputEl.value.trim();
		if (!message) return;

		inputEl.value    = '';
		inputEl.disabled = true;
		sendBtn.disabled = true;

		addMessage('user', message);
		history.push({ role: 'user', content: message });

		try {
			if (streamOk) {
				await sendStreaming(message);
			} else {
				// Non-streaming path (Gemini, Workers AI).
				const formData = new FormData();
				formData.append('action', 'spai_chat');
				formData.append('nonce', ajaxNonce);
				formData.append('message', message);
				formData.append('history', JSON.stringify(history.slice(-10)));

				const resp    = await fetch(ajaxUrl, { method: 'POST', body: formData });
				const ajaxResp = await resp.json();

				if (!ajaxResp.success) throw new Error(ajaxResp.data?.message || 'Chat failed');

				const data = ajaxResp.data;
				if (data.tool_call) {
					await handleToolCall(data.tool_call);
				} else if (data.response) {
					addMessage('assistant', data.response);
					history.push({ role: 'assistant', content: data.response });
					saveHistory();
				} else if (data.error) {
					addMessage('assistant', '⚠️ ' + data.error);
				}
			}
		} catch (err) {
			addMessage('assistant', '⚠️ Error: ' + err.message);
		}

		inputEl.disabled = false;
		sendBtn.disabled = false;
		inputEl.focus();
	}

	function saveHistory() {
		const formData = new FormData();
		formData.append('action', 'spai_chat_save_history');
		formData.append('nonce', ajaxNonce);
		formData.append('history', JSON.stringify(history));
		fetch(ajaxUrl, { method: 'POST', body: formData });
	}

	clearBtn.addEventListener('click', function() {
		if (!confirm('Clear all chat history?')) return;
		history = [];
		while (messagesEl.firstChild) messagesEl.removeChild(messagesEl.firstChild);
		// Restore greeting.
		const div  = document.createElement('div');
		div.className = 'spai-chat-msg spai-chat-assistant';
		const name = document.createElement('strong');
		name.textContent = 'MCPWP';
		name.style.color = '#2271b1';
		const p = document.createElement('p');
		p.style.whiteSpace = 'pre-wrap';
		p.textContent = <?php echo wp_json_encode(
			sprintf(
				/* translators: %s: site name */
				__( 'Hi! I can help you manage %s. Try: "Build a services page" or "List all pages" or "Add a testimonials section to the homepage."', 'mumega-mcp' ),
				get_bloginfo( 'name' )
			)
		); ?>;
		div.appendChild(name); div.appendChild(p);
		messagesEl.appendChild(div);

		const fd = new FormData();
		fd.append('action', 'spai_chat_clear_history');
		fd.append('nonce', ajaxNonce);
		fetch(ajaxUrl, { method: 'POST', body: fd });
	});

	sendBtn.addEventListener('click', sendMessage);
	inputEl.addEventListener('keydown', function(e) {
		if ('Enter' === e.key) sendMessage();
	});
})();
</script>
