<?php
/**
 * Document header partial: opens the HTML document and the app container.
 *
 * Paired with the closing markup in templates/layout.php.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$company_name = mbd_kpi_get_setting( 'company_name', 'MBD Kontraktor' );
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
