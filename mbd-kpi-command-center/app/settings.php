<?php
/**
 * Front-end settings & administration (/kpi/settings).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'mbd_kpi_manage_settings' ) ) {
	echo '<section class="mbd-card"><p class="mbd-muted">' . esc_html__( 'You do not have access to settings.', 'mbd-kpi' ) . '</p></section>';
	return;
}

global $wpdb;
$ctx     = $GLOBALS['mbd_kpi_ctx'];
$period  = $ctx['period'];
$locks   = (array) $wpdb->get_results( 'SELECT * FROM ' . MBD_KPI_DB::table( 'period_locks' ) . ' ORDER BY period DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
$audit   = current_user_can( 'mbd_kpi_view_audit' ) ? MBD_KPI_Audit_Log::recent( 40 ) : array();
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Settings & Administration', 'mbd-kpi' ); ?></h2>
</div>

<div class="mbd-grid-2">
	<section class="mbd-card">
		<h3><?php esc_html_e( 'Scoring & Formula', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'settings' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_settings' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_settings">
			<label><?php esc_html_e( 'Company Name', 'mbd-kpi' ); ?><input type="text" name="company_name" value="<?php echo esc_attr( mbd_kpi_get_setting( 'company_name', 'MBD Kontraktor' ) ); ?>"></label>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Score Cap', 'mbd-kpi' ); ?><input type="number" step="any" name="score_cap" value="<?php echo esc_attr( mbd_kpi_get_setting( 'score_cap', 120 ) ); ?>"></label>
				<label><?php esc_html_e( 'Default Green', 'mbd-kpi' ); ?><input type="number" step="any" name="default_threshold_green" value="<?php echo esc_attr( mbd_kpi_get_setting( 'default_threshold_green', 100 ) ); ?>"></label>
				<label><?php esc_html_e( 'Default Yellow', 'mbd-kpi' ); ?><input type="number" step="any" name="default_threshold_yellow" value="<?php echo esc_attr( mbd_kpi_get_setting( 'default_threshold_yellow', 80 ) ); ?>"></label>
			</div>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Settings', 'mbd-kpi' ); ?></button>
		</form>
	</section>

	<section class="mbd-card">
		<h3><?php esc_html_e( 'Period Locks', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'settings' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_lock_period' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="lock_period">
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Period', 'mbd-kpi' ); ?>
					<select name="period">
						<?php foreach ( mbd_kpi_period_options() as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $period, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<label><?php esc_html_e( 'Lock Note', 'mbd-kpi' ); ?><input type="text" name="note"></label>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Lock Period & Snapshot', 'mbd-kpi' ); ?></button>
		</form>

		<?php if ( $locks ) : ?>
			<table class="mbd-table">
				<thead><tr><th><?php esc_html_e( 'Period', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Type', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Locked At', 'mbd-kpi' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $locks as $l ) : ?>
					<tr>
						<td><?php echo esc_html( $l['period'] ); ?></td>
						<td><?php echo esc_html( $l['period_type'] ); ?></td>
						<td><?php echo esc_html( $l['locked_at'] ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'settings' ) ); ?>" class="mbd-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Unlock this period?', 'mbd-kpi' ) ); ?>');">
								<?php mbd_kpi_nonce_field( 'mbd_kpi_unlock_period' ); ?>
								<input type="hidden" name="mbd_kpi_form" value="unlock_period">
								<input type="hidden" name="period" value="<?php echo esc_attr( $l['period'] ); ?>">
								<button type="submit" class="mbd-btn-mini mbd-btn-danger"><?php esc_html_e( 'Unlock', 'mbd-kpi' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'No locked periods.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>
</div>

<?php if ( $audit ) : ?>
<section class="mbd-card">
	<h3><?php esc_html_e( 'Audit Log (recent)', 'mbd-kpi' ); ?></h3>
	<table class="mbd-table">
		<thead><tr><th><?php esc_html_e( 'When', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'User', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Action', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Object', 'mbd-kpi' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $audit as $row ) :
			$user = get_userdata( (int) $row['user_id'] ); ?>
			<tr>
				<td><?php echo esc_html( $row['created_at'] ); ?></td>
				<td><?php echo esc_html( $user ? $user->display_name : ( '#' . (int) $row['user_id'] ) ); ?></td>
				<td><?php echo esc_html( $row['action'] ); ?></td>
				<td><?php echo esc_html( $row['object_type'] . ' #' . (int) $row['object_id'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</section>
<?php endif; ?>
