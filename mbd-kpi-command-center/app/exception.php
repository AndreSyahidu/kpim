<?php
/**
 * Exception dashboard (/kpi/exception).
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

$red = $yellow = $no_owner = $no_target = $no_actual = $stale = array();

foreach ( $registry as $reg ) {
	$pkg = $scores[ (int) $reg['id'] ];
	switch ( $pkg['status'] ) {
		case 'red':
			$red[] = $reg;
			break;
		case 'yellow':
			$yellow[] = $reg;
			break;
		case 'stale':
			$stale[] = $reg;
			break;
		case 'missing':
			$no_actual[] = $reg;
			break;
	}
	if ( ! (int) $reg['owner_employee_id'] ) {
		$no_owner[] = $reg;
	}
	if ( null === $pkg['target_value'] ) {
		$no_target[] = $reg;
	}
}

$overdue       = MBD_KPI_Action_Plan_Service::get_action_plans( array( 'overdue' => true ) );
$not_verified  = MBD_KPI_Action_Plan_Service::get_action_plans( array( 'status' => 'done_pending_verification' ) );
$rejected_ev   = MBD_KPI_Action_Plan_Service::list_evidence( 'rejected' );
$repeated_rc   = MBD_KPI_Action_Plan_Service::repeated_root_causes( 2 );

$reg_name = static function ( $reg ) {
	return $reg['kpi_code'] . ' — ' . $reg['kpi_name'];
};

/**
 * Render a simple KPI exception list section.
 *
 * @param string $title Title.
 * @param array  $rows  Registry rows.
 * @param string $period Period.
 */
$render_kpi_list = static function ( $title, $rows, $period ) use ( $reg_name ) {
	echo '<section class="mbd-card"><div class="mbd-card-head"><h3>' . esc_html( $title ) . '</h3><span class="mbd-pill mbd-pill-default">' . count( $rows ) . '</span></div>';
	if ( $rows ) {
		echo '<ul class="mbd-exc-list">';
		foreach ( $rows as $reg ) {
			$url = mbd_kpi_url( 'kpi-actual', array( 'registry_id' => (int) $reg['id'], 'period' => $period ) );
			echo '<li><a class="mbd-link" href="' . esc_url( $url ) . '">' . esc_html( $reg_name( $reg ) ) . '</a></li>';
		}
		echo '</ul>';
	} else {
		echo '<p class="mbd-muted">' . esc_html__( 'None.', 'mbd-kpi' ) . '</p>';
	}
	echo '</section>';
};
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Exception Dashboard', 'mbd-kpi' ); ?></h2>
	<p class="mbd-muted"><?php printf( esc_html__( 'Period: %s', 'mbd-kpi' ), esc_html( $period ) ); ?></p>
</div>

<div class="mbd-grid-2">
	<?php
	$render_kpi_list( __( 'Red KPIs', 'mbd-kpi' ), $red, $period );
	$render_kpi_list( __( 'Yellow KPIs', 'mbd-kpi' ), $yellow, $period );
	$render_kpi_list( __( 'KPI without owner', 'mbd-kpi' ), $no_owner, $period );
	$render_kpi_list( __( 'KPI without target', 'mbd-kpi' ), $no_target, $period );
	$render_kpi_list( __( 'KPI without recent actual', 'mbd-kpi' ), $no_actual, $period );
	$render_kpi_list( __( 'Stale KPI data', 'mbd-kpi' ), $stale, $period );
	?>

	<section class="mbd-card">
		<div class="mbd-card-head"><h3><?php esc_html_e( 'Overdue action plans', 'mbd-kpi' ); ?></h3><span class="mbd-pill mbd-pill-default"><?php echo count( $overdue ); ?></span></div>
		<?php if ( $overdue ) : ?>
			<ul class="mbd-exc-list">
				<?php foreach ( $overdue as $p ) : ?>
					<li><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => (int) $p['id'] ) ) ); ?>"><?php echo esc_html( wp_trim_words( $p['problem_statement'], 10 ) ?: ( '#' . (int) $p['id'] ) ); ?></a> <small class="mbd-muted"><?php echo esc_html( $p['due_date'] ); ?></small></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'None.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="mbd-card">
		<div class="mbd-card-head"><h3><?php esc_html_e( 'Completed but not verified', 'mbd-kpi' ); ?></h3><span class="mbd-pill mbd-pill-default"><?php echo count( $not_verified ); ?></span></div>
		<?php if ( $not_verified ) : ?>
			<ul class="mbd-exc-list">
				<?php foreach ( $not_verified as $p ) : ?>
					<li><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'action-plan', array( 'action_plan_id' => (int) $p['id'] ) ) ); ?>"><?php echo esc_html( wp_trim_words( $p['problem_statement'], 10 ) ?: ( '#' . (int) $p['id'] ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'None.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="mbd-card">
		<div class="mbd-card-head"><h3><?php esc_html_e( 'Rejected evidence', 'mbd-kpi' ); ?></h3><span class="mbd-pill mbd-pill-default"><?php echo count( $rejected_ev ); ?></span></div>
		<?php if ( $rejected_ev ) : ?>
			<ul class="mbd-exc-list">
				<?php foreach ( $rejected_ev as $ev ) : ?>
					<li><?php echo esc_html( $ev['title'] ?: ( $ev['entity_type'] . ' #' . (int) $ev['entity_id'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'None.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="mbd-card">
		<div class="mbd-card-head"><h3><?php esc_html_e( 'Repeated root causes', 'mbd-kpi' ); ?></h3><span class="mbd-pill mbd-pill-default"><?php echo count( $repeated_rc ); ?></span></div>
		<?php if ( $repeated_rc ) : ?>
			<ul class="mbd-exc-list">
				<?php foreach ( $repeated_rc as $rc ) : ?>
					<li><?php echo esc_html( $rc['category'] ); ?> <small class="mbd-muted">&times;<?php echo (int) $rc['occurrence_count']; ?></small></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'None.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>
</div>
