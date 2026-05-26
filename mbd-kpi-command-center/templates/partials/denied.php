<?php
/**
 * Access-denied screen for authenticated users without KPI access.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php esc_html_e( 'Access Denied', 'mbd-kpi' ); ?></title>
	<?php wp_head(); ?>
</head>
<body class="mbd-kpi-body mbd-denied-body">
	<div class="mbd-denied">
		<h1><?php esc_html_e( 'Access Denied', 'mbd-kpi' ); ?></h1>
		<p><?php esc_html_e( 'Your account does not have permission to access the MBD KPI Command Center. Please contact your administrator if you believe this is a mistake.', 'mbd-kpi' ); ?></p>
		<p><a class="mbd-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return to site', 'mbd-kpi' ); ?></a></p>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
