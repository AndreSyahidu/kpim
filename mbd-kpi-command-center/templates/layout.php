<?php
/**
 * App shell layout for the /kpi front-end.
 *
 * Composed from the header, sidebar and topbar partials.
 *
 * @package MBD_KPI
 *
 * @var array $ctx Exposed via $GLOBALS['mbd_kpi_ctx'].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx          = $GLOBALS['mbd_kpi_ctx'];
$company_name = mbd_kpi_get_setting( 'company_name', 'MBD Kontraktor' );

include MBD_KPI_DIR . 'templates/partials/header.php';
include MBD_KPI_DIR . 'templates/partials/sidebar.php';
?>
	<main class="mbd-main">
		<?php include MBD_KPI_DIR . 'templates/partials/topbar.php'; ?>

		<?php if ( ! empty( $ctx['flash'] ) ) : ?>
			<div class="mbd-flash mbd-flash-<?php echo esc_attr( $ctx['flash']['type'] ); ?>">
				<?php echo esc_html( $ctx['flash']['message'] ); ?>
			</div>
		<?php endif; ?>

		<div class="mbd-content">
			<?php
			if ( ! empty( $ctx['view_file'] ) && file_exists( $ctx['view_file'] ) ) {
				include $ctx['view_file'];
			} else {
				echo '<p>' . esc_html__( 'View not found.', 'mbd-kpi' ) . '</p>';
			}
			?>
		</div>

		<footer class="mbd-footer">
			<span><?php echo esc_html( $company_name ); ?> &middot; <?php esc_html_e( 'Performance Operating System', 'mbd-kpi' ); ?></span>
		</footer>
	</main>
</div>
<?php wp_footer(); ?>
</body>
</html>
