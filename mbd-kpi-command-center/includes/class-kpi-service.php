<?php
/**
 * KPI service: divisions, employees, dictionary, registry, targets, actuals.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Service {

	/* --------------------------------------------------------------------- *
	 * Period locks
	 * --------------------------------------------------------------------- */

	/**
	 * Whether a period is locked.
	 *
	 * @param string $period Period string.
	 * @return bool
	 */
	public static function is_period_locked( $period ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'period_locks' );
		$row   = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$table} WHERE period = %s", $period ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return 'locked' === $row;
	}

	/* --------------------------------------------------------------------- *
	 * Divisions
	 * --------------------------------------------------------------------- */

	/**
	 * List divisions.
	 *
	 * @return array[]
	 */
	public static function get_divisions() {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'divisions' );
		return (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single division.
	 *
	 * @param int $id Division id.
	 * @return array|null
	 */
	public static function get_division( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'divisions' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update a division.
	 *
	 * @param array $data Raw input.
	 * @return int Division id.
	 */
	public static function save_division( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'divisions' );

		$id     = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$fields = array(
			'name'             => sanitize_text_field( $data['name'] ?? '' ),
			'code'             => sanitize_text_field( $data['code'] ?? '' ),
			'parent_id'        => (int) ( $data['parent_id'] ?? 0 ),
			'head_employee_id' => (int) ( $data['head_employee_id'] ?? 0 ),
			'description'      => sanitize_textarea_field( $data['description'] ?? '' ),
			'status'           => sanitize_text_field( $data['status'] ?? 'active' ),
			'updated_at'       => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'division.update', 'division', $id, null, $fields );
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'division.create', 'division', $id, null, $fields );
		}
		return $id;
	}

	/* --------------------------------------------------------------------- *
	 * Employees
	 * --------------------------------------------------------------------- */

	/**
	 * List employees, optionally by division.
	 *
	 * @param int $division_id Optional division filter.
	 * @return array[]
	 */
	public static function get_employees( $division_id = 0 ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'employees' );
		if ( $division_id ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE division_id = %d ORDER BY full_name ASC", (int) $division_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY full_name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single employee.
	 *
	 * @param int $id Employee id.
	 * @return array|null
	 */
	public static function get_employee( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'employees' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update an employee.
	 *
	 * @param array $data Raw input.
	 * @return int Employee id.
	 */
	public static function save_employee( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'employees' );

		$id     = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$fields = array(
			'user_id'       => (int) ( $data['user_id'] ?? 0 ),
			'full_name'     => sanitize_text_field( $data['full_name'] ?? '' ),
			'email'         => sanitize_email( $data['email'] ?? '' ),
			'division_id'   => (int) ( $data['division_id'] ?? 0 ),
			'position'      => sanitize_text_field( $data['position'] ?? '' ),
			'role_key'      => sanitize_text_field( $data['role_key'] ?? 'mbd_staff' ),
			'supervisor_id' => (int) ( $data['supervisor_id'] ?? 0 ),
			'status'        => sanitize_text_field( $data['status'] ?? 'active' ),
			'updated_at'    => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'employee.update', 'employee', $id, null, $fields );
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'employee.create', 'employee', $id, null, $fields );
		}
		return $id;
	}

	/**
	 * Map employee id => display name.
	 *
	 * @return array<int,string>
	 */
	public static function employee_map() {
		$out = array();
		foreach ( self::get_employees() as $e ) {
			$out[ (int) $e['id'] ] = $e['full_name'];
		}
		return $out;
	}

	/**
	 * Map division id => name.
	 *
	 * @return array<int,string>
	 */
	public static function division_map() {
		$out = array();
		foreach ( self::get_divisions() as $d ) {
			$out[ (int) $d['id'] ] = $d['name'];
		}
		return $out;
	}

	/* --------------------------------------------------------------------- *
	 * Dictionary
	 * --------------------------------------------------------------------- */

	/**
	 * List dictionary terms.
	 *
	 * @return array[]
	 */
	public static function get_dictionary() {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'dictionary' );
		return (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY term_name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Create or update a dictionary term.
	 *
	 * @param array $data Raw input.
	 * @return int Term id.
	 */
	public static function save_dictionary_term( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'dictionary' );

		$id     = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$fields = array(
			'term_code'           => sanitize_text_field( $data['term_code'] ?? '' ),
			'term_name'           => sanitize_text_field( $data['term_name'] ?? '' ),
			'business_definition' => sanitize_textarea_field( $data['business_definition'] ?? '' ),
			'category'            => sanitize_text_field( $data['category'] ?? '' ),
			'formula_type'        => sanitize_text_field( $data['formula_type'] ?? 'positive' ),
			'formula_description' => sanitize_textarea_field( $data['formula_description'] ?? '' ),
			'unit'                => sanitize_text_field( $data['unit'] ?? '' ),
			'target_direction'    => ( 'down' === ( $data['target_direction'] ?? 'up' ) ) ? 'down' : 'up',
			'status'              => sanitize_text_field( $data['status'] ?? 'active' ),
			'updated_at'          => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'dictionary.update', 'dictionary', $id, null, $fields );
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'dictionary.create', 'dictionary', $id, null, $fields );
		}
		return $id;
	}

	/* --------------------------------------------------------------------- *
	 * Registry
	 * --------------------------------------------------------------------- */

	/**
	 * List registry KPIs scoped to the current user.
	 *
	 * @param array $args Optional filters: division_id, perspective, pillar, status, owner_employee_id, search.
	 * @return array[]
	 */
	public static function get_registry( $args = array() ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'registry' );

		$scope  = MBD_KPI_Permissions::registry_scope_sql( '' );
		$where  = array( '(' . $scope['where'] . ')' );
		$params = $scope['params'];

		if ( ! empty( $args['division_id'] ) ) {
			$where[]  = 'division_id = %d';
			$params[] = (int) $args['division_id'];
		}
		if ( ! empty( $args['perspective'] ) ) {
			$where[]  = 'bsc_perspective = %s';
			$params[] = sanitize_text_field( $args['perspective'] );
		}
		if ( ! empty( $args['pillar'] ) ) {
			$where[]  = 'strategic_pillar = %s';
			$params[] = sanitize_text_field( $args['pillar'] );
		}
		if ( ! empty( $args['owner_employee_id'] ) ) {
			$where[]  = 'owner_employee_id = %d';
			$params[] = (int) $args['owner_employee_id'];
		}
		if ( isset( $args['status'] ) && '' !== $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[] = '(kpi_name LIKE %s OR kpi_code LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY kpi_code ASC, kpi_name ASC';
		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single registry item (unscoped read; callers must auth-check).
	 *
	 * @param int $id Registry id.
	 * @return array|null
	 */
	public static function get_registry_item( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'registry' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update a registry KPI. Audits formula and threshold changes.
	 *
	 * @param array $data Raw input.
	 * @return int Registry id.
	 */
	public static function save_registry( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'registry' );

		$id  = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$old = $id ? self::get_registry_item( $id ) : null;

		$fields = array(
			'kpi_code'            => sanitize_text_field( $data['kpi_code'] ?? '' ),
			'kpi_name'            => sanitize_text_field( $data['kpi_name'] ?? '' ),
			'dictionary_id'       => (int) ( $data['dictionary_id'] ?? 0 ),
			'business_definition' => sanitize_textarea_field( $data['business_definition'] ?? '' ),
			'category'            => sanitize_text_field( $data['category'] ?? '' ),
			'bsc_perspective'     => sanitize_text_field( $data['bsc_perspective'] ?? '' ),
			'strategic_pillar'    => sanitize_text_field( $data['strategic_pillar'] ?? '' ),
			'division_id'         => (int) ( $data['division_id'] ?? 0 ),
			'owner_employee_id'   => (int) ( $data['owner_employee_id'] ?? 0 ),
			'formula_type'        => sanitize_text_field( $data['formula_type'] ?? 'positive' ),
			'formula_description' => sanitize_textarea_field( $data['formula_description'] ?? '' ),
			'target_direction'    => ( 'down' === ( $data['target_direction'] ?? 'up' ) ) ? 'down' : 'up',
			'frequency'           => sanitize_text_field( $data['frequency'] ?? 'monthly' ),
			'data_source'         => sanitize_text_field( $data['data_source'] ?? '' ),
			'evidence_required'   => empty( $data['evidence_required'] ) ? 0 : 1,
			'threshold_green'     => (float) ( $data['threshold_green'] ?? 100 ),
			'threshold_yellow'    => (float) ( $data['threshold_yellow'] ?? 80 ),
			'threshold_red'       => (float) ( $data['threshold_red'] ?? 0 ),
			'unit'                => sanitize_text_field( $data['unit'] ?? '' ),
			'weight'              => (float) ( $data['weight'] ?? 1 ),
			'status'              => sanitize_text_field( $data['status'] ?? 'active' ),
			'updated_at'          => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'registry.update', 'registry', $id, $old, $fields );

			// Anti-cosmetic rule: formula changes must be specifically audited.
			if ( $old && (
				$old['formula_type'] !== $fields['formula_type'] ||
				$old['target_direction'] !== $fields['target_direction'] ||
				$old['formula_description'] !== $fields['formula_description']
			) ) {
				MBD_KPI_Audit_Log::log(
					'registry.formula_change',
					'registry',
					$id,
					array(
						'formula_type'        => $old['formula_type'],
						'target_direction'    => $old['target_direction'],
						'formula_description' => $old['formula_description'],
					),
					array(
						'formula_type'        => $fields['formula_type'],
						'target_direction'    => $fields['target_direction'],
						'formula_description' => $fields['formula_description'],
					)
				);
			}

			// Threshold changes audited separately.
			if ( $old && (
				(float) $old['threshold_green'] !== $fields['threshold_green'] ||
				(float) $old['threshold_yellow'] !== $fields['threshold_yellow'] ||
				(float) $old['threshold_red'] !== $fields['threshold_red']
			) ) {
				MBD_KPI_Audit_Log::log( 'registry.threshold_change', 'registry', $id, $old, $fields );
			}
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'registry.create', 'registry', $id, null, $fields );
		}
		return $id;
	}

	/* --------------------------------------------------------------------- *
	 * Targets
	 * --------------------------------------------------------------------- */

	/**
	 * Get the target value for a registry + period.
	 *
	 * @param int    $registry_id Registry id.
	 * @param string $period      Period.
	 * @return array|null
	 */
	public static function get_target( $registry_id, $period ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'targets' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE registry_id = %d AND period = %s", (int) $registry_id, $period ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Set/update a target. Audits the change (anti-cosmetic rule).
	 *
	 * @param array $data Raw input.
	 * @return array { @type bool $ok, @type string $message, @type int $id }
	 */
	public static function save_target( $data ) {
		global $wpdb;
		$table  = MBD_KPI_DB::table( 'targets' );
		$period = mbd_kpi_sanitize_period( $data['period'] ?? '' );

		if ( ! $period ) {
			return array( 'ok' => false, 'message' => __( 'Invalid period.', 'mbd-kpi' ), 'id' => 0 );
		}

		$registry_id = (int) ( $data['registry_id'] ?? 0 );
		$existing    = self::get_target( $registry_id, $period );

		// Period lock: target changes require an adjustment note.
		if ( MBD_KPI_Service::is_period_locked( $period ) ) {
			$adjustment = sanitize_textarea_field( $data['adjustment_log'] ?? '' );
			if ( '' === $adjustment ) {
				return array(
					'ok'      => false,
					'message' => __( 'Period is locked. An adjustment log note is required to change a locked target.', 'mbd-kpi' ),
					'id'      => 0,
				);
			}
			MBD_KPI_Audit_Log::log( 'target.locked_adjustment', 'target', $existing ? (int) $existing['id'] : 0, $existing, array( 'note' => $adjustment ) );
		}

		$fields = array(
			'registry_id'  => $registry_id,
			'period'       => $period,
			'target_value' => (float) ( $data['target_value'] ?? 0 ),
			'stretch_value' => isset( $data['stretch_value'] ) && '' !== $data['stretch_value'] ? (float) $data['stretch_value'] : null,
			'note'         => sanitize_textarea_field( $data['note'] ?? '' ),
			'set_by'       => get_current_user_id(),
			'updated_at'   => mbd_kpi_now(),
		);

		if ( $existing ) {
			$wpdb->update( $table, $fields, array( 'id' => (int) $existing['id'] ), null, array( '%d' ) );
			$id = (int) $existing['id'];
			MBD_KPI_Audit_Log::log( 'target.update', 'target', $id, $existing, $fields );
		} else {
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'target.create', 'target', $id, null, $fields );
		}

		return array( 'ok' => true, 'message' => __( 'Target saved.', 'mbd-kpi' ), 'id' => $id );
	}

	/* --------------------------------------------------------------------- *
	 * Actuals
	 * --------------------------------------------------------------------- */

	/**
	 * Get an actual for registry + period.
	 *
	 * @param int    $registry_id Registry id.
	 * @param string $period      Period.
	 * @return array|null
	 */
	public static function get_actual( $registry_id, $period ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'actuals' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE registry_id = %d AND period = %s", (int) $registry_id, $period ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Submit / update an actual value. Enforces period lock.
	 *
	 * @param array $data Raw input.
	 * @return array { @type bool $ok, @type string $message, @type int $id }
	 */
	public static function save_actual( $data ) {
		global $wpdb;
		$table  = MBD_KPI_DB::table( 'actuals' );
		$period = mbd_kpi_sanitize_period( $data['period'] ?? '' );

		if ( ! $period ) {
			return array( 'ok' => false, 'message' => __( 'Invalid period.', 'mbd-kpi' ), 'id' => 0 );
		}

		$registry_id = (int) ( $data['registry_id'] ?? 0 );
		$registry    = self::get_registry_item( $registry_id );
		if ( ! $registry ) {
			return array( 'ok' => false, 'message' => __( 'Unknown KPI.', 'mbd-kpi' ), 'id' => 0 );
		}

		// Period lock: actuals cannot be edited directly once locked.
		if ( self::is_period_locked( $period ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'This period is locked. Actuals cannot be edited directly; create an adjustment instead.', 'mbd-kpi' ),
				'id'      => 0,
			);
		}

		$existing = self::get_actual( $registry_id, $period );

		$fields = array(
			'registry_id'  => $registry_id,
			'period'       => $period,
			'actual_value' => (float) ( $data['actual_value'] ?? 0 ),
			'note'         => sanitize_textarea_field( $data['note'] ?? '' ),
			'is_manual'    => 1, // MVP: all entries are manual; flagged as such per anti-cosmetic rule.
			'submitted_by' => get_current_user_id(),
			'submitted_at' => mbd_kpi_now(),
			'updated_at'   => mbd_kpi_now(),
		);

		if ( $existing ) {
			// Re-submission resets verification to pending.
			$fields['verification_status'] = 'pending';
			$fields['verified_by']         = 0;
			$fields['verified_at']         = null;
			$wpdb->update( $table, $fields, array( 'id' => (int) $existing['id'] ), null, array( '%d' ) );
			$id = (int) $existing['id'];
			MBD_KPI_Audit_Log::log( 'actual.update', 'actual', $id, $existing, $fields );
		} else {
			$fields['verification_status'] = 'pending';
			$fields['created_at']          = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'actual.create', 'actual', $id, null, $fields );
		}

		// Recompute score immediately.
		MBD_KPI_Score_Engine::compute_and_store( $registry, $period );

		return array( 'ok' => true, 'message' => __( 'Actual saved and score recalculated.', 'mbd-kpi' ), 'id' => $id );
	}

	/**
	 * Verify (approve/reject) an actual.
	 *
	 * @param int    $actual_id Actual id.
	 * @param string $status    approved|rejected|need_revision.
	 * @return bool
	 */
	public static function verify_actual( $actual_id, $status ) {
		global $wpdb;
		$table  = MBD_KPI_DB::table( 'actuals' );
		$allowed = array_keys( mbd_kpi_verification_statuses() );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		$old = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $actual_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $old ) {
			return false;
		}
		$wpdb->update(
			$table,
			array(
				'verification_status' => $status,
				'verified_by'         => get_current_user_id(),
				'verified_at'         => mbd_kpi_now(),
				'updated_at'          => mbd_kpi_now(),
			),
			array( 'id' => (int) $actual_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		MBD_KPI_Audit_Log::log( 'actual.verify', 'actual', (int) $actual_id, array( 'status' => $old['verification_status'] ), array( 'status' => $status ) );

		// Recompute score (data health depends on verification).
		$registry = self::get_registry_item( (int) $old['registry_id'] );
		if ( $registry ) {
			MBD_KPI_Score_Engine::compute_and_store( $registry, $old['period'] );
		}
		return true;
	}
}
