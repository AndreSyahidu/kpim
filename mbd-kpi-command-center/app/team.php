<?php
/**
 * Team & Division dashboard (/kpi/team).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$period     = $ctx['period'];
$can_manage = current_user_can( 'mbd_kpi_manage_settings' );

$divisions = MBD_KPI_Service::get_divisions();
$employees = MBD_KPI_Service::get_employees();
$emp_map   = MBD_KPI_Service::employee_map();
$roles     = MBD_KPI_Permissions::roles();

// Per-division aggregate score for this period.
$div_scores = array();
foreach ( $divisions as $d ) {
	$regs = MBD_KPI_Service::get_registry( array( 'division_id' => (int) $d['id'], 'status' => 'active' ) );
	$pkgs = MBD_KPI_Score_Engine::compute_many( $regs, $period );
	$sum  = 0.0;
	$cnt  = 0;
	$red  = 0;
	foreach ( $pkgs as $pkg ) {
		if ( null !== $pkg['performance_score'] && in_array( $pkg['status'], array( 'green', 'yellow', 'red' ), true ) ) {
			$sum += $pkg['performance_score'];
			$cnt++;
		}
		if ( 'red' === $pkg['status'] ) {
			$red++;
		}
	}
	$div_scores[ (int) $d['id'] ] = array(
		'score' => $cnt ? round( $sum / $cnt, 1 ) : null,
		'kpis'  => count( $regs ),
		'red'   => $red,
	);
}
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Team & Divisions', 'mbd-kpi' ); ?></h2>
</div>

<section class="mbd-card">
	<h3><?php esc_html_e( 'Division Dashboard', 'mbd-kpi' ); ?></h3>
	<?php if ( $divisions ) : ?>
		<table class="mbd-table">
			<thead><tr><th><?php esc_html_e( 'Division', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Head', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Avg Score', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'KPIs', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Red', 'mbd-kpi' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $divisions as $d ) :
				$s = $div_scores[ (int) $d['id'] ]; ?>
				<tr>
					<td><?php echo esc_html( $d['name'] ); ?></td>
					<td><?php echo esc_html( $d['code'] ); ?></td>
					<td><?php echo esc_html( $emp_map[ (int) $d['head_employee_id'] ] ?? '—' ); ?></td>
					<td><?php echo null === $s['score'] ? '&mdash;' : esc_html( $s['score'] ); ?></td>
					<td><?php echo (int) $s['kpis']; ?></td>
					<td><?php echo (int) $s['red']; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="mbd-muted"><?php esc_html_e( 'No divisions defined yet.', 'mbd-kpi' ); ?></p>
	<?php endif; ?>
</section>

<section class="mbd-card">
	<h3><?php esc_html_e( 'Employees', 'mbd-kpi' ); ?></h3>
	<?php if ( $employees ) : ?>
		<table class="mbd-table">
			<thead><tr><th><?php esc_html_e( 'Name', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Position', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Role', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Supervisor', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $employees as $e ) : ?>
				<tr>
					<td><?php echo esc_html( $e['full_name'] ); ?></td>
					<td><?php echo esc_html( $e['position'] ); ?></td>
					<td><?php echo esc_html( $roles[ $e['role_key'] ] ?? $e['role_key'] ); ?></td>
					<td><?php echo esc_html( $emp_map[ (int) $e['supervisor_id'] ] ?? '—' ); ?></td>
					<td><?php echo mbd_kpi_pill( $e['status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="mbd-muted"><?php esc_html_e( 'No employees yet.', 'mbd-kpi' ); ?></p>
	<?php endif; ?>
</section>

<?php if ( $can_manage ) : ?>
<div class="mbd-grid-2">
	<section class="mbd-card">
		<h3><?php esc_html_e( 'Add Division', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'team' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_division' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_division">
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Name', 'mbd-kpi' ); ?><input type="text" name="name" required></label>
				<label><?php esc_html_e( 'Code', 'mbd-kpi' ); ?><input type="text" name="code"></label>
			</div>
			<label><?php esc_html_e( 'Head', 'mbd-kpi' ); ?>
				<select name="head_employee_id">
					<option value="0">&mdash;</option>
					<?php foreach ( $emp_map as $id => $name ) : ?>
						<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Description', 'mbd-kpi' ); ?><textarea name="description" rows="2"></textarea></label>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Division', 'mbd-kpi' ); ?></button>
		</form>
	</section>

	<section class="mbd-card">
		<h3><?php esc_html_e( 'Add Employee', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'team' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_employee' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_employee">
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Full Name', 'mbd-kpi' ); ?><input type="text" name="full_name" required></label>
				<label><?php esc_html_e( 'Email', 'mbd-kpi' ); ?><input type="email" name="email"></label>
			</div>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'WP User ID', 'mbd-kpi' ); ?><input type="number" name="user_id" placeholder="0"></label>
				<label><?php esc_html_e( 'Position', 'mbd-kpi' ); ?><input type="text" name="position"></label>
			</div>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Division', 'mbd-kpi' ); ?>
					<select name="division_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $divisions as $d ) : ?>
							<option value="<?php echo (int) $d['id']; ?>"><?php echo esc_html( $d['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Role', 'mbd-kpi' ); ?>
					<select name="role_key">
						<?php foreach ( $roles as $rk => $rl ) : ?>
							<option value="<?php echo esc_attr( $rk ); ?>"><?php echo esc_html( $rl ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Supervisor', 'mbd-kpi' ); ?>
					<select name="supervisor_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $emp_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Employee', 'mbd-kpi' ); ?></button>
		</form>
	</section>
</div>
<?php endif; ?>
