<?php
/**
 * Evidence Center (/kpi/evidence).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$can_verify = current_user_can( 'mbd_kpi_verify' );

$filter = isset( $_GET['vstatus'] ) ? sanitize_text_field( wp_unslash( $_GET['vstatus'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$items  = MBD_KPI_Action_Plan_Service::list_evidence( $filter );
$vstats = mbd_kpi_verification_statuses();
$etypes = mbd_kpi_evidence_types();
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'Evidence Center', 'mbd-kpi' ); ?></h2>
	<div class="mbd-toggle">
		<a class="mbd-toggle-btn <?php echo '' === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url( mbd_kpi_url( 'evidence' ) ); ?>"><?php esc_html_e( 'All', 'mbd-kpi' ); ?></a>
		<?php foreach ( $vstats as $k => $l ) : ?>
			<a class="mbd-toggle-btn <?php echo $filter === $k ? 'is-active' : ''; ?>" href="<?php echo esc_url( mbd_kpi_url( 'evidence', array( 'vstatus' => $k ) ) ); ?>"><?php echo esc_html( $l ); ?></a>
		<?php endforeach; ?>
	</div>
</div>

<section class="mbd-card">
	<table class="mbd-table">
		<thead><tr><th><?php esc_html_e( 'Title', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Type', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Entity', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Link', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Verification', 'mbd-kpi' ); ?></th><?php if ( $can_verify ) : ?><th></th><?php endif; ?></tr></thead>
		<tbody>
		<?php if ( $items ) : ?>
			<?php foreach ( $items as $ev ) :
				$link = $ev['file_url'] ?: $ev['link_url']; ?>
				<tr>
					<td><?php echo esc_html( $ev['title'] ?: '—' ); ?></td>
					<td><?php echo esc_html( $etypes[ $ev['evidence_type'] ] ?? $ev['evidence_type'] ); ?></td>
					<td><?php echo esc_html( $ev['entity_type'] . ' #' . (int) $ev['entity_id'] ); ?></td>
					<td><?php echo $link ? '<a class="mbd-link" target="_blank" rel="noopener" href="' . esc_url( $link ) . '">' . esc_html__( 'View', 'mbd-kpi' ) . '</a>' : '&mdash;'; ?></td>
					<td><?php echo mbd_kpi_pill( $vstats[ $ev['verification_status'] ] ?? $ev['verification_status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<?php if ( $can_verify ) : ?>
						<td>
							<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'evidence', array( 'vstatus' => $filter ) ) ); ?>" class="mbd-inline-form">
								<?php mbd_kpi_nonce_field( 'mbd_kpi_verify_evidence' ); ?>
								<input type="hidden" name="mbd_kpi_form" value="verify_evidence">
								<input type="hidden" name="evidence_id" value="<?php echo (int) $ev['id']; ?>">
								<select name="verification_status">
									<?php foreach ( $vstats as $k => $l ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $ev['verification_status'], $k ); ?>><?php echo esc_html( $l ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="submit" class="mbd-btn-mini"><?php esc_html_e( 'Set', 'mbd-kpi' ); ?></button>
							</form>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="6" class="mbd-muted"><?php esc_html_e( 'No evidence found.', 'mbd-kpi' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</section>
