<?php
/**
 * Balanced Scorecard view (/kpi/scorecard).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx       = $GLOBALS['mbd_kpi_ctx'];
$period    = $ctx['period'];
$framework = isset( $_GET['fw'] ) && 'pillar' === $_GET['fw'] ? 'pillar' : 'bsc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$registry = MBD_KPI_Service::get_registry( array( 'status' => 'active' ) );
$scores   = MBD_KPI_Score_Engine::compute_many( $registry, $period );

$groups = ( 'pillar' === $framework ) ? mbd_kpi_strategic_pillars() : mbd_kpi_bsc_perspectives();
$field  = ( 'pillar' === $framework ) ? 'strategic_pillar' : 'bsc_perspective';

$buckets = array();
foreach ( $groups as $k => $label ) {
	$buckets[ $k ] = array( 'label' => $label, 'rows' => array(), 'sum' => 0.0, 'count' => 0 );
}
$buckets['_unassigned'] = array( 'label' => __( 'Unassigned', 'mbd-kpi' ), 'rows' => array(), 'sum' => 0.0, 'count' => 0 );

foreach ( $registry as $reg ) {
	$pkg = $scores[ (int) $reg['id'] ];
	$key = isset( $buckets[ $reg[ $field ] ] ) ? $reg[ $field ] : '_unassigned';
	$buckets[ $key ]['rows'][] = array( 'reg' => $reg, 'pkg' => $pkg );
	if ( null !== $pkg['performance_score'] && in_array( $pkg['status'], array( 'green', 'yellow', 'red' ), true ) ) {
		$buckets[ $key ]['sum'] += $pkg['performance_score'];
		$buckets[ $key ]['count']++;
	}
}
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Balanced Scorecard', 'mbd-kpi' ); ?></h2>
	<div class="mbd-toggle">
		<a class="mbd-toggle-btn <?php echo 'bsc' === $framework ? 'is-active' : ''; ?>" href="<?php echo esc_url( mbd_kpi_url( 'scorecard', array( 'fw' => 'bsc', 'period' => $period ) ) ); ?>"><?php esc_html_e( 'BSC Perspectives', 'mbd-kpi' ); ?></a>
		<a class="mbd-toggle-btn <?php echo 'pillar' === $framework ? 'is-active' : ''; ?>" href="<?php echo esc_url( mbd_kpi_url( 'scorecard', array( 'fw' => 'pillar', 'period' => $period ) ) ); ?>"><?php esc_html_e( 'Strategic Pillars', 'mbd-kpi' ); ?></a>
	</div>
</div>

<?php foreach ( $buckets as $key => $b ) :
	if ( empty( $b['rows'] ) ) {
		continue;
	}
	$avg = $b['count'] ? round( $b['sum'] / $b['count'], 1 ) : null; ?>
	<section class="mbd-card">
		<div class="mbd-card-head">
			<h3><?php echo esc_html( $b['label'] ); ?></h3>
			<span class="mbd-pill mbd-pill-default"><?php esc_html_e( 'Avg', 'mbd-kpi' ); ?>: <?php echo null === $avg ? '&mdash;' : esc_html( $avg ); ?></span>
		</div>
		<table class="mbd-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th>
					<th><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?></th>
					<th><?php esc_html_e( 'Target', 'mbd-kpi' ); ?></th>
					<th><?php esc_html_e( 'Actual', 'mbd-kpi' ); ?></th>
					<th><?php esc_html_e( 'Score', 'mbd-kpi' ); ?></th>
					<th><?php esc_html_e( 'Health', 'mbd-kpi' ); ?></th>
					<th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $b['rows'] as $row ) :
				$reg = $row['reg'];
				$pkg = $row['pkg']; ?>
				<tr>
					<td><?php echo esc_html( $reg['kpi_code'] ); ?></td>
					<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'kpi-actual', array( 'registry_id' => (int) $reg['id'], 'period' => $period ) ) ); ?>"><?php echo esc_html( $reg['kpi_name'] ); ?></a></td>
					<td><?php echo null === $pkg['target_value'] ? '&mdash;' : wp_kses_post( mbd_kpi_format_value( $pkg['target_value'], $reg['unit'] ) ); ?></td>
					<td><?php echo null === $pkg['actual_value'] ? '&mdash;' : wp_kses_post( mbd_kpi_format_value( $pkg['actual_value'], $reg['unit'] ) ); ?></td>
					<td><?php echo null === $pkg['performance_score'] ? '&mdash;' : esc_html( round( (float) $pkg['performance_score'], 1 ) ); ?></td>
					<td><?php echo esc_html( round( (float) $pkg['data_health_score'], 0 ) ); ?></td>
					<td><?php echo mbd_kpi_status_badge( $pkg['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>
<?php endforeach; ?>
