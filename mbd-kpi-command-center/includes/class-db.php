<?php
/**
 * Database layer: table name resolution and schema definitions.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_DB {

	/**
	 * Logical table keys (without prefix).
	 *
	 * @var string[]
	 */
	private static $tables = array(
		'divisions',
		'employees',
		'objectives',
		'key_results',
		'dictionary',
		'registry',
		'targets',
		'actuals',
		'scores',
		'scorecards',
		'weights',
		'action_plans',
		'action_updates',
		'evidence',
		'reviews',
		'review_items',
		'review_decisions',
		'root_causes',
		'escalations',
		'period_locks',
		'snapshots',
		'audit_logs',
		'settings',
	);

	/**
	 * Resolve a fully prefixed table name for a logical key.
	 *
	 * @param string $key Logical table key, e.g. 'registry'.
	 * @return string
	 */
	public static function table( $key ) {
		global $wpdb;
		return $wpdb->prefix . 'mbd_kpi_' . $key;
	}

	/**
	 * Return all logical table keys.
	 *
	 * @return string[]
	 */
	public static function table_keys() {
		return self::$tables;
	}

	/**
	 * SQL schema statements for dbDelta().
	 *
	 * @return string[] One CREATE TABLE statement per logical table.
	 */
	public static function schema() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$t       = array();
		foreach ( self::$tables as $key ) {
			$t[ $key ] = self::table( $key );
		}

		$sql = array();

		$sql['divisions'] = "CREATE TABLE {$t['divisions']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			code VARCHAR(40) NOT NULL DEFAULT '',
			parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			head_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			description TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY code (code),
			KEY parent_id (parent_id)
		) {$charset};";

		$sql['employees'] = "CREATE TABLE {$t['employees']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			full_name VARCHAR(190) NOT NULL,
			email VARCHAR(190) NOT NULL DEFAULT '',
			division_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			position VARCHAR(120) NOT NULL DEFAULT '',
			role_key VARCHAR(40) NOT NULL DEFAULT 'mbd_staff',
			supervisor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY division_id (division_id),
			KEY supervisor_id (supervisor_id)
		) {$charset};";

		$sql['objectives'] = "CREATE TABLE {$t['objectives']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			description TEXT NULL,
			period VARCHAR(20) NOT NULL DEFAULT '',
			owner_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			division_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(30) NOT NULL DEFAULT 'on_track',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY period (period),
			KEY division_id (division_id),
			KEY owner_employee_id (owner_employee_id)
		) {$charset};";

		$sql['key_results'] = "CREATE TABLE {$t['key_results']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			objective_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			title VARCHAR(255) NOT NULL,
			target_value DECIMAL(20,4) NOT NULL DEFAULT 0,
			current_value DECIMAL(20,4) NOT NULL DEFAULT 0,
			unit VARCHAR(40) NOT NULL DEFAULT '',
			progress DECIMAL(7,2) NOT NULL DEFAULT 0,
			confidence VARCHAR(20) NOT NULL DEFAULT 'medium',
			risk_note TEXT NULL,
			linked_kpi_ids VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'on_track',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY objective_id (objective_id)
		) {$charset};";

		$sql['dictionary'] = "CREATE TABLE {$t['dictionary']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			term_code VARCHAR(60) NOT NULL DEFAULT '',
			term_name VARCHAR(190) NOT NULL,
			business_definition TEXT NULL,
			category VARCHAR(80) NOT NULL DEFAULT '',
			formula_type VARCHAR(40) NOT NULL DEFAULT 'positive',
			formula_description TEXT NULL,
			unit VARCHAR(40) NOT NULL DEFAULT '',
			target_direction VARCHAR(10) NOT NULL DEFAULT 'up',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY term_code (term_code)
		) {$charset};";

		$sql['registry'] = "CREATE TABLE {$t['registry']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			kpi_code VARCHAR(60) NOT NULL DEFAULT '',
			kpi_name VARCHAR(190) NOT NULL,
			dictionary_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			business_definition TEXT NULL,
			category VARCHAR(80) NOT NULL DEFAULT '',
			bsc_perspective VARCHAR(60) NOT NULL DEFAULT '',
			strategic_pillar VARCHAR(80) NOT NULL DEFAULT '',
			division_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			owner_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			formula_type VARCHAR(40) NOT NULL DEFAULT 'positive',
			formula_description TEXT NULL,
			target_direction VARCHAR(10) NOT NULL DEFAULT 'up',
			frequency VARCHAR(20) NOT NULL DEFAULT 'monthly',
			data_source VARCHAR(190) NOT NULL DEFAULT '',
			evidence_required TINYINT(1) NOT NULL DEFAULT 0,
			threshold_green DECIMAL(7,2) NOT NULL DEFAULT 100,
			threshold_yellow DECIMAL(7,2) NOT NULL DEFAULT 80,
			threshold_red DECIMAL(7,2) NOT NULL DEFAULT 0,
			unit VARCHAR(40) NOT NULL DEFAULT '',
			weight DECIMAL(7,2) NOT NULL DEFAULT 1,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY kpi_code (kpi_code),
			KEY division_id (division_id),
			KEY owner_employee_id (owner_employee_id),
			KEY bsc_perspective (bsc_perspective),
			KEY strategic_pillar (strategic_pillar)
		) {$charset};";

		$sql['targets'] = "CREATE TABLE {$t['targets']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			period VARCHAR(20) NOT NULL DEFAULT '',
			target_value DECIMAL(20,4) NOT NULL DEFAULT 0,
			stretch_value DECIMAL(20,4) NULL,
			note TEXT NULL,
			set_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY registry_period (registry_id, period)
		) {$charset};";

		$sql['actuals'] = "CREATE TABLE {$t['actuals']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			period VARCHAR(20) NOT NULL DEFAULT '',
			actual_value DECIMAL(20,4) NOT NULL DEFAULT 0,
			note TEXT NULL,
			is_manual TINYINT(1) NOT NULL DEFAULT 1,
			submitted_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			submitted_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			verification_status VARCHAR(20) NOT NULL DEFAULT 'pending',
			verified_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			verified_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY registry_period (registry_id, period),
			KEY verification_status (verification_status)
		) {$charset};";

		$sql['scores'] = "CREATE TABLE {$t['scores']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			period VARCHAR(20) NOT NULL DEFAULT '',
			target_value DECIMAL(20,4) NOT NULL DEFAULT 0,
			actual_value DECIMAL(20,4) NOT NULL DEFAULT 0,
			performance_score DECIMAL(7,2) NOT NULL DEFAULT 0,
			data_health_score DECIMAL(7,2) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'missing',
			is_snapshot TINYINT(1) NOT NULL DEFAULT 0,
			computed_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY registry_period_snap (registry_id, period, is_snapshot),
			KEY status (status)
		) {$charset};";

		$sql['scorecards'] = "CREATE TABLE {$t['scorecards']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			scope_type VARCHAR(20) NOT NULL DEFAULT 'company',
			scope_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			framework VARCHAR(20) NOT NULL DEFAULT 'bsc',
			period VARCHAR(20) NOT NULL DEFAULT '',
			total_score DECIMAL(7,2) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY scope (scope_type, scope_id),
			KEY period (period)
		) {$charset};";

		$sql['weights'] = "CREATE TABLE {$t['weights']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			context_type VARCHAR(30) NOT NULL DEFAULT 'perspective',
			context_key VARCHAR(80) NOT NULL DEFAULT '',
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			division_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			weight DECIMAL(7,2) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY context (context_type, context_key)
		) {$charset};";

		$sql['action_plans'] = "CREATE TABLE {$t['action_plans']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			objective_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			key_result_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			period VARCHAR(20) NOT NULL DEFAULT '',
			problem_statement TEXT NULL,
			root_cause_category VARCHAR(120) NOT NULL DEFAULT '',
			root_cause_detail TEXT NULL,
			corrective_action TEXT NULL,
			preventive_action TEXT NULL,
			owner_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			due_date DATE NULL,
			priority VARCHAR(20) NOT NULL DEFAULT 'medium',
			status VARCHAR(30) NOT NULL DEFAULT 'open',
			reviewer_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			effectiveness_check VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY registry_id (registry_id),
			KEY owner_employee_id (owner_employee_id),
			KEY status (status)
		) {$charset};";

		$sql['action_updates'] = "CREATE TABLE {$t['action_updates']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			action_plan_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			update_note TEXT NULL,
			status VARCHAR(30) NOT NULL DEFAULT '',
			updated_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY action_plan_id (action_plan_id)
		) {$charset};";

		$sql['evidence'] = "CREATE TABLE {$t['evidence']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			entity_type VARCHAR(30) NOT NULL DEFAULT 'actual',
			entity_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			evidence_type VARCHAR(30) NOT NULL DEFAULT 'file',
			title VARCHAR(190) NOT NULL DEFAULT '',
			file_url TEXT NULL,
			link_url TEXT NULL,
			note TEXT NULL,
			uploaded_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			verification_status VARCHAR(20) NOT NULL DEFAULT 'pending',
			verified_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			verified_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY entity (entity_type, entity_id),
			KEY verification_status (verification_status)
		) {$charset};";

		$sql['reviews'] = "CREATE TABLE {$t['reviews']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			review_type VARCHAR(40) NOT NULL DEFAULT 'weekly_division',
			title VARCHAR(190) NOT NULL,
			period VARCHAR(20) NOT NULL DEFAULT '',
			scope_type VARCHAR(20) NOT NULL DEFAULT 'company',
			scope_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			scheduled_date DATE NULL,
			facilitator_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			summary TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY review_type (review_type),
			KEY period (period)
		) {$charset};";

		$sql['review_items'] = "CREATE TABLE {$t['review_items']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			review_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			objective_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action_plan_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			item_type VARCHAR(30) NOT NULL DEFAULT 'kpi',
			note TEXT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'open',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY review_id (review_id)
		) {$charset};";

		$sql['review_decisions'] = "CREATE TABLE {$t['review_decisions']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			review_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			decision_text TEXT NULL,
			action_item TEXT NULL,
			owner_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			due_date DATE NULL,
			escalation_path VARCHAR(190) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'open',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY review_id (review_id)
		) {$charset};";

		$sql['root_causes'] = "CREATE TABLE {$t['root_causes']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			action_plan_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			category VARCHAR(120) NOT NULL DEFAULT '',
			detail TEXT NULL,
			occurrence_count INT UNSIGNED NOT NULL DEFAULT 1,
			period VARCHAR(20) NOT NULL DEFAULT '',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY registry_id (registry_id),
			KEY category (category)
		) {$charset};";

		$sql['escalations'] = "CREATE TABLE {$t['escalations']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			registry_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action_plan_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			reason TEXT NULL,
			level VARCHAR(30) NOT NULL DEFAULT 'supervisor',
			from_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			to_employee_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			period VARCHAR(20) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			resolved_at DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY registry_id (registry_id),
			KEY status (status)
		) {$charset};";

		$sql['period_locks'] = "CREATE TABLE {$t['period_locks']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			period_type VARCHAR(10) NOT NULL DEFAULT 'month',
			period VARCHAR(20) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'locked',
			note TEXT NULL,
			locked_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			locked_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY period (period)
		) {$charset};";

		$sql['snapshots'] = "CREATE TABLE {$t['snapshots']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			period VARCHAR(20) NOT NULL DEFAULT '',
			snapshot_type VARCHAR(30) NOT NULL DEFAULT 'period_lock',
			payload LONGTEXT NULL,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY period (period)
		) {$charset};";

		$sql['audit_logs'] = "CREATE TABLE {$t['audit_logs']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(80) NOT NULL DEFAULT '',
			object_type VARCHAR(60) NOT NULL DEFAULT '',
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			old_value LONGTEXT NULL,
			new_value LONGTEXT NULL,
			ip_address VARCHAR(60) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY object (object_type, object_id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset};";

		$sql['settings'] = "CREATE TABLE {$t['settings']} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			setting_key VARCHAR(120) NOT NULL,
			setting_value LONGTEXT NULL,
			autoload TINYINT(1) NOT NULL DEFAULT 1,
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset};";

		return $sql;
	}
}
