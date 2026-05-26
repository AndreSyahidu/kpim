<?php
/**
 * Centralised asset registration / enqueueing.
 *
 * Front-end assets are only loaded inside the /kpi application (enqueued on
 * demand by the router) so they never leak into the rest of the site.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Assets {

	const HANDLE = 'mbd-kpi-app';

	/**
	 * Enqueue the front-end application CSS/JS.
	 *
	 * Called by the router immediately before rendering the /kpi layout.
	 *
	 * @return void
	 */
	public static function enqueue_app() {
		wp_enqueue_style(
			self::HANDLE,
			MBD_KPI_URL . 'assets/css/app.css',
			array(),
			MBD_KPI_VERSION
		);
		wp_enqueue_script(
			self::HANDLE,
			MBD_KPI_URL . 'assets/js/app.js',
			array(),
			MBD_KPI_VERSION,
			true
		);
	}
}
