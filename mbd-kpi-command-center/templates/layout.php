<?php
/**
 * App shell layout for the /kpi front-end.
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
$current_user = wp_get_current_user();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $company_name ); ?> &middot; KPI Command Center</title>
	<?php wp_head(); ?>
</head>
<body class="mbd-kpi-body">
<div class="mbd-app">

	<?php include MBD_KPI_DIR . 'templates/partials/nav.php'; ?>

	<main class="mbd-main">
		<header class="mbd-topbar">
			<div class="mbd-topbar-title">
				<h1><?php echo esc_html( $company_name ); ?> <span>KPI Command Center</span></h1>
			</div>
			<div class="mbd-topbar-meta">
				<?php include MBD_KPI_DIR . 'templates/partials/period-switcher.php'; ?>
				<span class="mbd-user">
					<?php echo esc_html( $current_user->display_name ); ?>
					<a class="mbd-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'mbd-kpi' ); ?></a>
				</span>
			</div>
		</header>

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
