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

<!-- Chat history passed as JSON data element for spai-admin.js (no inline logic). -->
<script type="application/json" id="spai-chat-history-data"><?php echo wp_json_encode( array_values( $saved_hist ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
