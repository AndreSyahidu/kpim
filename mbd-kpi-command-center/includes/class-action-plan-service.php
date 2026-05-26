<?php
/**
 * Action plan service: action plans, updates, evidence, root causes, escalation.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Action_Plan_Service {

	/**
	 * List action plans with optional filters.
	 *
	 * @param array $args registry_id, owner_employee_id, status, overdue (bool).
	 * @return array[]
	 */
	public static function get_action_plans( $args = array() ) {
		global $wpdb;
		$table  = MBD_KPI_DB::table( 'action_plans' );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['registry_id'] ) ) {
			$where[]  = 'registry_id = %d';
			$params[] = (int) $args['registry_id'];
		}
		if ( ! empty( $args['owner_employee_id'] ) ) {
			$where[]  = 'owner_employee_id = %d';
			$params[] = (int) $args['owner_employee_id'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}
		if ( ! empty( $args['overdue'] ) ) {
			$where[]  = 'due_date IS NOT NULL AND due_date < %s AND status NOT IN ( "verified_effective", "closed_ineffective" )';
			$params[] = gmdate( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY due_date ASC, id DESC';
		if ( $params ) {
			$sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Get a single action plan.
	 *
	 * @param int $id Action plan id.
	 * @return array|null
	 */
	public static function get_action_plan( $id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'action_plans' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}

	/**
	 * Create or update an action plan. Enforces evidence + verification rules
	 * on status transitions.
	 *
	 * @param array $data Raw input.
	 * @return array { @type bool $ok, @type string $message, @type int $id }
	 */
	public static function save_action_plan( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'action_plans' );

		$id  = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$old = $id ? self::get_action_plan( $id ) : null;

		$status   = sanitize_text_field( $data['status'] ?? 'open' );
		$allowed  = array_keys( mbd_kpi_action_statuses() );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'open';
		}

		// Anti-cosmetic rule: "done" / closure states require verified evidence.
		if ( $id && in_array( $status, array( 'done_pending_verification', 'verified_effective' ), true ) ) {
			$evidence_total    = self::evidence_count( $id, false );
			$evidence_approved = self::evidence_count( $id, true );

			if ( 'done_pending_verification' === $status && $evidence_total < 1 ) {
				return array(
					'ok'      => false,
					'message' => __( 'An action plan cannot be marked done without at least one piece of evidence.', 'mbd-kpi' ),
					'id'      => $id,
				);
			}
			if ( 'verified_effective' === $status && $evidence_approved < 1 ) {
				return array(
					'ok'      => false,
					'message' => __( 'Evidence must be verified (approved) before an action plan can be marked verified effective.', 'mbd-kpi' ),
					'id'      => $id,
				);
			}
		}

		$due_date = '';
		if ( ! empty( $data['due_date'] ) ) {
			$due_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data['due_date'] ) ? $data['due_date'] : '';
		}

		$fields = array(
			'registry_id'          => (int) ( $data['registry_id'] ?? 0 ),
			'objective_id'         => (int) ( $data['objective_id'] ?? 0 ),
			'key_result_id'        => (int) ( $data['key_result_id'] ?? 0 ),
			'period'               => mbd_kpi_sanitize_period( $data['period'] ?? '' ),
			'problem_statement'    => sanitize_textarea_field( $data['problem_statement'] ?? '' ),
			'root_cause_category'  => sanitize_text_field( $data['root_cause_category'] ?? '' ),
			'root_cause_detail'    => sanitize_textarea_field( $data['root_cause_detail'] ?? '' ),
			'corrective_action'    => sanitize_textarea_field( $data['corrective_action'] ?? '' ),
			'preventive_action'    => sanitize_textarea_field( $data['preventive_action'] ?? '' ),
			'owner_employee_id'    => (int) ( $data['owner_employee_id'] ?? 0 ),
			'due_date'             => $due_date ?: null,
			'priority'             => in_array( $data['priority'] ?? 'medium', array( 'low', 'medium', 'high', 'critical' ), true ) ? $data['priority'] : 'medium',
			'status'               => $status,
			'reviewer_employee_id' => (int) ( $data['reviewer_employee_id'] ?? 0 ),
			'effectiveness_check'  => sanitize_text_field( $data['effectiveness_check'] ?? 'pending' ),
			'updated_at'           => mbd_kpi_now(),
		);

		if ( $id ) {
			$wpdb->update( $table, $fields, array( 'id' => $id ), null, array( '%d' ) );
			MBD_KPI_Audit_Log::log( 'action_plan.update', 'action_plan', $id, $old, $fields );
			if ( $old && $old['status'] !== $status ) {
				self::add_update( $id, sprintf( /* translators: %s status */ __( 'Status changed to %s', 'mbd-kpi' ), $status ), $status );
			}
		} else {
			$fields['created_by'] = get_current_user_id();
			$fields['created_at'] = mbd_kpi_now();
			$wpdb->insert( $table, $fields );
			$id = (int) $wpdb->insert_id;
			MBD_KPI_Audit_Log::log( 'action_plan.create', 'action_plan', $id, null, $fields );
		}

		// Track root cause occurrences for repeated-cause detection.
		if ( $fields['root_cause_category'] ) {
			self::record_root_cause( $id, (int) $fields['registry_id'], $fields['root_cause_category'], $fields['root_cause_detail'], $fields['period'] );
		}

		return array( 'ok' => true, 'message' => __( 'Action plan saved.', 'mbd-kpi' ), 'id' => $id );
	}

	/**
	 * Append a progress update to an action plan.
	 *
	 * @param int    $action_plan_id Action plan id.
	 * @param string $note           Update note.
	 * @param string $status         Optional status snapshot.
	 * @return int Update id.
	 */
	public static function add_update( $action_plan_id, $note, $status = '' ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'action_updates' );
		$wpdb->insert(
			$table,
			array(
				'action_plan_id' => (int) $action_plan_id,
				'update_note'    => sanitize_textarea_field( $note ),
				'status'         => sanitize_text_field( $status ),
				'updated_by'     => get_current_user_id(),
				'created_at'     => mbd_kpi_now(),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get updates for an action plan.
	 *
	 * @param int $action_plan_id Action plan id.
	 * @return array[]
	 */
	public static function get_updates( $action_plan_id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'action_updates' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE action_plan_id = %d ORDER BY id DESC", (int) $action_plan_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/* --------------------------------------------------------------------- *
	 * Evidence
	 * --------------------------------------------------------------------- */

	/**
	 * Count evidence for an entity.
	 *
	 * @param int  $action_plan_id Action plan id.
	 * @param bool $approved_only  Only approved.
	 * @return int
	 */
	public static function evidence_count( $action_plan_id, $approved_only = false ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'evidence' );
		if ( $approved_only ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE entity_type = 'action_plan' AND entity_id = %d AND verification_status = 'approved'", (int) $action_plan_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE entity_type = 'action_plan' AND entity_id = %d", (int) $action_plan_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * List evidence for an entity.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity id.
	 * @return array[]
	 */
	public static function get_evidence( $entity_type, $entity_id ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'evidence' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE entity_type = %s AND entity_id = %d ORDER BY id DESC", sanitize_text_field( $entity_type ), (int) $entity_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * List evidence with optional verification status filter (center view).
	 *
	 * @param string $status Verification status filter ('' for all).
	 * @return array[]
	 */
	public static function list_evidence( $status = '' ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'evidence' );
		if ( $status ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE verification_status = %s ORDER BY id DESC LIMIT 500", sanitize_text_field( $status ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (array) $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 500", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Save an evidence record (handles file upload or link).
	 *
	 * @param array $data  Raw input.
	 * @param array $file  Optional $_FILES entry.
	 * @return array { @type bool $ok, @type string $message, @type int $id }
	 */
	public static function save_evidence( $data, $file = array() ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'evidence' );

		$evidence_type = sanitize_text_field( $data['evidence_type'] ?? 'file' );
		$allowed_types = array_keys( mbd_kpi_evidence_types() );
		if ( ! in_array( $evidence_type, $allowed_types, true ) ) {
			$evidence_type = 'file';
		}

		$file_url = '';
		$link_url = '';

		// Link-style evidence.
		if ( in_array( $evidence_type, array( 'gdrive', 'document' ), true ) ) {
			$link_url = esc_url_raw( $data['link_url'] ?? '' );
			if ( ! $link_url ) {
				return array( 'ok' => false, 'message' => __( 'A valid link URL is required for this evidence type.', 'mbd-kpi' ), 'id' => 0 );
			}
		} elseif ( ! empty( $file['name'] ) ) {
			// Handle upload through WordPress media handling.
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$overrides = array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg' => 'image/jpeg',
					'png'      => 'image/png',
					'gif'      => 'image/gif',
					'pdf'      => 'application/pdf',
					'doc'      => 'application/msword',
					'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'xls'      => 'application/vnd.ms-excel',
					'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				),
			);
			$moved = wp_handle_upload( $file, $overrides );
			if ( isset( $moved['error'] ) ) {
				return array( 'ok' => false, 'message' => esc_html( $moved['error'] ), 'id' => 0 );
			}
			$file_url = esc_url_raw( $moved['url'] );
		} else {
			$link_url = esc_url_raw( $data['link_url'] ?? '' );
		}

		$fields = array(
			'entity_type'         => sanitize_text_field( $data['entity_type'] ?? 'action_plan' ),
			'entity_id'           => (int) ( $data['entity_id'] ?? 0 ),
			'evidence_type'       => $evidence_type,
			'title'               => sanitize_text_field( $data['title'] ?? '' ),
			'file_url'            => $file_url,
			'link_url'            => $link_url,
			'note'                => sanitize_textarea_field( $data['note'] ?? '' ),
			'uploaded_by'         => get_current_user_id(),
			'verification_status' => 'pending',
			'created_at'          => mbd_kpi_now(),
			'updated_at'          => mbd_kpi_now(),
		);

		$wpdb->insert( $table, $fields );
		$id = (int) $wpdb->insert_id;
		MBD_KPI_Audit_Log::log( 'evidence.create', 'evidence', $id, null, $fields );

		return array( 'ok' => true, 'message' => __( 'Evidence saved.', 'mbd-kpi' ), 'id' => $id );
	}

	/**
	 * Verify an evidence record.
	 *
	 * @param int    $evidence_id Evidence id.
	 * @param string $status      approved|rejected|need_revision|pending.
	 * @return bool
	 */
	public static function verify_evidence( $evidence_id, $status ) {
		global $wpdb;
		$table   = MBD_KPI_DB::table( 'evidence' );
		$allowed = array_keys( mbd_kpi_verification_statuses() );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		$old = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $evidence_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
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
			array( 'id' => (int) $evidence_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
		MBD_KPI_Audit_Log::log( 'evidence.verify', 'evidence', (int) $evidence_id, array( 'status' => $old['verification_status'] ), array( 'status' => $status ) );
		return true;
	}

	/* --------------------------------------------------------------------- *
	 * Root causes & escalation
	 * --------------------------------------------------------------------- */

	/**
	 * Record a root cause occurrence (increments count if the same category
	 * recurs for the same registry).
	 *
	 * @param int    $action_plan_id Action plan id.
	 * @param int    $registry_id    Registry id.
	 * @param string $category       Root cause category.
	 * @param string $detail         Detail.
	 * @param string $period         Period.
	 * @return void
	 */
	public static function record_root_cause( $action_plan_id, $registry_id, $category, $detail, $period ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'root_causes' );

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE registry_id = %d AND category = %s", (int) $registry_id, $category ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'occurrence_count' => (int) $existing['occurrence_count'] + 1,
					'detail'           => sanitize_textarea_field( $detail ),
					'period'           => $period,
				),
				array( 'id' => (int) $existing['id'] ),
				array( '%d', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'action_plan_id'   => (int) $action_plan_id,
					'registry_id'      => (int) $registry_id,
					'category'         => sanitize_text_field( $category ),
					'detail'           => sanitize_textarea_field( $detail ),
					'occurrence_count' => 1,
					'period'           => $period,
					'created_by'       => get_current_user_id(),
					'created_at'       => mbd_kpi_now(),
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
			);
		}
	}

	/**
	 * Repeated root causes (occurrence_count >= threshold).
	 *
	 * @param int $threshold Minimum occurrences.
	 * @return array[]
	 */
	public static function repeated_root_causes( $threshold = 2 ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'root_causes' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE occurrence_count >= %d ORDER BY occurrence_count DESC", (int) $threshold ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Create an escalation record (idempotent per registry+period+reason).
	 *
	 * @param array $data registry_id, action_plan_id, reason, level, period.
	 * @return int Escalation id (existing or new).
	 */
	public static function create_escalation( $data ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'escalations' );

		$registry_id = (int) ( $data['registry_id'] ?? 0 );
		$period      = mbd_kpi_sanitize_period( $data['period'] ?? '' );
		$reason      = sanitize_text_field( $data['reason'] ?? '' );

		// Avoid duplicate open escalations for the same registry+period.
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE registry_id = %d AND period = %s AND status = 'open'", $registry_id, $period ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $existing ) {
			return (int) $existing;
		}

		$wpdb->insert(
			$table,
			array(
				'registry_id'      => $registry_id,
				'action_plan_id'   => (int) ( $data['action_plan_id'] ?? 0 ),
				'reason'           => $reason,
				'level'            => sanitize_text_field( $data['level'] ?? 'supervisor' ),
				'from_employee_id' => (int) ( $data['from_employee_id'] ?? 0 ),
				'to_employee_id'   => (int) ( $data['to_employee_id'] ?? 0 ),
				'period'           => $period,
				'status'           => 'open',
				'created_at'       => mbd_kpi_now(),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);
		$id = (int) $wpdb->insert_id;
		MBD_KPI_Audit_Log::log( 'escalation.create', 'escalation', $id, null, $data );
		return $id;
	}

	/**
	 * Anti-cosmetic rule: a KPI that is red for two consecutive periods must
	 * raise an escalation. Idempotent via create_escalation().
	 *
	 * @param array  $registry      Registry row.
	 * @param string $period        Current period.
	 * @param string $current_status Current computed status for the period.
	 * @return int Escalation id created/found, or 0 if no escalation needed.
	 */
	public static function maybe_escalate_consecutive_red( $registry, $period, $current_status ) {
		if ( 'red' !== $current_status ) {
			return 0;
		}
		$prev = mbd_kpi_previous_period( $period );
		if ( ! $prev ) {
			return 0;
		}

		global $wpdb;
		$scores_tbl = MBD_KPI_DB::table( 'scores' );
		$prev_status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$scores_tbl} WHERE registry_id = %d AND period = %s ORDER BY is_snapshot DESC LIMIT 1", (int) $registry['id'], $prev ) ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( 'red' !== $prev_status ) {
			return 0;
		}

		return self::create_escalation(
			array(
				'registry_id'      => (int) $registry['id'],
				'reason'           => sprintf( /* translators: %s previous period */ __( 'Red for two consecutive periods (since %s).', 'mbd-kpi' ), $prev ),
				'level'            => 'division_head',
				'from_employee_id' => (int) $registry['owner_employee_id'],
				'period'           => $period,
			)
		);
	}

	/**
	 * Open escalations.
	 *
	 * @return array[]
	 */
	public static function get_open_escalations() {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'escalations' );
		return (array) $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'open' ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
