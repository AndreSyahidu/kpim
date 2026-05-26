<?php
/**
 * OKR service: objectives and key results.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_OKR_Service {

	/**
	 * List objectives, optionally filtered by period or division.
	 *
	 * @param array $args period, division_id.
	 * @return array[]
	 */
	public static function get_objectives( $args = array() ) {
		global $wpdb;
		$table  = MBD_KPI_DB::table( 'objectives' );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['period'] ) ) {
			$where[]  = 'period = %s';
			$params[] = mbd_kpi_sanitize_period( $args['period'] );
		}
		if ( ! empty( $args['division_id'] ) ) {
			$where[]  = 'division_id = %d';
			$params[] = (int) $args['division_id'];
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC';
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single objective.
	 *
	 * @param int $id Objective id.
	 * @return array|null
	 */
	public static function get_objective( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'objectives' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update an objective.
	 *
	 * @param array $data Raw input.
	 * @return int Objective id.
	 */
	public static function save_objective( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'objectives' );

		$id     = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$fields = array(
			'title'             => sanitize_text_field( $data['title'] ?? '' ),
			'description'       => sanitize_textarea_field( $data['description'] ?? '' ),
			'period'            => mbd_kpi_sanitize_period( $data['period'] ?? '' ),
			'owner_employee_id' => (int) ( $data['owner_employee_id'] ?? 0 ),
			'division_id'       => (int) ( $data['division_id'] ?? 0 ),
			'status'            => sanitize_text_field( $data['status'] ?? 'on_track' ),
			'updated_at'        => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'objective.update', 'objective', $id, null, $fields );
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'objective.create', 'objective', $id, null, $fields );
		}
		return $id;
	}

	/**
	 * Get key results for an objective.
	 *
	 * @param int $objective_id Objective id.
	 * @return array[]
	 */
	public static function get_key_results( $objective_id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'key_results' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE objective_id = %d ORDER BY id ASC", (int) $objective_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single key result.
	 *
	 * @param int $id Key result id.
	 * @return array|null
	 */
	public static function get_key_result( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'key_results' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update a key result. Auto-computes progress.
	 *
	 * @param array $data Raw input.
	 * @return int Key result id.
	 */
	public static function save_key_result( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'key_results' );

		$id     = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$target = (float) ( $data['target_value'] ?? 0 );
		$current = (float) ( $data['current_value'] ?? 0 );
		$progress = ( $target > 0 ) ? round( min( 100, ( $current / $target ) * 100 ), 2 ) : 0;

		// Sanitize linked KPI ids to a clean csv of ints.
		$linked_raw = isset( $data['linked_kpi_ids'] ) ? (array) $data['linked_kpi_ids'] : array();
		$linked     = implode( ',', array_filter( array_map( 'intval', $linked_raw ) ) );

		$confidence = in_array( $data['confidence'] ?? 'medium', array( 'low', 'medium', 'high' ), true ) ? $data['confidence'] : 'medium';

		$fields = array(
			'objective_id'   => (int) ( $data['objective_id'] ?? 0 ),
			'title'          => sanitize_text_field( $data['title'] ?? '' ),
			'target_value'   => $target,
			'current_value'  => $current,
			'unit'           => sanitize_text_field( $data['unit'] ?? '' ),
			'progress'       => $progress,
			'confidence'     => $confidence,
			'risk_note'      => sanitize_textarea_field( $data['risk_note'] ?? '' ),
			'linked_kpi_ids' => $linked,
			'status'         => sanitize_text_field( $data['status'] ?? 'on_track' ),
			'updated_at'     => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'key_result.update', 'key_result', $id, null, $fields );
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'key_result.create', 'key_result', $id, null, $fields );
		}
		return $id;
	}

	/**
	 * Average progress across all key results for an objective.
	 *
	 * @param int $objective_id Objective id.
	 * @return float
	 */
	public static function objective_progress( $objective_id ) {
		$krs = self::get_key_results( $objective_id );
		if ( empty( $krs ) ) {
			return 0.0;
		}
		$sum = 0.0;
		foreach ( $krs as $kr ) {
			$sum += (float) $kr['progress'];
		}
		return round( $sum / count( $krs ), 2 );
	}
}
