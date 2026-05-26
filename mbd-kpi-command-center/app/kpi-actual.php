<?php
/**
 * KPI Actuals input (/kpi/kpi-actual).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$period     = $ctx['period'];
$can_input  = current_user_can( 'mbd_kpi_input_actual' );
$can_verify = current_user_can( 'mbd_kpi_verify' );
$can_target = current_user_can( 'mbd_kpi_manage_registry' );
$locked     = MBD_KPI_Service::is_period_locked( $period );

$registry = MBD_KPI_Service::get_registry( array( 'status' => 'active' ) );
$scores   = MBD_KPI_Score_Engine::compute_many( $registry, $period );

$selected_id = isset( $_GET['registry_id'] ) ? (int) $_GET['registry_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$selected    = $selected_id ? MBD_KPI_Service::get_registry_item( $selected_id ) : null;
if ( $selected && ! MBD_KPI_Permissions::can_view_registry( $selected ) ) {
	$selected = null;
}
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'KPI Actuals', 'mbd-kpi' ); ?></h2>
	<p class="mbd-muted"><?php printf( esc_html__( 'Period: %s', 'mbd-kpi' ), esc_html( $period ) ); ?> <?php echo $locked ? mbd_kpi_pill( __( 'Locked', 'mbd-kpi' ), 'locked' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
</div>

<section class="mbd-card">
	<table class="mbd-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Target', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Actual', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Score', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Verification', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $registry ) : ?>
			<?php foreach ( $registry as $reg ) :
				$pkg    = $scores[ (int) $reg['id'] ];
				$actual = MBD_KPI_Service::get_actual( (int) $reg['id'], $period );
				$vstat  = $actual ? $actual['verification_status'] : '';
				?>
				<tr>
					<td><?php echo esc_html( $reg['kpi_code'] ); ?></td>
					<td><?php echo esc_html( $reg['kpi_name'] ); ?>
						<?php if ( $actual && (int) $actual['is_manual'] ) : ?><br><small class="mbd-pill mbd-pill-default"><?php esc_html_e( 'Manual', 'mbd-kpi' ); ?></small><?php endif; ?>
					</td>
					<td><?php echo null === $pkg['target_value'] ? '&mdash;' : wp_kses_post( mbd_kpi_format_value( $pkg['target_value'], $reg['unit'] ) ); ?></td>
					<td><?php echo null === $pkg['actual_value'] ? '&mdash;' : wp_kses_post( mbd_kpi_format_value( $pkg['actual_value'], $reg['unit'] ) ); ?></td>
					<td><?php echo null === $pkg['performance_score'] ? '&mdash;' : esc_html( round( (float) $pkg['performance_score'], 1 ) ); ?></td>
					<td>
						<?php echo $vstat ? mbd_kpi_pill( mbd_kpi_verification_statuses()[ $vstat ] ?? $vstat, 'default' ) : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $can_verify && $actual && 'pending' === $vstat ) : ?>
							<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'kpi-actual', array( 'period' => $period ) ) ); ?>" class="mbd-inline-form">
								<?php mbd_kpi_nonce_field( 'mbd_kpi_verify_actual' ); ?>
								<input type="hidden" name="mbd_kpi_form" value="verify_actual">
								<input type="hidden" name="actual_id" value="<?php echo (int) $actual['id']; ?>">
								<button name="verification_status" value="approved" class="mbd-btn-mini mbd-btn-ok"><?php esc_html_e( 'Approve', 'mbd-kpi' ); ?></button>
								<button name="verification_status" value="rejected" class="mbd-btn-mini mbd-btn-danger"><?php esc_html_e( 'Reject', 'mbd-kpi' ); ?></button>
							</form>
						<?php endif; ?>
					</td>
					<td><?php echo mbd_kpi_status_badge( $pkg['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'kpi-actual', array( 'registry_id' => (int) $reg['id'], 'period' => $period ) ) ); ?>"><?php esc_html_e( 'Input', 'mbd-kpi' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="8" class="mbd-muted"><?php esc_html_e( 'No KPIs available in your scope.', 'mbd-kpi' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</section>

<?php if ( $selected && $can_input ) :
	$cur_actual = MBD_KPI_Service::get_actual( $selected_id, $period );
	$cur_target = MBD_KPI_Service::get_target( $selected_id, $period ); ?>
	<section class="mbd-card">
		<h3><?php printf( esc_html__( 'Input Actual — %s', 'mbd-kpi' ), esc_html( $selected['kpi_name'] ) ); ?></h3>

		<?php if ( $locked ) : ?>
			<p class="mbd-alert mbd-alert-warn"><?php esc_html_e( 'This period is locked. Actuals cannot be edited directly.', 'mbd-kpi' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'kpi-actual', array( 'registry_id' => $selected_id, 'period' => $period ) ) ); ?>" class="mbd-form">
				<?php mbd_kpi_nonce_field( 'mbd_kpi_save_actual' ); ?>
				<input type="hidden" name="mbd_kpi_form" value="save_actual">
				<input type="hidden" name="registry_id" value="<?php echo (int) $selected_id; ?>">
				<input type="hidden" name="period" value="<?php echo esc_attr( $period ); ?>">
				<div class="mbd-form-row">
					<label><?php esc_html_e( 'Actual Value', 'mbd-kpi' ); ?><input type="number" step="any" name="actual_value" value="<?php echo esc_attr( $cur_actual['actual_value'] ?? '' ); ?>" required></label>
					<label><?php esc_html_e( 'Unit', 'mbd-kpi' ); ?><input type="text" value="<?php echo esc_attr( $selected['unit'] ); ?>" disabled></label>
				</div>
				<label><?php esc_html_e( 'Note', 'mbd-kpi' ); ?><textarea name="note" rows="2"><?php echo esc_textarea( $cur_actual['note'] ?? '' ); ?></textarea></label>
				<p class="mbd-muted"><?php esc_html_e( 'Submissions are recorded as manual entries and reset verification to pending.', 'mbd-kpi' ); ?></p>
				<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Submit Actual', 'mbd-kpi' ); ?></button>
			</form>
		<?php endif; ?>

		<?php if ( $can_target ) : ?>
			<details class="mbd-details" <?php echo $cur_target ? '' : 'open'; ?>>
				<summary><?php esc_html_e( 'Set Target for this period', 'mbd-kpi' ); ?></summary>
				<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'kpi-actual', array( 'registry_id' => $selected_id, 'period' => $period ) ) ); ?>" class="mbd-form">
					<?php mbd_kpi_nonce_field( 'mbd_kpi_save_target' ); ?>
					<input type="hidden" name="mbd_kpi_form" value="save_target">
					<input type="hidden" name="registry_id" value="<?php echo (int) $selected_id; ?>">
					<input type="hidden" name="period" value="<?php echo esc_attr( $period ); ?>">
					<div class="mbd-form-row">
						<label><?php esc_html_e( 'Target Value', 'mbd-kpi' ); ?><input type="number" step="any" name="target_value" value="<?php echo esc_attr( $cur_target['target_value'] ?? '' ); ?>" required></label>
						<label><?php esc_html_e( 'Stretch (optional)', 'mbd-kpi' ); ?><input type="number" step="any" name="stretch_value" value="<?php echo esc_attr( $cur_target['stretch_value'] ?? '' ); ?>"></label>
					</div>
					<label><?php esc_html_e( 'Note', 'mbd-kpi' ); ?><input type="text" name="note" value="<?php echo esc_attr( $cur_target['note'] ?? '' ); ?>"></label>
					<?php if ( $locked ) : ?>
						<label><?php esc_html_e( 'Adjustment Log (required for locked period)', 'mbd-kpi' ); ?><textarea name="adjustment_log" rows="2"></textarea></label>
					<?php endif; ?>
					<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Target', 'mbd-kpi' ); ?></button>
				</form>
			</details>
		<?php endif; ?>
	</section>
<?php endif; ?>
