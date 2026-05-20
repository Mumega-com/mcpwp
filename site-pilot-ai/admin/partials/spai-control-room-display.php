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
$approved_items  = isset( $control_room['approved_approvals'] ) && is_array( $control_room['approved_approvals'] ) ? $control_room['approved_approvals'] : array();
$rollback_items  = isset( $control_room['rollback_ready'] ) && is_array( $control_room['rollback_ready'] ) ? $control_room['rollback_ready'] : array();
$seo_issues      = isset( $control_room['open_seo_issues'] ) && is_array( $control_room['open_seo_issues'] ) ? $control_room['open_seo_issues'] : array();
$seo_filters     = isset( $control_room['seo_filters'] ) && is_array( $control_room['seo_filters'] ) ? $control_room['seo_filters'] : array();
$event_inbox     = isset( $control_room['event_inbox'] ) && is_array( $control_room['event_inbox'] ) ? $control_room['event_inbox'] : array();
$event_filters   = isset( $control_room['event_filters'] ) && is_array( $control_room['event_filters'] ) ? $control_room['event_filters'] : array();
$activity_rows   = isset( $control_room['recent_activity'] ) && is_array( $control_room['recent_activity'] ) ? $control_room['recent_activity'] : array();
$recommendations = isset( $control_room['recommendations'] ) && is_array( $control_room['recommendations'] ) ? $control_room['recommendations'] : array();
$event_summary   = isset( $event_inbox['summary'] ) && is_array( $event_inbox['summary'] ) ? $event_inbox['summary'] : array();
$event_items     = isset( $event_inbox['events'] ) && is_array( $event_inbox['events'] ) ? $event_inbox['events'] : array();

