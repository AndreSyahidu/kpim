<?php
/**
 * Sidebar navigation partial.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx     = $GLOBALS['mbd_kpi_ctx'];
$current = $ctx['page'];

$items = array(
	'dashboard'    => array( __( 'Command Center', 'mbd-kpi' ), '' ),
	'scorecard'    => array( __( 'Balanced Scorecard', 'mbd-kpi' ), 'scorecard' ),
	'okr'          => array( __( 'OKR', 'mbd-kpi' ), 'okr' ),
	'kpi-registry' => array( __( 'KPI Registry', 'mbd-kpi' ), 'kpi-registry' ),
	'kpi-actual'   => array( __( 'KPI Actuals', 'mbd-kpi' ), 'kpi-actual' ),
	'action-plan'  => array( __( 'Action Plans', 'mbd-kpi' ), 'action-plan' ),
	'evidence'     => array( __( 'Evidence Center', 'mbd-kpi' ), 'evidence' ),
	'review'       => array( __( 'Review Room', 'mbd-kpi' ), 'review' ),
	'exception'    => array( __( 'Exceptions', 'mbd-kpi' ), 'exception' ),
	'my'           => array( __( 'My KPI', 'mbd-kpi' ), 'my' ),
	'team'         => array( __( 'Team & Divisions', 'mbd-kpi' ), 'team' ),
	'settings'     => array( __( 'Settings', 'mbd-kpi' ), 'settings' ),
);

// Pages requiring elevated capability are hidden if not permitted.
$gated = array(
	'settings' => 'mbd_kpi_manage_settings',
	'team'     => 'mbd_kpi_view_division',
);
?>
<nav class="mbd-sidebar" aria-label="<?php esc_attr_e( 'KPI navigation', 'mbd-kpi' ); ?>">
	<div class="mbd-brand">
		<a href="<?php echo esc_url( mbd_kpi_url() ); ?>">MBD <strong>KPI</strong></a>
		<button type="button" class="mbd-nav-toggle" aria-label="<?php esc_attr_e( 'Toggle navigation', 'mbd-kpi' ); ?>">&#9776;</button>
	</div>
	<ul class="mbd-nav">
		<?php
		foreach ( $items as $key => $item ) :
			if ( isset( $gated[ $key ] ) && ! current_user_can( $gated[ $key ] ) ) {
				continue;
			}
			$active = ( $current === $key ) ? ' is-active' : '';
			?>
			<li class="mbd-nav-item<?php echo esc_attr( $active ); ?>">
				<a href="<?php echo esc_url( mbd_kpi_url( $item[1] ) ); ?>"><?php echo esc_html( $item[0] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
