<?php
/**
 * Review service: review sessions, items, and decisions.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Review_Service {

	/**
	 * List reviews with optional filters.
	 *
	 * @param array $args review_type, period, status.
	 * @return array[]
	 */
	public static function get_reviews( $args = array() ) {
		global $wpdb;
		$table  = MBD_KPI_DB::table( 'reviews' );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['review_type'] ) ) {
			$where[]  = 'review_type = %s';
			$params[] = sanitize_text_field( $args['review_type'] );
		}
		if ( ! empty( $args['period'] ) ) {
			$where[]  = 'period = %s';
			$params[] = mbd_kpi_sanitize_period( $args['period'] );
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY scheduled_date DESC, id DESC';
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single review.
	 *
	 * @param int $id Review id.
	 * @return array|null
	 */
	public static function get_review( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'reviews' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update a review session.
	 *
	 * @param array $data Raw input.
	 * @return int Review id.
	 */
	public static function save_review( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'reviews' );

		$id          = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$review_type = sanitize_text_field( $data['review_type'] ?? 'weekly_division' );
		if ( ! array_key_exists( $review_type, mbd_kpi_review_types() ) ) {
			$review_type = 'weekly_division';
		}

		$scheduled = '';
		if ( ! empty( $data['scheduled_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['scheduled_date'] ) ) {
			$scheduled = $data['scheduled_date'];
		}

		$fields = array(
			'review_type'             => $review_type,
			'title'                   => sanitize_text_field( $data['title'] ?? '' ),
			'period'                  => mbd_kpi_sanitize_period( $data['period'] ?? '' ),
			'scope_type'              => sanitize_text_field( $data['scope_type'] ?? 'company' ),
			'scope_id'                => (int) ( $data['scope_id'] ?? 0 ),
			'scheduled_date'          => $scheduled ?: null,
			'facilitator_employee_id' => (int) ( $data['facilitator_employee_id'] ?? 0 ),
			'summary'                 => sanitize_textarea_field( $data['summary'] ?? '' ),
			'status'                  => sanitize_text_field( $data['status'] ?? 'open' ),
			'updated_at'              => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'review.update', 'review', $id, null, $fields );
		} else {
			$fields['created_by'] = get_current_user_id();
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'review.create', 'review', $id, null, $fields );
		}
		return $id;
	}

	/**
	 * Get items for a review.
	 *
	 * @param int $review_id Review id.
	 * @return array[]
	 */
	public static function get_items( $review_id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'review_items' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE review_id = %d ORDER BY id ASC", (int) $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Add a review item.
	 *
	 * @param array $data Raw input.
	 * @return int Item id.
	 */
	public static function add_item( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'review_items' );
		$wpdb->insert(
			$table,
			array(
				'review_id'      => (int) ( $data['review_id'] ?? 0 ),
				'registry_id'    => (int) ( $data['registry_id'] ?? 0 ),
				'objective_id'   => (int) ( $data['objective_id'] ?? 0 ),
				'action_plan_id' => (int) ( $data['action_plan_id'] ?? 0 ),
				'item_type'      => sanitize_text_field( $data['item_type'] ?? 'kpi' ),
				'note'           => sanitize_textarea_field( $data['note'] ?? '' ),
				'status'         => sanitize_text_field( $data['status'] ?? 'open' ),
				'created_at'     => mbd_kpi_now(),
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get decisions for a review.
	 *
	 * @param int $review_id Review id.
	 * @return array[]
	 */
	public static function get_decisions( $review_id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'review_decisions' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE review_id = %d ORDER BY id ASC", (int) $review_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Add a review decision (with action item, owner, due date, escalation path).
	 *
	 * @param array $data Raw input.
	 * @return int Decision id.
	 */
	public static function add_decision( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'review_decisions' );

		$due = '';
		if ( ! empty( $data['due_date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['due_date'] ) ) {
			$due = $data['due_date'];
		}

		$wpdb->insert(
			$table,
			array(
				'review_id'         => (int) ( $data['review_id'] ?? 0 ),
				'decision_text'     => sanitize_textarea_field( $data['decision_text'] ?? '' ),
				'action_item'       => sanitize_textarea_field( $data['action_item'] ?? '' ),
				'owner_employee_id' => (int) ( $data['owner_employee_id'] ?? 0 ),
				'due_date'          => $due ?: null,
				'escalation_path'   => sanitize_text_field( $data['escalation_path'] ?? '' ),
				'status'            => sanitize_text_field( $data['status'] ?? 'open' ),
				'created_at'        => mbd_kpi_now(),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
		$id = (int) $wpdb->insert_id;
		MBD_KPI_Audit_Log::log( 'review.decision', 'review_decision', $id, null, $data );
		return $id;
	}
}
