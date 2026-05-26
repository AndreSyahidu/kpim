<?php
/**
 * Role / capability management and data-scoping helpers.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Permissions {

	/**
	 * Role definitions: role_key => display name.
	 *
	 * @return array<string,string>
	 */
	public static function roles() {
		return array(
			'mbd_owner'         => 'MBD Owner',
			'mbd_director'      => 'MBD Director',
			'mbd_division_head' => 'MBD Division Head',
			'mbd_supervisor'    => 'MBD Supervisor',
			'mbd_staff'         => 'MBD Staff',
			'mbd_kpi_admin'     => 'MBD KPI Admin',
		);
	}

	/**
	 * Custom capabilities used by the plugin.
	 *
	 * @return string[]
	 */
	public static function capabilities() {
		return array(
			'mbd_kpi_access',           // Enter /kpi at all.
			'mbd_kpi_view_all',         // Company-wide data.
			'mbd_kpi_view_division',    // Own division data.
			'mbd_kpi_view_team',        // Direct team data.
			'mbd_kpi_input_actual',     // Submit actual values.
			'mbd_kpi_verify',           // Verify evidence / actuals.
			'mbd_kpi_manage_registry',  // Manage dictionary/registry/targets.
			'mbd_kpi_manage_okr',       // Manage objectives & key results.
			'mbd_kpi_manage_action',    // Manage action plans.
			'mbd_kpi_manage_review',    // Run reviews & decisions.
			'mbd_kpi_manage_settings',  // Settings, formulas, period locks, import/export.
			'mbd_kpi_view_audit',       // View audit log.
		);
	}

	/**
	 * Capability matrix per role.
	 *
	 * @return array<string,string[]>
	 */
	public static function role_caps() {
		$all = self::capabilities();

		$owner   = $all; // Owner gets everything.
		$director = array(
			'mbd_kpi_access',
			'mbd_kpi_view_all',
			'mbd_kpi_view_division',
			'mbd_kpi_view_team',
			'mbd_kpi_manage_okr',
			'mbd_kpi_manage_review',
			'mbd_kpi_verify',
			'mbd_kpi_view_audit',
		);
		$division_head = array(
			'mbd_kpi_access',
			'mbd_kpi_view_division',
			'mbd_kpi_view_team',
			'mbd_kpi_input_actual',
			'mbd_kpi_manage_okr',
			'mbd_kpi_manage_action',
			'mbd_kpi_manage_review',
			'mbd_kpi_verify',
		);
		$supervisor = array(
			'mbd_kpi_access',
			'mbd_kpi_view_team',
			'mbd_kpi_input_actual',
			'mbd_kpi_manage_action',
			'mbd_kpi_verify',
		);
		$staff = array(
			'mbd_kpi_access',
			'mbd_kpi_input_actual',
			'mbd_kpi_manage_action',
		);
		$kpi_admin = array(
			'mbd_kpi_access',
			'mbd_kpi_view_all',
			'mbd_kpi_manage_registry',
			'mbd_kpi_manage_okr',
			'mbd_kpi_manage_settings',
			'mbd_kpi_input_actual',
			'mbd_kpi_verify',
			'mbd_kpi_view_audit',
		);

		return array(
			'mbd_owner'         => $owner,
			'mbd_director'      => $director,
			'mbd_division_head' => $division_head,
			'mbd_supervisor'    => $supervisor,
			'mbd_staff'         => $staff,
			'mbd_kpi_admin'     => $kpi_admin,
		);
	}

	/**
	 * Register roles + capabilities. Called on activation.
	 *
	 * @return void
	 */
	public static function register_roles() {
		$role_caps = self::role_caps();

		foreach ( self::roles() as $role_key => $display ) {
			$caps = array( 'read' => true );
			foreach ( $role_caps[ $role_key ] as $cap ) {
				$caps[ $cap ] = true;
			}

			$existing = get_role( $role_key );
			if ( $existing ) {
				// Refresh capability set on re-activation.
				foreach ( self::capabilities() as $cap ) {
					if ( isset( $caps[ $cap ] ) ) {
						$existing->add_cap( $cap );
					} else {
						$existing->remove_cap( $cap );
					}
				}
			} else {
				add_role( $role_key, $display, $caps );
			}
		}

		// Administrators always get full access for support/admin work.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::capabilities() as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove custom roles. Called on uninstall (not deactivation).
	 *
	 * @return void
	 */
	public static function remove_roles() {
		foreach ( array_keys( self::roles() ) as $role_key ) {
			remove_role( $role_key );
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::capabilities() as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}

	/**
	 * Whether the current user can reach the /kpi front-end at all.
	 *
	 * @return bool
	 */
	public static function can_access() {
		return is_user_logged_in() && current_user_can( 'mbd_kpi_access' );
	}

	/**
	 * Convenience capability check.
	 *
	 * @param string $cap Capability.
	 * @return bool
	 */
	public static function can( $cap ) {
		return current_user_can( $cap );
	}

	/**
	 * Get the employee record for the current logged-in user (or null).
	 *
	 * @return array|null
	 */
	public static function current_employee() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache ?: null;
		}
		global $wpdb;
		$uid = get_current_user_id();
		if ( ! $uid ) {
			$cache = false;
			return null;
		}
		$table = MBD_KPI_DB::table( 'employees' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $uid ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$cache = $row ? $row : false;
		return $row ? $row : null;
	}

	/**
	 * Highest-privilege scope for the current user.
	 *
	 * @return array {
	 *     @type string $scope        all|division|team|self
	 *     @type int    $division_id  Division id when scope=division.
	 *     @type int    $employee_id  Current employee id (0 if none).
	 * }
	 */
	public static function current_scope() {
		$emp         = self::current_employee();
		$employee_id = $emp ? (int) $emp['id'] : 0;
		$division_id = $emp ? (int) $emp['division_id'] : 0;

		if ( current_user_can( 'mbd_kpi_view_all' ) ) {
			$scope = 'all';
		} elseif ( current_user_can( 'mbd_kpi_view_division' ) ) {
			$scope = 'division';
		} elseif ( current_user_can( 'mbd_kpi_view_team' ) ) {
			$scope = 'team';
		} else {
			$scope = 'self';
		}

		return array(
			'scope'       => $scope,
			'division_id' => $division_id,
			'employee_id' => $employee_id,
		);
	}

	/**
	 * Build a WHERE fragment + params restricting registry rows to the
	 * current user's data scope.
	 *
	 * @param string $alias Table alias prefix for the registry table (e.g. 'r').
	 * @return array { @type string $where, @type array $params }
	 */
	public static function registry_scope_sql( $alias = 'r' ) {
		$scope = self::current_scope();
		$a     = $alias ? $alias . '.' : '';

		switch ( $scope['scope'] ) {
			case 'all':
				return array( 'where' => '1=1', 'params' => array() );

			case 'division':
				return array(
					'where'  => "{$a}division_id = %d",
					'params' => array( $scope['division_id'] ),
				);

			case 'team':
				// Supervisor: registry owned by self or by direct reports.
				$ids = self::team_employee_ids( $scope['employee_id'] );
				$ids[] = $scope['employee_id'];
				$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
				if ( empty( $ids ) ) {
					return array( 'where' => '1=0', 'params' => array() );
				}
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				return array(
					'where'  => "{$a}owner_employee_id IN ({$placeholders})",
					'params' => $ids,
				);

			case 'self':
			default:
				return array(
					'where'  => "{$a}owner_employee_id = %d",
					'params' => array( $scope['employee_id'] ),
				);
		}
	}

	/**
	 * Direct report employee ids for a supervisor.
	 *
	 * @param int $supervisor_employee_id Supervisor employee id.
	 * @return int[]
	 */
	public static function team_employee_ids( $supervisor_employee_id ) {
		global $wpdb;
		$supervisor_employee_id = (int) $supervisor_employee_id;
		if ( ! $supervisor_employee_id ) {
			return array();
		}
		$table = MBD_KPI_DB::table( 'employees' );
		$rows  = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE supervisor_id = %d", $supervisor_employee_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		return array_map( 'intval', (array) $rows );
	}

	/**
	 * Whether the current user may view a specific registry KPI.
	 *
	 * @param array $registry Registry row (assoc).
	 * @return bool
	 */
	public static function can_view_registry( $registry ) {
		if ( empty( $registry ) ) {
			return false;
		}
		$scope = self::current_scope();
		switch ( $scope['scope'] ) {
			case 'all':
				return true;
			case 'division':
				return (int) $registry['division_id'] === (int) $scope['division_id'];
			case 'team':
				$ids   = self::team_employee_ids( $scope['employee_id'] );
				$ids[] = $scope['employee_id'];
				return in_array( (int) $registry['owner_employee_id'], array_map( 'intval', $ids ), true );
			case 'self':
			default:
				return (int) $registry['owner_employee_id'] === (int) $scope['employee_id'];
		}
	}
}
