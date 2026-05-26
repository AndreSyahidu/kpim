<?php
/**
 * Action Plan management (/kpi/action-plan).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$period     = $ctx['period'];
$can_manage = current_user_can( 'mbd_kpi_manage_action' );
$can_verify = current_user_can( 'mbd_kpi_verify' );

$emp_map  = MBD_KPI_Service::employee_map();
$registry = MBD_KPI_Service::get_registry();
$reg_map  = array();
foreach ( $registry as $r ) {
	$reg_map[ (int) $r['id'] ] = $r['kpi_code'] . ' — ' . $r['kpi_name'];
}

$prefill_registry = isset( $_GET['registry_id'] ) ? (int) $_GET['registry_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$detail_id        = isset( $_GET['action_plan_id'] ) ? (int) $_GET['action_plan_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$detail           = $detail_id ? MBD_KPI_Action_Plan_Service::get_action_plan( $detail_id ) : null;

$plans   = MBD_KPI_Action_Plan_Service::get_action_plans();
$statuses = mbd_kpi_action_statuses();
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Action Plans', 'mbd-kpi' ); ?></h2>
</div>

<section class="mbd-card">
	<table class="mbd-table">
		<thead><tr><th><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Problem', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Due', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Priority', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th><th></th></tr></thead>
		<tbody>
		<?php if ( $plans ) : ?>
			<?php foreach ( $plans as $p ) : ?>
				<tr>
					<td><?php echo esc_html( $reg_map[ (int) $p['registry_id'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( wp_trim_words( $p['problem_statement'], 12 ) ); ?></td>
					<td><?php echo esc_html( $emp_map[ (int) $p['owner_employee_id'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( $p['due_date'] ?: '—' ); ?></td>
					<td><?php echo mbd_kpi_pill( $p['priority'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><?php echo mbd_kpi_pill( $statuses[ $p['status'] ] ?? $p['status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => (int) $p['id'] ) ) ); ?>"><?php esc_html_e( 'Open', 'mbd-kpi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="7" class="mbd-muted"><?php esc_html_e( 'No action plans yet.', 'mbd-kpi' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</section>

<?php if ( $detail ) :
	$updates  = MBD_KPI_Action_Plan_Service::get_updates( $detail_id );
	$evidence = MBD_KPI_Action_Plan_Service::get_evidence( 'action_plan', $detail_id ); ?>
	<section class="mbd-card">
		<h3><?php esc_html_e( 'Action Plan Detail', 'mbd-kpi' ); ?></h3>
		<div class="mbd-detail-grid">
			<div><strong><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $reg_map[ (int) $detail['registry_id'] ] ?? '—' ); ?></div>
			<div><strong><?php esc_html_e( 'Status', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $statuses[ $detail['status'] ] ?? $detail['status'] ); ?></div>
			<div><strong><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $emp_map[ (int) $detail['owner_employee_id'] ] ?? '—' ); ?></div>
			<div><strong><?php esc_html_e( 'Due', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $detail['due_date'] ?: '—' ); ?></div>
		</div>
		<p><strong><?php esc_html_e( 'Problem', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $detail['problem_statement'] ); ?></p>
		<p><strong><?php esc_html_e( 'Root Cause', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $detail['root_cause_category'] ); ?> — <?php echo esc_html( $detail['root_cause_detail'] ); ?></p>
		<p><strong><?php esc_html_e( 'Corrective', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $detail['corrective_action'] ); ?></p>
		<p><strong><?php esc_html_e( 'Preventive', 'mbd-kpi' ); ?>:</strong> <?php echo esc_html( $detail['preventive_action'] ); ?></p>

		<h4><?php esc_html_e( 'Evidence', 'mbd-kpi' ); ?></h4>
		<?php if ( $evidence ) : ?>
			<ul class="mbd-evidence-list">
				<?php foreach ( $evidence as $ev ) : ?>
					<li>
						<?php echo esc_html( $ev['title'] ?: mbd_kpi_evidence_types()[ $ev['evidence_type'] ] ?? $ev['evidence_type'] ); ?>
						<?php $link = $ev['file_url'] ?: $ev['link_url']; ?>
						<?php if ( $link ) : ?> &middot; <a class="mbd-link" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'mbd-kpi' ); ?></a><?php endif; ?>
						<?php echo mbd_kpi_pill( mbd_kpi_verification_statuses()[ $ev['verification_status'] ] ?? $ev['verification_status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $can_verify && 'pending' === $ev['verification_status'] ) : ?>
							<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => $detail_id ) ) ); ?>" class="mbd-inline-form">
								<?php mbd_kpi_nonce_field( 'mbd_kpi_verify_evidence' ); ?>
								<input type="hidden" name="mbd_kpi_form" value="verify_evidence">
								<input type="hidden" name="evidence_id" value="<?php echo (int) $ev['id']; ?>">
								<button name="verification_status" value="approved" class="mbd-btn-mini mbd-btn-ok"><?php esc_html_e( 'Approve', 'mbd-kpi' ); ?></button>
								<button name="verification_status" value="rejected" class="mbd-btn-mini mbd-btn-danger"><?php esc_html_e( 'Reject', 'mbd-kpi' ); ?></button>
							</form>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'No evidence attached. Required before this plan can be verified effective.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>

		<?php if ( $can_manage ) : ?>
			<details class="mbd-details">
				<summary><?php esc_html_e( 'Attach Evidence', 'mbd-kpi' ); ?></summary>
				<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => $detail_id ) ) ); ?>" class="mbd-form" enctype="multipart/form-data">
					<?php mbd_kpi_nonce_field( 'mbd_kpi_save_evidence' ); ?>
					<input type="hidden" name="mbd_kpi_form" value="save_evidence">
					<input type="hidden" name="entity_type" value="action_plan">
					<input type="hidden" name="entity_id" value="<?php echo (int) $detail_id; ?>">
					<div class="mbd-form-row">
						<label><?php esc_html_e( 'Title', 'mbd-kpi' ); ?><input type="text" name="title"></label>
						<label><?php esc_html_e( 'Type', 'mbd-kpi' ); ?>
							<select name="evidence_type">
								<?php foreach ( mbd_kpi_evidence_types() as $k => $l ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</div>
					<label><?php esc_html_e( 'File (image/PDF/doc)', 'mbd-kpi' ); ?><input type="file" name="evidence_file"></label>
					<label><?php esc_html_e( 'Or Link URL', 'mbd-kpi' ); ?><input type="url" name="link_url" placeholder="https://"></label>
					<label><?php esc_html_e( 'Note', 'mbd-kpi' ); ?><textarea name="note" rows="2"></textarea></label>
					<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Attach', 'mbd-kpi' ); ?></button>
				</form>
			</details>

			<details class="mbd-details">
				<summary><?php esc_html_e( 'Add Progress Update', 'mbd-kpi' ); ?></summary>
				<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => $detail_id ) ) ); ?>" class="mbd-form">
					<?php mbd_kpi_nonce_field( 'mbd_kpi_add_action_update' ); ?>
					<input type="hidden" name="mbd_kpi_form" value="add_action_update">
					<input type="hidden" name="action_plan_id" value="<?php echo (int) $detail_id; ?>">
					<label><?php esc_html_e( 'Update Note', 'mbd-kpi' ); ?><textarea name="update_note" rows="2" required></textarea></label>
					<button type="submit" class="mbd-btn"><?php esc_html_e( 'Add Update', 'mbd-kpi' ); ?></button>
				</form>
			</details>
		<?php endif; ?>

		<?php if ( $updates ) : ?>
			<h4><?php esc_html_e( 'Timeline', 'mbd-kpi' ); ?></h4>
			<ul class="mbd-timeline">
				<?php foreach ( $updates as $u ) : ?>
					<li><span class="mbd-muted"><?php echo esc_html( $u['created_at'] ); ?></span> — <?php echo esc_html( $u['update_note'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
<?php endif; ?>

<?php if ( $can_manage ) :
	$ed = $detail;
	$gv = function ( $key, $default = '' ) use ( $ed, $prefill_registry ) {
		if ( $ed && isset( $ed[ $key ] ) ) {
			return $ed[ $key ];
		}
		if ( 'registry_id' === $key && $prefill_registry ) {
			return $prefill_registry;
		}
		return $default;
	}; ?>
	<section class="mbd-card">
		<h3><?php echo $detail ? esc_html__( 'Edit Action Plan', 'mbd-kpi' ) : esc_html__( 'New Action Plan', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'action-plan' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_action_plan' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_action_plan">
			<?php if ( $detail ) : ?><input type="hidden" name="id" value="<?php echo (int) $detail['id']; ?>"><?php endif; ?>
			<input type="hidden" name="period" value="<?php echo esc_attr( $ed['period'] ?? $period ); ?>">
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Linked KPI', 'mbd-kpi' ); ?>
					<select name="registry_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $reg_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>" <?php selected( (int) $gv( 'registry_id' ), (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?>
					<select name="owner_employee_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $emp_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>" <?php selected( (int) $gv( 'owner_employee_id' ), (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<label><?php esc_html_e( 'Problem Statement', 'mbd-kpi' ); ?><textarea name="problem_statement" rows="2"><?php echo esc_textarea( $gv( 'problem_statement' ) ); ?></textarea></label>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Root Cause Category', 'mbd-kpi' ); ?><input type="text" name="root_cause_category" value="<?php echo esc_attr( $gv( 'root_cause_category' ) ); ?>"></label>
				<label><?php esc_html_e( 'Priority', 'mbd-kpi' ); ?>
					<select name="priority">
						<?php foreach ( array( 'low', 'medium', 'high', 'critical' ) as $pr ) : ?>
							<option value="<?php echo esc_attr( $pr ); ?>" <?php selected( $gv( 'priority', 'medium' ), $pr ); ?>><?php echo esc_html( ucfirst( $pr ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Due Date', 'mbd-kpi' ); ?><input type="date" name="due_date" value="<?php echo esc_attr( $gv( 'due_date' ) ); ?>"></label>
			</div>
			<label><?php esc_html_e( 'Root Cause Detail', 'mbd-kpi' ); ?><textarea name="root_cause_detail" rows="2"><?php echo esc_textarea( $gv( 'root_cause_detail' ) ); ?></textarea></label>
			<label><?php esc_html_e( 'Corrective Action', 'mbd-kpi' ); ?><textarea name="corrective_action" rows="2"><?php echo esc_textarea( $gv( 'corrective_action' ) ); ?></textarea></label>
			<label><?php esc_html_e( 'Preventive Action', 'mbd-kpi' ); ?><textarea name="preventive_action" rows="2"><?php echo esc_textarea( $gv( 'preventive_action' ) ); ?></textarea></label>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Reviewer', 'mbd-kpi' ); ?>
					<select name="reviewer_employee_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $emp_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>" <?php selected( (int) $gv( 'reviewer_employee_id' ), (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Status', 'mbd-kpi' ); ?>
					<select name="status">
						<?php foreach ( $statuses as $k => $l ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $gv( 'status', 'open' ), $k ); ?>><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Effectiveness', 'mbd-kpi' ); ?>
					<select name="effectiveness_check">
						<option value="pending" <?php selected( $gv( 'effectiveness_check', 'pending' ), 'pending' ); ?>><?php esc_html_e( 'Pending', 'mbd-kpi' ); ?></option>
						<option value="effective" <?php selected( $gv( 'effectiveness_check' ), 'effective' ); ?>><?php esc_html_e( 'Effective', 'mbd-kpi' ); ?></option>
						<option value="ineffective" <?php selected( $gv( 'effectiveness_check' ), 'ineffective' ); ?>><?php esc_html_e( 'Ineffective', 'mbd-kpi' ); ?></option>
					</select>
				</label>
			</div>
			<p class="mbd-muted"><?php esc_html_e( 'Marking "Done – Pending Verification" requires evidence; "Verified Effective" requires approved evidence.', 'mbd-kpi' ); ?></p>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Action Plan', 'mbd-kpi' ); ?></button>
		</form>
	</section>
<?php endif; ?>
