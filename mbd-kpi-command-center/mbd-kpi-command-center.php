<?php
/**
 * Plugin Name:       MBD KPI Command Center
 * Plugin URI:        https://mbdkontraktor.local/kpi
 * Description:        Private performance operating system for MBD Kontraktor. Connects Balanced Scorecard, OKR, KPI, action plans, evidence, review cadence and management decisions. Front-end app served at /kpi.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MBD Kontraktor
 * License:           GPL-2.0-or-later
 * Text Domain:       mbd-kpi
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBD_KPI_VERSION', '1.0.0' );
define( 'MBD_KPI_FILE', __FILE__ );
define( 'MBD_KPI_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBD_KPI_URL', plugin_dir_url( __FILE__ ) );
define( 'MBD_KPI_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Core bootstrap loader.
 *
 * Pulls in every service class and wires the runtime hooks.
 */
require_once MBD_KPI_DIR . 'includes/helpers.php';
require_once MBD_KPI_DIR . 'includes/class-db.php';
require_once MBD_KPI_DIR . 'includes/class-permissions.php';
require_once MBD_KPI_DIR . 'includes/class-audit-log.php';
require_once MBD_KPI_DIR . 'includes/class-assets.php';
require_once MBD_KPI_DIR . 'includes/class-score-engine.php';
require_once MBD_KPI_DIR . 'includes/class-kpi-service.php';
require_once MBD_KPI_DIR . 'includes/class-okr-service.php';
require_once MBD_KPI_DIR . 'includes/class-action-plan-service.php';
require_once MBD_KPI_DIR . 'includes/class-review-service.php';
require_once MBD_KPI_DIR . 'includes/class-activator.php';
require_once MBD_KPI_DIR . 'includes/class-router.php';

/**
 * Activation / deactivation hooks.
 */
register_activation_hook( __FILE__, array( 'MBD_KPI_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MBD_KPI_Activator', 'deactivate' ) );

/**
 * Boot the plugin once WordPress is loaded.
 */
function mbd_kpi_boot() {
	// Front-end router (rewrite rules, query vars, /kpi dispatch).
	$router = new MBD_KPI_Router();
	$router->init();

	// Admin settings screens.
	if ( is_admin() ) {
		require_once MBD_KPI_DIR . 'admin/settings-page.php';
		$admin = new MBD_KPI_Admin_Settings();
		$admin->init();
	}
}
add_action( 'plugins_loaded', 'mbd_kpi_boot' );

/**
 * Safety net: make sure rewrite rules exist if the activation hook was missed
 * (e.g. plugin files dropped in via deployment rather than the activator).
 */
function mbd_kpi_maybe_flush_rewrites() {
	if ( get_option( 'mbd_kpi_flush_needed' ) ) {
		flush_rewrite_rules();
		delete_option( 'mbd_kpi_flush_needed' );
	}
}
add_action( 'wp_loaded', 'mbd_kpi_maybe_flush_rewrites' );
