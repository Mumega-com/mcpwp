<?php
/**
 * MCP Tools admin page template.
 *
 * @package MumegaMCP
 * @since   1.1.2
 *
 * @var array $disabled_categories Currently disabled category slugs.
 * @var array $category_meta       Category display metadata.
 * @var array $tool_counts         Category slug => tool count.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap spai-admin spai-tools-page">
	<h1 class="spai-header">
		<span class="spai-logo">
			<span class="dashicons dashicons-admin-tools"></span>
		</span>
		<?php esc_html_e( 'Tools', 'mumega-mcp' ); ?>
	</h1>
	<p class="description spai-page-intro">
		<?php esc_html_e( 'Enable or disable tool categories exposed to AI assistants via MCP. Disabled categories are hidden from tools/list to reduce context noise.', 'mumega-mcp' ); ?>
	</p>

	<div class="spai-tools-grid">
		<?php foreach ( $category_meta as $slug => $meta ) :
			$is_disabled = in_array( $slug, $disabled_categories, true );
			$count       = isset( $tool_counts[ $slug ] ) ? (int) $tool_counts[ $slug ] : 0;
		?>
			<div class="spai-tool-card <?php echo $is_disabled ? 'is-disabled' : 'is-enabled'; ?>" data-category="<?php echo esc_attr( $slug ); ?>">

				<div class="spai-tool-card__header">
					<div class="spai-tool-card__title">
						<span class="spai-tool-card__icon dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span>
						<h3><?php echo esc_html( $meta['name'] ); ?></h3>
					</div>
					<label class="spai-toggle">
						<input type="checkbox" class="spai-category-toggle"
							data-category="<?php echo esc_attr( $slug ); ?>"
							<?php checked( ! $is_disabled ); ?>
							aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s tools', 'mumega-mcp' ), $meta['name'] ) ); ?>" />
						<span class="spai-toggle-track"></span>
						<span class="spai-toggle-knob"></span>
					</label>
				</div>

				<p class="spai-tool-card__description">
					<?php echo esc_html( $meta['description'] ); ?>
				</p>

				<span class="spai-tool-card__count">
					<?php
						printf(
							/* translators: %d: number of tools */
							esc_html( _n( '%d tool', '%d tools', $count, 'mumega-mcp' ) ),
							absint( $count )
						);
					?>
				</span>

				<span class="spai-tool-status" hidden></span>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<script type="text/javascript">
jQuery(function($) {
	var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce = '<?php echo esc_js( wp_create_nonce( 'spai_tools_nonce' ) ); ?>';

	$(document).on('change', '.spai-category-toggle', function() {
		var $cb = $(this);
		var category = $cb.data('category');
		var enabled = $cb.is(':checked') ? '1' : '0';
		var $card = $cb.closest('.spai-tool-card');
		var $status = $card.find('.spai-tool-status');

		// Immediate visual feedback.
		if (enabled === '1') {
			$card.removeClass('is-disabled').addClass('is-enabled');
		} else {
			$card.removeClass('is-enabled').addClass('is-disabled');
		}

		$status.text('Saving...').css('color', '#64748b').prop('hidden', false);

		$.post(ajaxUrl, {
			action: 'spai_toggle_tool_category',
			nonce: nonce,
			category: category,
			enabled: enabled
		}, function(response) {
			if (response.success) {
				$status.text(enabled === '1' ? 'Enabled' : 'Disabled').css('color', '#20b86f');
				if (window.posthog) {
					posthog.capture('tool_category_toggled', { category: category, enabled: enabled === '1' });
				}
				setTimeout(function() { $status.prop('hidden', true); }, 1500);
			} else {
				$status.text(response.data.message || 'Error').css('color', '#d63638');
				// Revert toggle.
				$cb.prop('checked', enabled !== '1');
				$card.toggleClass('is-disabled', enabled === '1').toggleClass('is-enabled', enabled !== '1');
			}
		}).fail(function() {
			$status.text('Request failed').css('color', '#d63638');
			$cb.prop('checked', enabled !== '1');
			$card.toggleClass('is-disabled', enabled === '1').toggleClass('is-enabled', enabled !== '1');
		});
	});
});
</script>
