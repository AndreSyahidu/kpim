<?php
/**
 * OKR management (/kpi/okr).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$period     = $ctx['period'];
$can_manage = current_user_can( 'mbd_kpi_manage_okr' );

$objectives  = MBD_KPI_OKR_Service::get_objectives();
$emp_map     = MBD_KPI_Service::employee_map();
$div_map     = MBD_KPI_Service::division_map();
$registry    = MBD_KPI_Service::get_registry();
$reg_map     = array();
foreach ( $registry as $r ) {
	$reg_map[ (int) $r['id'] ] = $r['kpi_code'] . ' — ' . $r['kpi_name'];
}

$selected_obj = isset( $_GET['objective_id'] ) ? (int) $_GET['objective_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Objectives & Key Results', 'mbd-kpi' ); ?></h2>
</div>

<div class="mbd-grid-2">
	<section class="mbd-card">
		<h3><?php esc_html_e( 'Objectives', 'mbd-kpi' ); ?></h3>
		<?php if ( $objectives ) : ?>
			<table class="mbd-table">
				<thead><tr><th><?php esc_html_e( 'Objective', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Period', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Progress', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $objectives as $o ) :
					$prog = MBD_KPI_OKR_Service::objective_progress( (int) $o['id'] ); ?>
					<tr class="<?php echo $selected_obj === (int) $o['id'] ? 'is-selected' : ''; ?>">
						<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'okr', array( 'objective_id' => (int) $o['id'] ) ) ); ?>"><?php echo esc_html( $o['title'] ); ?></a></td>
						<td><?php echo esc_html( $o['period'] ); ?></td>
						<td><?php echo esc_html( $emp_map[ (int) $o['owner_employee_id'] ] ?? '—' ); ?></td>
						<td><div class="mbd-progress"><span style="width:<?php echo esc_attr( min( 100, $prog ) ); ?>%"></span></div><?php echo esc_html( $prog ); ?>%</td>
						<td><?php echo mbd_kpi_pill( $o['status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'No objectives yet.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>

	<?php if ( $can_manage ) : ?>
	<section class="mbd-card">
		<h3><?php esc_html_e( 'New Objective', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'okr' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_objective' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_objective">
			<label><?php esc_html_e( 'Title', 'mbd-kpi' ); ?><input type="text" name="title" required></label>
			<label><?php esc_html_e( 'Description', 'mbd-kpi' ); ?><textarea name="description" rows="2"></textarea></label>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Period', 'mbd-kpi' ); ?>
					<select name="period">
						<?php foreach ( mbd_kpi_period_options() as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $period, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?>
					<select name="owner_employee_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $emp_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Division', 'mbd-kpi' ); ?>
					<select name="division_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $div_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Status', 'mbd-kpi' ); ?>
					<select name="status">
						<option value="on_track"><?php esc_html_e( 'On Track', 'mbd-kpi' ); ?></option>
						<option value="at_risk"><?php esc_html_e( 'At Risk', 'mbd-kpi' ); ?></option>
						<option value="behind"><?php esc_html_e( 'Behind', 'mbd-kpi' ); ?></option>
						<option value="done"><?php esc_html_e( 'Done', 'mbd-kpi' ); ?></option>
					</select>
				</label>
			</div>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Objective', 'mbd-kpi' ); ?></button>
		</form>
	</section>
	<?php endif; ?>
</div>

<?php if ( $selected_obj ) :
	$obj = MBD_KPI_OKR_Service::get_objective( $selected_obj );
	if ( $obj ) :
		$krs = MBD_KPI_OKR_Service::get_key_results( $selected_obj ); ?>
		<section class="mbd-card">
			<h3><?php printf( esc_html__( 'Key Results — %s', 'mbd-kpi' ), esc_html( $obj['title'] ) ); ?></h3>
			<?php if ( $krs ) : ?>
				<table class="mbd-table">
					<thead><tr><th><?php esc_html_e( 'Key Result', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Target', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Current', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Progress', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Confidence', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Linked KPI', 'mbd-kpi' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $krs as $kr ) :
						$linked = array_filter( array_map( 'intval', explode( ',', $kr['linked_kpi_ids'] ) ) ); ?>
						<tr>
							<td><?php echo esc_html( $kr['title'] ); ?><?php if ( $kr['risk_note'] ) : ?><br><small class="mbd-muted"><?php echo esc_html( $kr['risk_note'] ); ?></small><?php endif; ?></td>
							<td><?php echo wp_kses_post( mbd_kpi_format_value( $kr['target_value'], $kr['unit'] ) ); ?></td>
							<td><?php echo wp_kses_post( mbd_kpi_format_value( $kr['current_value'], $kr['unit'] ) ); ?></td>
							<td><div class="mbd-progress"><span style="width:<?php echo esc_attr( min( 100, (float) $kr['progress'] ) ); ?>%"></span></div><?php echo esc_html( $kr['progress'] ); ?>%</td>
							<td><?php echo mbd_kpi_pill( $kr['confidence'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td><?php
								$names = array();
								foreach ( $linked as $lid ) {
									if ( isset( $reg_map[ $lid ] ) ) {
										$names[] = $reg_map[ $lid ];
									}
								}
								echo $names ? esc_html( implode( '; ', $names ) ) : '&mdash;';
							?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="mbd-muted"><?php esc_html_e( 'No key results yet.', 'mbd-kpi' ); ?></p>
			<?php endif; ?>

			<?php if ( $can_manage ) : ?>
			<details class="mbd-details">
				<summary><?php esc_html_e( 'Add Key Result', 'mbd-kpi' ); ?></summary>
				<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'okr' ) ); ?>" class="mbd-form">
					<?php mbd_kpi_nonce_field( 'mbd_kpi_save_key_result' ); ?>
					<input type="hidden" name="mbd_kpi_form" value="save_key_result">
					<input type="hidden" name="objective_id" value="<?php echo (int) $selected_obj; ?>">
					<label><?php esc_html_e( 'Title', 'mbd-kpi' ); ?><input type="text" name="title" required></label>
					<div class="mbd-form-row">
						<label><?php esc_html_e( 'Target', 'mbd-kpi' ); ?><input type="number" step="any" name="target_value"></label>
						<label><?php esc_html_e( 'Current', 'mbd-kpi' ); ?><input type="number" step="any" name="current_value"></label>
						<label><?php esc_html_e( 'Unit', 'mbd-kpi' ); ?><input type="text" name="unit"></label>
					</div>
					<div class="mbd-form-row">
						<label><?php esc_html_e( 'Confidence', 'mbd-kpi' ); ?>
							<select name="confidence"><option value="high"><?php esc_html_e( 'High', 'mbd-kpi' ); ?></option><option value="medium" selected><?php esc_html_e( 'Medium', 'mbd-kpi' ); ?></option><option value="low"><?php esc_html_e( 'Low', 'mbd-kpi' ); ?></option></select>
						</label>
						<label><?php esc_html_e( 'Status', 'mbd-kpi' ); ?>
							<select name="status"><option value="on_track"><?php esc_html_e( 'On Track', 'mbd-kpi' ); ?></option><option value="at_risk"><?php esc_html_e( 'At Risk', 'mbd-kpi' ); ?></option><option value="behind"><?php esc_html_e( 'Behind', 'mbd-kpi' ); ?></option></select>
						</label>
					</div>
					<label><?php esc_html_e( 'Risk Note', 'mbd-kpi' ); ?><textarea name="risk_note" rows="2"></textarea></label>
					<label><?php esc_html_e( 'Linked KPI(s)', 'mbd-kpi' ); ?>
						<select name="linked_kpi_ids[]" multiple size="4">
							<?php foreach ( $reg_map as $id => $name ) : ?>
								<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Key Result', 'mbd-kpi' ); ?></button>
				</form>
			</details>
			<?php endif; ?>
		</section>
	<?php endif; ?>
<?php endif; ?>
