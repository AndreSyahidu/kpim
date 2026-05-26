<?php
/**
 * Period switcher control (GET ?period=).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx     = $GLOBALS['mbd_kpi_ctx'];
$current = $ctx['period'];
$options = mbd_kpi_period_options();
$locked  = MBD_KPI_Service::is_period_locked( $current );
?>
<form class="mbd-period-form" method="get" action="<?php echo esc_url( mbd_kpi_url( 'dashboard' === $ctx['page'] ? '' : $ctx['page'] ) ); ?>">
	<label for="mbd-period" class="screen-reader-text"><?php esc_html_e( 'Period', 'mbd-kpi' ); ?></label>
	<select id="mbd-period" name="period" onchange="this.form.submit()">
		<?php foreach ( $options as $opt ) : ?>
			<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $current, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php if ( $locked ) : ?>
		<span class="mbd-pill mbd-pill-locked"><?php esc_html_e( 'Locked', 'mbd-kpi' ); ?></span>
	<?php endif; ?>
</form>
