<?php
/**
 * Audit log writer / reader for sensitive changes.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Audit_Log {

	/**
	 * Record an audited change.
	 *
	 * @param string $action      Action key, e.g. 'target.update'.
	 * @param string $object_type Object type, e.g. 'target'.
	 * @param int    $object_id   Object id.
	 * @param mixed  $old         Previous value (will be JSON encoded).
	 * @param mixed  $new         New value (will be JSON encoded).
	 * @return int|false Insert id or false.
	 */
	public static function log( $action, $object_type, $object_id = 0, $old = null, $new = null ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'audit_logs' );

		$result = $wpdb->insert(
			$table,
			array(
				'user_id'     => get_current_user_id(),
				'action'      => sanitize_text_field( $action ),
				'object_type' => sanitize_text_field( $object_type ),
				'object_id'   => (int) $object_id,
				'old_value'   => ( null === $old ) ? null : wp_json_encode( $old ),
				'new_value'   => ( null === $new ) ? null : wp_json_encode( $new ),
				'ip_address'  => self::client_ip(),
				'created_at'  => mbd_kpi_now(),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Best-effort client IP, sanitized.
	 *
	 * @return string
	 */
	private static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( $ip, 0, 60 );
	}

	/**
	 * Fetch recent audit rows, optionally filtered by object type.
	 *
	 * @param int    $limit       Max rows.
	 * @param string $object_type Optional filter.
	 * @return array[]
	 */
	public static function recent( $limit = 100, $object_type = '' ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'audit_logs' );
		$limit = max( 1, min( 1000, (int) $limit ) );

		if ( $object_type ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE object_type = %s ORDER BY id DESC LIMIT %d", $object_type, $limit ); // phpcs:ignore WordPress.DB.PreparedSQL
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
