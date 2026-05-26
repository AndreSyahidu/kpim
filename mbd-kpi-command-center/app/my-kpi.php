<?php
/**
 * My KPI dashboard (/kpi/my).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx      = $GLOBALS['mbd_kpi_ctx'];
$period   = $ctx['period'];
$employee = $ctx['employee'];
$emp_id   = $employee ? (int) $employee['id'] : 0;
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'My KPI', 'mbd-kpi' ); ?></h2>
	<p class="mbd-muted"><?php echo $employee ? esc_html( $employee['full_name'] ) : esc_html__( 'No employee profile linked to your account.', 'mbd-kpi' ); ?></p>
</div>

<?php if ( ! $emp_id ) : ?>
	<section class="mbd-card"><p class="mbd-muted"><?php esc_html_e( 'Ask a KPI Admin to link your user account to an employee record to see personal KPIs.', 'mbd-kpi' ); ?></p></section>
	<?php return; ?>
<?php endif; ?>

<?php
$my_registry = MBD_KPI_Service::get_registry( array( 'owner_employee_id' => $emp_id, 'status' => 'active' ) );
$scores      = MBD_KPI_Score_Engine::compute_many( $my_registry, $period );
$my_actions  = MBD_KPI_Action_Plan_Service::get_action_plans( array( 'owner_employee_id' => $emp_id ) );
$my_overdue  = array_filter(
	$my_actions,
	static function ( $p ) {
		return $p['due_date'] && $p['due_date'] < gmdate( 'Y-m-d' ) && ! in_array( $p['status'], array( 'verified_effective', 'closed_ineffective' ), true );
	}
);

$score_count = 0;
$score_sum   = 0.0;
foreach ( $scores as $pkg ) {
	if ( null !== $pkg['performance_score'] && in_array( $pkg['status'], array( 'green', 'yellow', 'red' ), true ) ) {
		$score_sum += $pkg['performance_score'];
		$score_count++;
	}
}
$my_score = $score_count ? round( $score_sum / $score_count, 1 ) : null;
?>

<div class="mbd-stat-grid">
	<div class="mbd-stat-card mbd-accent-blue"><span class="mbd-stat-label"><?php esc_html_e( 'My Avg Score', 'mbd-kpi' ); ?></span><span class="mbd-stat-value"><?php echo null === $my_score ? '&mdash;' : esc_html( $my_score ); ?></span></div>
	<div class="mbd-stat-card"><span class="mbd-stat-label"><?php esc_html_e( 'My KPIs', 'mbd-kpi' ); ?></span><span class="mbd-stat-value"><?php echo count( $my_registry ); ?></span></div>
	<div class="mbd-stat-card mbd-accent-red"><span class="mbd-stat-label"><?php esc_html_e( 'Overdue Actions', 'mbd-kpi' ); ?></span><span class="mbd-stat-value"><?php echo count( $my_overdue ); ?></span></div>
	<div class="mbd-stat-card"><span class="mbd-stat-label"><?php esc_html_e( 'My Action Plans', 'mbd-kpi' ); ?></span><span class="mbd-stat-value"><?php echo count( $my_actions ); ?></span></div>
</div>

<section class="mbd-card">
	<h3><?php esc_html_e( 'My KPI Performance', 'mbd-kpi' ); ?></h3>
	<?php if ( $my_registry ) : ?>
		<table class="mbd-table">
			<thead><tr><th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Target', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Actual', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Score', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $my_registry as $reg ) :
				$pkg = $scores[ (int) $reg['id'] ]; ?>
				<tr>
					<td><?php echo esc_html( $reg['kpi_code'] ); ?></td>
					<td><?php echo esc_html( $reg['kpi_name'] ); ?></td>
					<td><?php echo null === $pkg['target_value'] ? '&mdash;' : wp_kses_post( mbd_kpi_format_value( $pkg['target_value'], $reg['unit'] ) ); ?></td>
					<td><?php echo null === $pkg['actual_value'] ? '&mdash;' : wp_kses_post( mbd_kpi_format_value( $pkg['actual_value'], $reg['unit'] ) ); ?></td>
					<td><?php echo null === $pkg['performance_score'] ? '&mdash;' : esc_html( round( (float) $pkg['performance_score'], 1 ) ); ?></td>
					<td><?php echo mbd_kpi_status_badge( $pkg['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'kpi-actual', array( 'registry_id' => (int) $reg['id'], 'period' => $period ) ) ); ?>"><?php esc_html_e( 'Input', 'mbd-kpi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="mbd-muted"><?php esc_html_e( 'No KPIs are assigned to you.', 'mbd-kpi' ); ?></p>
	<?php endif; ?>
</section>

<section class="mbd-card">
	<h3><?php esc_html_e( 'My Action Plans', 'mbd-kpi' ); ?></h3>
	<?php if ( $my_actions ) : ?>
		<table class="mbd-table">
			<thead><tr><th><?php esc_html_e( 'Problem', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Due', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php $st = mbd_kpi_action_statuses(); foreach ( $my_actions as $p ) : ?>
				<tr>
					<td><?php echo esc_html( wp_trim_words( $p['problem_statement'], 14 ) ); ?></td>
					<td><?php echo esc_html( $p['due_date'] ?: '—' ); ?></td>
					<td><?php echo mbd_kpi_pill( $st[ $p['status'] ] ?? $p['status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => (int) $p['id'] ) ) ); ?>"><?php esc_html_e( 'Open', 'mbd-kpi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="mbd-muted"><?php esc_html_e( 'No action plans assigned to you.', 'mbd-kpi' ); ?></p>
	<?php endif; ?>
</section>
