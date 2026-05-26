<?php
/**
 * Top bar partial: page title, period switcher, current user and logout.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$company_name = mbd_kpi_get_setting( 'company_name', 'MBD Kontraktor' );
$current_user = wp_get_current_user();
?>
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