$pending_count  = (int) ( $approval_counts['pending'] ?? 0 );
$approved_count = (int) ( $approval_counts['approved'] ?? 0 );
$applied_count  = (int) ( $approval_counts['applied'] ?? 0 );
$seo_open_count = (int) ( $seo_summary['open'] ?? 0 );
$seo_error_count = (int) ( $seo_summary['error'] ?? 0 );
$event_escalated_count = (int) ( $event_summary['escalated'] ?? 0 );
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

	<?php settings_errors( 'spai_messages' ); ?>

	<div class="spai-control-summary">
		<div class="spai-library-stat spai-control-stat <?php echo esc_attr( $pending_count > 0 ? 'is-warning' : 'is-good' ); ?>">
			<span class="spai-control-stat__icon dashicons <?php echo esc_attr( $pending_count > 0 ? 'dashicons-clock' : 'dashicons-yes-alt' ); ?>"></span>
			<span class="spai-library-stat__value"><?php echo esc_html( (string) $pending_count ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Pending approvals', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat spai-control-stat <?php echo esc_attr( $applied_count > 0 ? 'is-info' : 'is-good' ); ?>">
			<span class="spai-control-stat__icon dashicons <?php echo esc_attr( $applied_count > 0 ? 'dashicons-backup' : 'dashicons-shield' ); ?>"></span>
			<span class="spai-library-stat__value"><?php echo esc_html( (string) $applied_count ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Rollback-ready changes', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat spai-control-stat <?php echo esc_attr( $seo_open_count > 0 ? 'is-warning' : 'is-good' ); ?>">
			<span class="spai-control-stat__icon dashicons <?php echo esc_attr( $seo_open_count > 0 ? 'dashicons-search' : 'dashicons-chart-line' ); ?>"></span>
			<span class="spai-library-stat__value"><?php echo esc_html( (string) $seo_open_count ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Open SEO issues', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat spai-control-stat <?php echo esc_attr( $seo_error_count > 0 ? 'is-critical' : 'is-good' ); ?>">
			<span class="spai-control-stat__icon dashicons <?php echo esc_attr( $seo_error_count > 0 ? 'dashicons-warning' : 'dashicons-yes' ); ?>"></span>
			<span class="spai-library-stat__value"><?php echo esc_html( (string) $seo_error_count ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'SEO errors', 'mumega-mcp' ); ?></span>
		</div>
		<div class="spai-library-stat spai-control-stat <?php echo esc_attr( $event_escalated_count > 0 ? 'is-critical' : 'is-good' ); ?>">
			<span class="spai-control-stat__icon dashicons <?php echo esc_attr( $event_escalated_count > 0 ? 'dashicons-bell' : 'dashicons-yes-alt' ); ?>"></span>
			<span class="spai-library-stat__value"><?php echo esc_html( (string) $event_escalated_count ); ?></span>
			<span class="spai-library-stat__label"><?php esc_html_e( 'Escalated events', 'mumega-mcp' ); ?></span>
		</div>
	</div>

	<div class="spai-control-grid">
		<div class="spai-card">
			<h2>
				<span class="dashicons dashicons-yes-alt"></span>
				<?php esc_html_e( 'Recommended Next Actions', 'mumega-mcp' ); ?>
			</h2>
			<?php foreach ( $recommendations as $recommendation ) : ?>
				<div class="spai-control-action spai-control-action--<?php echo esc_attr( sanitize_html_class( $recommendation['priority'] ?? 'low' ) ); ?>">
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
				<div class="spai-control-empty is-good">
					<span class="dashicons dashicons-yes-alt"></span>
					<p><?php esc_html_e( 'No pending approval requests.', 'mumega-mcp' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $pending_items as $item ) : ?>
						<?php
						$resource = isset( $item['resource'] ) && is_array( $item['resource'] ) ? $item['resource'] : array();
						$post_id  = isset( $resource['id'] ) ? absint( $resource['id'] ) : 0;
						$edit_url = $post_id ? get_edit_post_link( $post_id, 'raw' ) : '';
						?>
						<li class="spai-control-list__item is-warning">
							<span class="spai-control-list__icon dashicons dashicons-clock"></span>
							<strong><?php echo esc_html( $item['title'] ?? $item['id'] ?? '' ); ?></strong>
							<span><?php echo esc_html( $item['tool'] ?? $item['action'] ?? '' ); ?></span>
							<div class="spai-control-actions">
								<form method="post">
									<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
									<input type="hidden" name="spai_control_room_action" value="approve">
									<input type="hidden" name="spai_approval_id" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Approve', 'mumega-mcp' ); ?></button>
								</form>
								<form method="post">
									<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
									<input type="hidden" name="spai_control_room_action" value="reject">
									<input type="hidden" name="spai_approval_id" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
									<button type="submit" class="button"><?php esc_html_e( 'Reject', 'mumega-mcp' ); ?></button>
								</form>
								<?php if ( $edit_url ) : ?>
									<a class="button" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Open resource', 'mumega-mcp' ); ?></a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="spai-control-grid">
		<div class="spai-card">
			<h2>
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Approved Changes', 'mumega-mcp' ); ?>
			</h2>
			<p class="description">
				<?php
				printf(
					/* translators: %d: number of approved approvals */
					esc_html( _n( '%d approved change is ready to apply.', '%d approved changes are ready to apply.', $approved_count, 'mumega-mcp' ) ),
					esc_html( (string) $approved_count )
				);
				?>
			</p>
			<?php if ( empty( $approved_items ) ) : ?>
				<div class="spai-control-empty is-muted">
					<span class="dashicons dashicons-shield"></span>
					<p><?php esc_html_e( 'No approved approval requests are waiting to apply.', 'mumega-mcp' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $approved_items as $item ) : ?>
						<li class="spai-control-list__item is-info">
							<span class="spai-control-list__icon dashicons dashicons-saved"></span>
							<strong><?php echo esc_html( $item['title'] ?? $item['id'] ?? '' ); ?></strong>
							<span><?php echo esc_html( $item['approved_at'] ?? '' ); ?></span>
							<div class="spai-control-actions">
								<form method="post">
									<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
									<input type="hidden" name="spai_control_room_action" value="apply">
									<input type="hidden" name="spai_approval_id" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply', 'mumega-mcp' ); ?></button>
								</form>
								<form method="post">
									<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
									<input type="hidden" name="spai_control_room_action" value="reject">
									<input type="hidden" name="spai_approval_id" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
									<button type="submit" class="button"><?php esc_html_e( 'Reject', 'mumega-mcp' ); ?></button>
								</form>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="spai-card">
			<div class="spai-control-card-header">
				<h2>
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Stored SEO Issues', 'mumega-mcp' ); ?>
				</h2>
				<form method="post">
					<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
					<input type="hidden" name="spai_control_room_action" value="run_seo_audit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Run SEO Audit', 'mumega-mcp' ); ?></button>
				</form>
			</div>
			<form method="get" class="spai-control-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( Spai_Admin::CONTROL_ROOM_PAGE_SLUG ); ?>">
				<label>
					<span><?php esc_html_e( 'Status', 'mumega-mcp' ); ?></span>
					<select name="spai_seo_status">
						<option value="open" <?php selected( $seo_filters['status'] ?? 'open', 'open' ); ?>><?php esc_html_e( 'Open', 'mumega-mcp' ); ?></option>
						<option value="resolved" <?php selected( $seo_filters['status'] ?? '', 'resolved' ); ?>><?php esc_html_e( 'Resolved', 'mumega-mcp' ); ?></option>
						<option value="" <?php selected( $seo_filters['status'] ?? '', '' ); ?>><?php esc_html_e( 'Any', 'mumega-mcp' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Severity', 'mumega-mcp' ); ?></span>
					<select name="spai_seo_severity">
						<option value="" <?php selected( $seo_filters['severity'] ?? '', '' ); ?>><?php esc_html_e( 'Any', 'mumega-mcp' ); ?></option>
						<option value="error" <?php selected( $seo_filters['severity'] ?? '', 'error' ); ?>><?php esc_html_e( 'Error', 'mumega-mcp' ); ?></option>
						<option value="warning" <?php selected( $seo_filters['severity'] ?? '', 'warning' ); ?>><?php esc_html_e( 'Warning', 'mumega-mcp' ); ?></option>
						<option value="info" <?php selected( $seo_filters['severity'] ?? '', 'info' ); ?>><?php esc_html_e( 'Info', 'mumega-mcp' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Category', 'mumega-mcp' ); ?></span>
					<select name="spai_seo_category">
						<option value="" <?php selected( $seo_filters['category'] ?? '', '' ); ?>><?php esc_html_e( 'Any', 'mumega-mcp' ); ?></option>
						<option value="readiness" <?php selected( $seo_filters['category'] ?? '', 'readiness' ); ?>><?php esc_html_e( 'Readiness', 'mumega-mcp' ); ?></option>
						<option value="structured_data" <?php selected( $seo_filters['category'] ?? '', 'structured_data' ); ?>><?php esc_html_e( 'Structured data', 'mumega-mcp' ); ?></option>
						<option value="media" <?php selected( $seo_filters['category'] ?? '', 'media' ); ?>><?php esc_html_e( 'Media', 'mumega-mcp' ); ?></option>
						<option value="content_quality" <?php selected( $seo_filters['category'] ?? '', 'content_quality' ); ?>><?php esc_html_e( 'Content quality', 'mumega-mcp' ); ?></option>
					</select>
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'mumega-mcp' ); ?></button>
			</form>
			<?php if ( empty( $seo_issues ) ) : ?>
				<div class="spai-control-empty is-good">
					<span class="dashicons dashicons-chart-line"></span>
					<p><?php esc_html_e( 'No stored open SEO issues. Run a site SEO audit with store=true to populate this panel.', 'mumega-mcp' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $seo_issues as $issue ) : ?>
						<?php
						$post_id     = isset( $issue['post_id'] ) ? absint( $issue['post_id'] ) : 0;
						$edit_url    = $post_id ? get_edit_post_link( $post_id, 'raw' ) : '';
						$severity    = isset( $issue['severity'] ) ? sanitize_key( (string) $issue['severity'] ) : 'info';
						$icon_class  = 'error' === $severity ? 'dashicons-warning' : ( 'warning' === $severity ? 'dashicons-info' : 'dashicons-lightbulb' );
						$state_class = 'error' === $severity ? 'is-critical' : ( 'warning' === $severity ? 'is-warning' : 'is-info' );
						?>
						<li class="spai-control-list__item <?php echo esc_attr( $state_class ); ?>">
							<span class="spai-control-list__icon dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
							<strong><?php echo esc_html( $issue['message'] ?? $issue['code'] ?? '' ); ?></strong>
							<span>
								<?php echo esc_html( strtoupper( $severity ) ); ?>
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
				<div class="spai-control-empty is-muted">
					<span class="dashicons dashicons-shield"></span>
					<p><?php esc_html_e( 'No applied approval requests are currently listed for rollback.', 'mumega-mcp' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="spai-control-list">
					<?php foreach ( $rollback_items as $item ) : ?>
						<li class="spai-control-list__item is-info">
							<span class="spai-control-list__icon dashicons dashicons-backup"></span>
							<strong><?php echo esc_html( $item['title'] ?? $item['id'] ?? '' ); ?></strong>
							<span><?php echo esc_html( $item['applied_at'] ?? '' ); ?></span>
							<div class="spai-control-actions">
								<form method="post">
									<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
									<input type="hidden" name="spai_control_room_action" value="rollback">
									<input type="hidden" name="spai_approval_id" value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
									<button type="submit" class="button"><?php esc_html_e( 'Rollback', 'mumega-mcp' ); ?></button>
								</form>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="spai-card">
		<div class="spai-control-card-header">
			<h2>
				<span class="dashicons dashicons-bell"></span>
				<?php esc_html_e( 'Event Inbox', 'mumega-mcp' ); ?>
			</h2>
			<span class="spai-control-count">
				<?php
				printf(
					/* translators: %d: number of visible events */
					esc_html( _n( '%d event', '%d events', (int) ( $event_summary['total'] ?? 0 ), 'mumega-mcp' ) ),
					esc_html( (string) ( $event_summary['total'] ?? 0 ) )
				);
				?>
			</span>
		</div>
		<form method="get" class="spai-control-filters">
			<input type="hidden" name="page" value="<?php echo esc_attr( Spai_Admin::CONTROL_ROOM_PAGE_SLUG ); ?>">
			<label>
				<span><?php esc_html_e( 'Event type', 'mumega-mcp' ); ?></span>
				<input type="text" name="spai_event_type" value="<?php echo esc_attr( $event_filters['type'] ?? '' ); ?>" placeholder="approval.created">
			</label>
			<label>
				<span><?php esc_html_e( 'Risk', 'mumega-mcp' ); ?></span>
				<select name="spai_event_risk">
					<option value="" <?php selected( $event_filters['risk_level'] ?? '', '' ); ?>><?php esc_html_e( 'Any', 'mumega-mcp' ); ?></option>
					<option value="high" <?php selected( $event_filters['risk_level'] ?? '', 'high' ); ?>><?php esc_html_e( 'High', 'mumega-mcp' ); ?></option>
					<option value="medium" <?php selected( $event_filters['risk_level'] ?? '', 'medium' ); ?>><?php esc_html_e( 'Medium', 'mumega-mcp' ); ?></option>
					<option value="low" <?php selected( $event_filters['risk_level'] ?? '', 'low' ); ?>><?php esc_html_e( 'Low', 'mumega-mcp' ); ?></option>
				</select>
			</label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'mumega-mcp' ); ?></button>
		</form>
		<?php if ( empty( $event_items ) ) : ?>
			<div class="spai-control-empty is-muted">
				<span class="dashicons dashicons-bell"></span>
				<p><?php esc_html_e( 'No normalized events match the current filters yet.', 'mumega-mcp' ); ?></p>
			</div>
		<?php else : ?>
			<ul class="spai-control-list spai-control-event-list">
				<?php foreach ( $event_items as $event ) : ?>
					<?php
					$event_risk       = isset( $event['risk_level'] ) ? sanitize_key( (string) $event['risk_level'] ) : 'low';
					$event_escalation = isset( $event['escalation'] ) && is_array( $event['escalation'] ) ? $event['escalation'] : array();
					$event_class      = 'high' === $event_risk ? 'is-critical' : ( 'medium' === $event_risk ? 'is-warning' : 'is-info' );
					$event_icon       = ! empty( $event_escalation['escalated'] ) ? 'dashicons-warning' : 'dashicons-info';
					$event_resource   = isset( $event['resource'] ) && is_array( $event['resource'] ) ? $event['resource'] : array();
					?>
					<li class="spai-control-list__item <?php echo esc_attr( $event_class ); ?>">
						<span class="spai-control-list__icon dashicons <?php echo esc_attr( $event_icon ); ?>"></span>
						<strong><?php echo esc_html( $event['type'] ?? '' ); ?></strong>
						<span>
							<?php echo esc_html( strtoupper( $event_risk ) ); ?>
							<?php echo esc_html( ' · ' . (string) ( $event['timestamp'] ?? '' ) ); ?>
						</span>
						<?php if ( ! empty( $event_escalation['label'] ) ) : ?>
							<span class="spai-control-event-escalation"><?php echo esc_html( $event_escalation['label'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $event['recommended_action'] ) ) : ?>
							<span><?php echo esc_html( $event['recommended_action'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $event_resource['id'] ) || ! empty( $event_resource['type'] ) ) : ?>
							<code><?php echo esc_html( trim( (string) ( $event_resource['type'] ?? '' ) . ' #' . (string) ( $event_resource['id'] ?? '' ) ) ); ?></code>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="spai-card">
		<h2>
			<span class="dashicons dashicons-list-view"></span>
			<?php esc_html_e( 'Recent Agent Activity', 'mumega-mcp' ); ?>
		</h2>
		<?php if ( empty( $activity_rows ) ) : ?>
			<div class="spai-control-empty is-muted">
				<span class="dashicons dashicons-list-view"></span>
				<p><?php esc_html_e( 'No activity recorded yet.', 'mumega-mcp' ); ?></p>
			</div>
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
