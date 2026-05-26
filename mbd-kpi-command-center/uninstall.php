<?php
/**
 * Uninstall routine.
 *
 * Removes custom roles/capabilities and plugin option flags. Core KPI data
 * tables are preserved by default to avoid accidental data loss. Set the
 * constant MBD_KPI_DROP_TABLES to true (e.g. in wp-config.php) to also drop
 * all custom tables on uninstall.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-db.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-permissions.php';

MBD_KPI_Permissions::remove_roles();

delete_option( 'mbd_kpi_seeded' );
delete_option( 'mbd_kpi_flush_needed' );
delete_option( 'mbd_kpi_version' );

if ( defined( 'MBD_KPI_DROP_TABLES' ) && MBD_KPI_DROP_TABLES ) {
	global $wpdb;
	foreach ( MBD_KPI_DB::table_keys() as $key ) {
		$table = MBD_KPI_DB::table( $key );
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
	}
}
