<?php
/**
 * Executive Command Center (/kpi).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx    = $GLOBALS['mbd_kpi_ctx'];
$period = $ctx['period'];

$registry = MBD_KPI_Service::get_registry( array( 'status' => 'active' ) );
$scores   = MBD_KPI_Score_Engine::compute_many( $registry, $period );

// Aggregate company + perspective scores.
$company_sum    = 0.0;
$company_weight = 0.0;
$red            = array();
$missing        = 0;
$perspectives   = array();
foreach ( mbd_kpi_bsc_perspectives() as $pk => $pl ) {
	$perspectives[ $pk ] = array( 'label' => $pl, 'sum' => 0.0, 'count' => 0 );
}

foreach ( $registry as $reg ) {
	$pkg = $scores[ (int) $reg['id'] ];
	if ( 'red' === $pkg['status'] ) {
		$red[] = array( 'reg' => $reg, 'pkg' => $pkg );
		// Anti-cosmetic rule: two consecutive red periods raise an escalation.
		if ( current_user_can( 'mbd_kpi_manage_action' ) || current_user_can( 'mbd_kpi_view_all' ) ) {
			MBD_KPI_Action_Plan_Service::maybe_escalate_consecutive_red( $reg, $period, $pkg['status'] );
		}
	}
	if ( 'missing' === $pkg['status'] ) {
		$missing++;
	}
	if ( null !== $pkg['performance_score'] && in_array( $pkg['status'], array( 'green', 'yellow', 'red' ), true ) ) {
		$w = (float) $reg['weight'];
		$company_sum    += $pkg['performance_score'] * $w;
		$company_weight += $w;
		$pk = $reg['bsc_perspective'];
		if ( isset( $perspectives[ $pk ] ) ) {
			$perspectives[ $pk ]['sum']  += $pkg['performance_score'];
			$perspectives[ $pk ]['count']++;
		}
	}
}
$company_score = $company_weight > 0 ? round( $company_sum / $company_weight, 1 ) : null;
$bsc_score     = $company_score; // BSC score uses the same weighted base in MVP.

// OKR progress.
$objectives   = MBD_KPI_OKR_Service::get_objectives( array( 'period' => $period ) );
$okr_progress = 0.0;
if ( $objectives ) {
	$sum = 0.0;
	foreach ( $objectives as $o ) {
		$sum += MBD_KPI_OKR_Service::objective_progress( (int) $o['id'] );
	}
	$okr_progress = round( $sum / count( $objectives ), 1 );
}

// Operational counters.
$overdue        = MBD_KPI_Action_Plan_Service::get_action_plans( array( 'overdue' => true ) );
$pending_ev     = MBD_KPI_Action_Plan_Service::list_evidence( 'pending' );
$repeated_rc    = MBD_KPI_Action_Plan_Service::repeated_root_causes( 2 );
$escalations    = MBD_KPI_Action_Plan_Service::get_open_escalations();

// Review completion this period.
$reviews        = MBD_KPI_Review_Service::get_reviews( array( 'period' => $period ) );
$reviews_done   = 0;
foreach ( $reviews as $rv ) {
	if ( 'closed' === $rv['status'] || 'completed' === $rv['status'] ) {
		$reviews_done++;
	}
}
$review_pct = $reviews ? round( ( $reviews_done / count( $reviews ) ) * 100 ) : 0;
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Executive Command Center', 'mbd-kpi' ); ?></h2>
	<p class="mbd-muted"><?php printf( esc_html__( 'Period: %s', 'mbd-kpi' ), esc_html( $period ) ); ?></p>
</div>

<div class="mbd-stat-grid">
	<div class="mbd-stat-card mbd-accent-blue">
		<span class="mbd-stat-label"><?php esc_html_e( 'Company Score', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo null === $company_score ? '&mdash;' : esc_html( $company_score ); ?></span>
	</div>
	<div class="mbd-stat-card">
		<span class="mbd-stat-label"><?php esc_html_e( 'BSC Score', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo null === $bsc_score ? '&mdash;' : esc_html( $bsc_score ); ?></span>
	</div>
	<div class="mbd-stat-card">
		<span class="mbd-stat-label"><?php esc_html_e( 'OKR Progress', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo esc_html( $okr_progress ); ?>%</span>
	</div>
	<div class="mbd-stat-card mbd-accent-red">
		<span class="mbd-stat-label"><?php esc_html_e( 'Red KPIs', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo count( $red ); ?></span>
	</div>
	<div class="mbd-stat-card">
		<span class="mbd-stat-label"><?php esc_html_e( 'Overdue Actions', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo count( $overdue ); ?></span>
	</div>
	<div class="mbd-stat-card">
		<span class="mbd-stat-label"><?php esc_html_e( 'Pending Evidence', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo count( $pending_ev ); ?></span>
	</div>
	<div class="mbd-stat-card">
		<span class="mbd-stat-label"><?php esc_html_e( 'Review Completion', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo esc_html( $review_pct ); ?>%</span>
	</div>
	<div class="mbd-stat-card mbd-accent-amber">
		<span class="mbd-stat-label"><?php esc_html_e( 'Repeated Root Causes', 'mbd-kpi' ); ?></span>
		<span class="mbd-stat-value"><?php echo count( $repeated_rc ); ?></span>
	</div>
</div>

<div class="mbd-grid-2">
	<section class="mbd-card">
		<h3><?php esc_html_e( 'Balanced Scorecard Snapshot', 'mbd-kpi' ); ?></h3>
		<table class="mbd-table">
			<thead><tr><th><?php esc_html_e( 'Perspective', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Avg Score', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'KPIs', 'mbd-kpi' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $perspectives as $p ) :
				$avg = $p['count'] ? round( $p['sum'] / $p['count'], 1 ) : null; ?>
				<tr>
					<td><?php echo esc_html( $p['label'] ); ?></td>
					<td><?php echo null === $avg ? '&mdash;' : esc_html( $avg ); ?></td>
					<td><?php echo (int) $p['count']; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'scorecard', array( 'period' => $period ) ) ); ?>"><?php esc_html_e( 'Open full scorecard', 'mbd-kpi' ); ?> &rarr;</a>
	</section>

	<section class="mbd-card">
		<h3><?php esc_html_e( 'Strategic Alerts', 'mbd-kpi' ); ?></h3>
		<ul class="mbd-alert-list">
			<?php if ( $missing ) : ?>
				<li class="mbd-alert mbd-alert-warn"><?php printf( esc_html__( '%d KPI(s) have no actual data this period (counted as missing, never green).', 'mbd-kpi' ), (int) $missing ); ?></li>
			<?php endif; ?>
			<?php if ( $red ) : ?>
				<li class="mbd-alert mbd-alert-danger"><?php printf( esc_html__( '%d KPI(s) are red and require an action plan.', 'mbd-kpi' ), count( $red ) ); ?></li>
			<?php endif; ?>
			<?php if ( $escalations ) : ?>
				<li class="mbd-alert mbd-alert-danger"><?php printf( esc_html__( '%d open escalation(s) need management attention.', 'mbd-kpi' ), count( $escalations ) ); ?></li>
			<?php endif; ?>
			<?php if ( $repeated_rc ) : ?>
				<li class="mbd-alert mbd-alert-warn"><?php printf( esc_html__( '%d repeated root cause(s) detected across periods.', 'mbd-kpi' ), count( $repeated_rc ) ); ?></li>
			<?php endif; ?>
			<?php if ( ! $missing && ! $red && ! $escalations && ! $repeated_rc ) : ?>
				<li class="mbd-alert mbd-alert-ok"><?php esc_html_e( 'No critical strategic alerts for this period.', 'mbd-kpi' ); ?></li>
			<?php endif; ?>
		</ul>
	</section>
</div>

<section class="mbd-card">
	<h3><?php esc_html_e( 'Red KPIs — Require Action', 'mbd-kpi' ); ?></h3>
	<?php if ( $red ) : ?>
		<table class="mbd-table">
			<thead><tr><th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Score', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $red as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r['reg']['kpi_code'] ); ?></td>
					<td><?php echo esc_html( $r['reg']['kpi_name'] ); ?></td>
					<td><?php echo esc_html( round( (float) $r['pkg']['performance_score'], 1 ) ); ?></td>
					<td><?php echo mbd_kpi_status_badge( $r['pkg']['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'registry_id' => (int) $r['reg']['id'], 'period' => $period ) ) ); ?>"><?php esc_html_e( 'Create action plan', 'mbd-kpi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="mbd-muted"><?php esc_html_e( 'No red KPIs this period.', 'mbd-kpi' ); ?></p>
	<?php endif; ?>
</section>
