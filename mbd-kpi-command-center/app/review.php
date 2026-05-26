<?php
/**
 * Review Room (/kpi/review).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$period     = $ctx['period'];
$can_manage = current_user_can( 'mbd_kpi_manage_review' );

$emp_map = MBD_KPI_Service::employee_map();
$types   = mbd_kpi_review_types();
$reviews = MBD_KPI_Review_Service::get_reviews();

$review_id = isset( $_GET['review_id'] ) ? (int) $_GET['review_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$review    = $review_id ? MBD_KPI_Review_Service::get_review( $review_id ) : null;
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Review Room', 'mbd-kpi' ); ?></h2>
</div>

<div class="mbd-grid-2">
	<section class="mbd-card">
		<h3><?php esc_html_e( 'Review Sessions', 'mbd-kpi' ); ?></h3>
		<?php if ( $reviews ) : ?>
			<table class="mbd-table">
				<thead><tr><th><?php esc_html_e( 'Title', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Type', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Period', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Date', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $reviews as $r ) : ?>
					<tr class="<?php echo $review_id === (int) $r['id'] ? 'is-selected' : ''; ?>">
						<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'review', array( 'review_id' => (int) $r['id'] ) ) ); ?>"><?php echo esc_html( $r['title'] ); ?></a></td>
						<td><?php echo esc_html( $types[ $r['review_type'] ] ?? $r['review_type'] ); ?></td>
						<td><?php echo esc_html( $r['period'] ); ?></td>
						<td><?php echo esc_html( $r['scheduled_date'] ?: '—' ); ?></td>
						<td><?php echo mbd_kpi_pill( $r['status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'No review sessions yet.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>
	</section>

	<?php if ( $can_manage ) : ?>
	<section class="mbd-card">
		<h3><?php esc_html_e( 'New Review Session', 'mbd-kpi' ); ?></h3>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'review' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_review' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_review">
			<label><?php esc_html_e( 'Title', 'mbd-kpi' ); ?><input type="text" name="title" required></label>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Type', 'mbd-kpi' ); ?>
					<select name="review_type">
						<?php foreach ( $types as $k => $l ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Period', 'mbd-kpi' ); ?>
					<select name="period">
						<?php foreach ( mbd_kpi_period_options() as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $period, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Scheduled Date', 'mbd-kpi' ); ?><input type="date" name="scheduled_date"></label>
				<label><?php esc_html_e( 'Facilitator', 'mbd-kpi' ); ?>
					<select name="facilitator_employee_id">
						<option value="0">&mdash;</option>
						<?php foreach ( $emp_map as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Create Review', 'mbd-kpi' ); ?></button>
		</form>
	</section>
	<?php endif; ?>
</div>

<?php if ( $review ) :
	$decisions = MBD_KPI_Review_Service::get_decisions( $review_id );
	$items     = MBD_KPI_Review_Service::get_items( $review_id ); ?>
	<section class="mbd-card">
		<h3><?php echo esc_html( $review['title'] ); ?> <span class="mbd-pill mbd-pill-default"><?php echo esc_html( $types[ $review['review_type'] ] ?? $review['review_type'] ); ?></span></h3>

		<h4><?php esc_html_e( 'Decisions & Action Items', 'mbd-kpi' ); ?></h4>
		<?php if ( $decisions ) : ?>
			<table class="mbd-table">
				<thead><tr><th><?php esc_html_e( 'Decision', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Action Item', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Due', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Escalation', 'mbd-kpi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $decisions as $d ) : ?>
					<tr>
						<td><?php echo esc_html( $d['decision_text'] ); ?></td>
						<td><?php echo esc_html( $d['action_item'] ); ?></td>
						<td><?php echo esc_html( $emp_map[ (int) $d['owner_employee_id'] ] ?? '—' ); ?></td>
						<td><?php echo esc_html( $d['due_date'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $d['escalation_path'] ?: '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="mbd-muted"><?php esc_html_e( 'No decisions recorded yet.', 'mbd-kpi' ); ?></p>
		<?php endif; ?>

		<?php if ( $can_manage ) : ?>
			<details class="mbd-details" open>
				<summary><?php esc_html_e( 'Record Decision', 'mbd-kpi' ); ?></summary>
				<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'review', array( 'review_id' => $review_id ) ) ); ?>" class="mbd-form">
					<?php mbd_kpi_nonce_field( 'mbd_kpi_add_review_decision' ); ?>
					<input type="hidden" name="mbd_kpi_form" value="add_review_decision">
					<input type="hidden" name="review_id" value="<?php echo (int) $review_id; ?>">
					<label><?php esc_html_e( 'Decision', 'mbd-kpi' ); ?><textarea name="decision_text" rows="2" required></textarea></label>
					<label><?php esc_html_e( 'Action Item', 'mbd-kpi' ); ?><textarea name="action_item" rows="2"></textarea></label>
					<div class="mbd-form-row">
						<label><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?>
							<select name="owner_employee_id">
								<option value="0">&mdash;</option>
								<?php foreach ( $emp_map as $id => $name ) : ?>
									<option value="<?php echo (int) $id; ?>"><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label><?php esc_html_e( 'Due Date', 'mbd-kpi' ); ?><input type="date" name="due_date"></label>
						<label><?php esc_html_e( 'Escalation Path', 'mbd-kpi' ); ?><input type="text" name="escalation_path" placeholder="<?php esc_attr_e( 'e.g. Supervisor → Division Head', 'mbd-kpi' ); ?>"></label>
					</div>
					<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Record Decision', 'mbd-kpi' ); ?></button>
				</form>
			</details>
		<?php endif; ?>
	</section>
<?php endif; ?>
