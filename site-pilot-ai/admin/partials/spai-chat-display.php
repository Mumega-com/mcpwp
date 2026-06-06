<?php
/**
 * Chat tab — AI assistant built into WP Admin.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$chat_endpoint = get_option( 'spai_chat_endpoint', 'https://mumcp-chat.mumega.workers.dev' );
$site_name     = get_bloginfo( 'name' );
$site_url      = home_url();
$plugin_ver    = SPAI_VERSION;
?>
<div class="wrap spai-admin spai-chat-page">
	<h1 class="spai-header">
		<span class="spai-logo">
			<span class="dashicons dashicons-format-chat"></span>
		</span>
		<?php esc_html_e( 'MCPWP Chat', 'mumega-mcp' ); ?>
	</h1>
	<p class="description spai-page-intro"><?php esc_html_e( 'Ask your site for help, then keep meaningful changes inside the MCPWP approval and audit loop.', 'mumega-mcp' ); ?></p>

	<div id="spai-chat-container" class="spai-chat-panel">
		<div class="spai-chat-safety">
			<span class="dashicons dashicons-shield"></span>
			<?php esc_html_e( 'Safety mode: review agent work in Control Room before applying high-impact changes.', 'mumega-mcp' ); ?>
		</div>
		<div id="spai-chat-messages" class="spai-chat-messages">
			<div class="spai-chat-msg spai-chat-assistant">
				<strong style="color:#2271b1;">MCPWP</strong>
				<p style="margin:4px 0 0;">
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

		<div class="spai-chat-composer">
			<input type="text" id="spai-chat-input" placeholder="<?php esc_attr_e( 'Ask MCPWP what to inspect or prepare...', 'mumega-mcp' ); ?>" />
			<button type="button" id="spai-chat-send" class="button button-primary">
				<?php esc_html_e( 'Send', 'mumega-mcp' ); ?>
			</button>
		</div>
	</div>
	<p class="spai-chat-note">
		<?php esc_html_e( 'Powered by Workers AI. Site operations remain auditable through WordPress and MCPWP activity logs.', 'mumega-mcp' ); ?>
	</p>
</div>

<script>
(function() {
	const messagesEl = document.getElementById('spai-chat-messages');
	const inputEl = document.getElementById('spai-chat-input');
	const sendBtn = document.getElementById('spai-chat-send');
	const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	const ajaxNonce = <?php echo wp_json_encode( wp_create_nonce( 'spai_admin_nonce' ) ); ?>;

	let history = [];

	function addMessage(role, text) {
		const div = document.createElement('div');
		div.className = 'spai-chat-msg spai-chat-' + role;
		div.style.marginBottom = '16px';

		const name = document.createElement('strong');
		name.textContent = role === 'user' ? 'You' : 'MCPWP';
		name.style.color = role === 'user' ? '#1d2327' : '#2271b1';

		const p = document.createElement('p');
		p.style.margin = '4px 0 0';
		p.style.whiteSpace = 'pre-wrap';
		p.textContent = text;

		div.appendChild(name);
		div.appendChild(p);
		messagesEl.appendChild(div);
		messagesEl.scrollTop = messagesEl.scrollHeight;
	}

	function addToolResult(toolCall, result) {
		const div = document.createElement('div');
		div.className = 'spai-chat-msg spai-chat-tool';
		div.style.marginBottom = '16px';
		div.style.padding = '10px';
		div.style.background = '#f0f6fc';
		div.style.borderRadius = '6px';
		div.style.fontFamily = 'monospace';
		div.style.fontSize = '12px';

		const label = document.createElement('strong');
		label.textContent = '⚡ ' + toolCall.tool;
		label.style.color = '#2271b1';

		const pre = document.createElement('pre');
		pre.style.margin = '8px 0 0';
		pre.style.whiteSpace = 'pre-wrap';
		pre.style.fontSize = '11px';
		pre.textContent = typeof result === 'string' ? result : JSON.stringify(result, null, 2).substring(0, 500);

		div.appendChild(label);
		div.appendChild(pre);
		messagesEl.appendChild(div);
		messagesEl.scrollTop = messagesEl.scrollHeight;
	}

	async function executeTool(toolCall) {
		// Execute via WP AJAX — server-side, no API key needed in browser
		try {
			const formData = new FormData();
			formData.append('action', 'spai_chat_execute_tool');
			formData.append('nonce', ajaxNonce);
			formData.append('tool', toolCall.tool);
			formData.append('arguments', JSON.stringify(toolCall.arguments || {}));

			const resp = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const ajaxResp = await resp.json();

			if (!ajaxResp.success) {
				return { error: ajaxResp.data?.message || 'Tool execution failed' };
			}
			return ajaxResp.data;
		} catch (err) {
			return { error: err.message };
		}
	}

	async function sendMessage() {
		const message = inputEl.value.trim();
		if (!message) return;

		inputEl.value = '';
		inputEl.disabled = true;
		sendBtn.disabled = true;

		addMessage('user', message);
		history.push({ role: 'user', content: message });

		try {
			const formData = new FormData();
			formData.append('action', 'spai_chat');
			formData.append('nonce', ajaxNonce);
			formData.append('message', message);
			formData.append('history', JSON.stringify(history.slice(-10)));

			const resp = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const ajaxResp = await resp.json();

			if (!ajaxResp.success) {
				throw new Error(ajaxResp.data?.message || 'Chat failed');
			}
			const data = ajaxResp.data;

			if (data.tool_call) {
				addMessage('assistant', 'Running: ' + data.tool_call.tool + '...');
				const result = await executeTool(data.tool_call);
				addToolResult(data.tool_call, result);

				// Tell the AI what happened
				const summary = typeof result === 'object'
					? (result.success ? 'Success' : 'Error: ' + (result.error || JSON.stringify(result)).substring(0, 200))
					: String(result).substring(0, 200);
				history.push({ role: 'assistant', content: 'Executed ' + data.tool_call.tool + '. Result: ' + summary });
			} else if (data.response) {
				addMessage('assistant', data.response);
				history.push({ role: 'assistant', content: data.response });
			} else if (data.error) {
				addMessage('assistant', '⚠️ ' + data.error);
			}
		} catch (err) {
			addMessage('assistant', '⚠️ Connection error: ' + err.message);
		}

		inputEl.disabled = false;
		sendBtn.disabled = false;
		inputEl.focus();
	}

	sendBtn.addEventListener('click', sendMessage);
	inputEl.addEventListener('keydown', function(e) {
		if (e.key === 'Enter') sendMessage();
	});
})();
</script>
