<?php
/**
 * Human control room template.
 *
 * Variables available from render_control_room_page():
 *   $control_room - array summarized approval, SEO, and activity data.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$control_room    = is_array( $control_room ) ? $control_room : array();
$approval_counts = isset( $control_room['approval_counts'] ) && is_array( $control_room['approval_counts'] ) ? $control_room['approval_counts'] : array();
$seo_summary     = isset( $control_room['seo_summary'] ) && is_array( $control_room['seo_summary'] ) ? $control_room['seo_summary'] : array();
$pending_items   = isset( $control_room['pending_approvals'] ) && is_array( $control_room['pending_approvals'] ) ? $control_room['pending_approvals'] : array();
$rollback_items  = isset( $control_room['rollback_ready'] ) && is_array( $control_room['rollback_ready'] ) ? $control_room['rollback_ready'] : array();
$seo_issues      = isset( $control_room['open_seo_issues'] ) && is_array( $control_room['open_seo_issues'] ) ? $control_room['open_seo_issues'] : array();
$activity_rows   = isset( $control_room['recent_activity'] ) && is_array( $control_room['recent_activity'] ) ? $control_room['recent_activity'] : array();
$recommendations = isset( $control_room['recommendations'] ) && is_array( $control_room['recommendations'] ) ? $control_room['recommendations'] : array();
?>

<div class="wrap spai-admin spai-control-room">
	<h1 class="spai-header">
		<span class="spai-logo">
			<span class="dashicons dashicons-visibility"></span>
		</span>
		<?php esc_html_e( 'Control Room', 'mumega-mcp' ); ?>
		<span class="spai-version">v<?php echo esc_html( SPAI_VERSION ); ?></span>
	</h1>

	<p class="description">
		<?php esc_html_e( 'Review agent work, SEO findings, recent activity, and rollback-ready changes from one supervised WordPress screen.', 'mumega-mcp' ); ?>
	</p>

	<div class="spai-control-summary">
		<div class="spai-library-stat">
			<span class="spai-library-stat__value"><?php echo esc_html( (string) ( $approval_counts['pending'] ?? 0 ) ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Pending approvals', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat">
			<span class="spai-library-stat__value"><?php echo esc_html( (string) ( $approval_counts['applied'] ?? 0 ) ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Rollback-ready changes', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat">
			<span class="spai-library-stat__value"><?php echo esc_html( (string) ( $seo_summary['open'] ?? 0 ) ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Open SEO issues', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat">
			<span class="spai-library-stat__value"><?php echo esc_html( (string) ( $seo_summary['error'] ?? 0 ) ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'SEO errors', 'mumega-mcp' ); ?></span>
		</div>
	</div>

	<div class="spai-control-grid">
		<div class="spai-card">
			<h2>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( 'Recommended Next Actions', 'mumega-mcp' ); ?>
			</h2>
			<?php foreach ( $recommendations as $recommendation ) : ?>
				<div class="spai-control-action">
					<span class="spai-control-priority spai-control-priority--<?php echo esc_attr( sanitize_html_class( $recommendation['priority'] ?? 'low' ) ); ?>">
						<?php echo esc_html( $recommendation['priority'] ?? 'low' ); ?>
					</span>
					<div>
						<strong><?php echo esc_html( $recommendation['title'] ?? '' ); ?></strong>
						<p><?php echo esc_html( $recommendation['detail'] ?? '' ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="spai-card">
			<h2>
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Pending Approvals', 'mumega-mcp' ); ?>
			</h2>
			<?php if ( empty( $pending_items ) ) : ?>
				<p><em><?php esc_html_e( 'No pending approval requests.', 'mumega-mcp' ); ?></em></p>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $pending_items as $item ) : ?>
						<?php
						$resource = isset( $item['resource'] ) && is_array( $item['resource'] ) ? $item['resource'] : array();
						$post_id  = isset( $resource['id'] ) ? absint( $resource['id'] ) : 0;
						$edit_url = $post_id ? get_edit_post_link( $post_id, 'raw' ) : '';
						?>
						<li>
							<strong><?php echo esc_html( $item['title'] ?? $item['id'] ?? '' ); ?></strong>
							<span><?php echo esc_html( $item['tool'] ?? $item['action'] ?? '' ); ?></span>
							<?php if ( $edit_url ) : ?>
								<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Open resource', 'mumega-mcp' ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="spai-control-grid">
		<div class="spai-card">
			<h2>
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'Open SEO Issues', 'mumega-mcp' ); ?>
			</h2>
			<?php if ( empty( $seo_issues ) ) : ?>
				<p><em><?php esc_html_e( 'No stored open SEO issues. Run a site SEO audit with store=true to populate this panel.', 'mumega-mcp' ); ?></em></p>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $seo_issues as $issue ) : ?>
						<?php
						$post_id  = isset( $issue['post_id'] ) ? absint( $issue['post_id'] ) : 0;
						$edit_url = $post_id ? get_edit_post_link( $post_id, 'raw' ) : '';
						?>
						<li>
							<strong><?php echo esc_html( $issue['message'] ?? $issue['code'] ?? '' ); ?></strong>
							<span>
								<?php echo esc_html( strtoupper( (string) ( $issue['severity'] ?? 'info' ) ) ); ?>
								<?php echo esc_html( ' · ' . (string) ( $issue['category'] ?? '' ) ); ?>
							</span>
							<?php if ( $edit_url ) : ?>
								<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $issue['title'] ?? __( 'Open post', 'mumega-mcp' ) ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="spai-card">
			<h2>
				<span class="dashicons dashicons-backup"></span>
				<?php esc_html_e( 'Rollback Ready', 'mumega-mcp' ); ?>
			</h2>
			<?php if ( empty( $rollback_items ) ) : ?>
				<p><em><?php esc_html_e( 'No applied approval requests are currently listed for rollback.', 'mumega-mcp' ); ?></em></p>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $rollback_items as $item ) : ?>
						<li>
							<strong><?php echo esc_html( $item['title'] ?? $item['id'] ?? '' ); ?></strong>
							<span><?php echo esc_html( $item['applied_at'] ?? '' ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="spai-card">
		<h2>
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'Recent Agent Activity', 'mumega-mcp' ); ?>
		</h2>
		<?php if ( empty( $activity_rows ) ) : ?>
			<p><em><?php esc_html_e( 'No activity recorded yet.', 'mumega-mcp' ); ?></em></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'When', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Action', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Endpoint', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Status', 'mumega-mcp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $activity_rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['created_at'] ?? '' ); ?></td>
							<td><?php echo esc_html( $row['action'] ?? '' ); ?></td>
							<td><code><?php echo esc_html( $row['endpoint'] ?? '' ); ?></code></td>
							<td><?php echo esc_html( $row['status_code'] ?? '' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
