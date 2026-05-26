<?php
/**
 * Activation / deactivation: schema creation, roles, defaults, seed data.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		MBD_KPI_Permissions::register_roles();
		self::default_settings();
		self::seed_examples();

		// Make sure /kpi rewrite rules are present, then flush.
		MBD_KPI_Router::register_rewrite_rules();
		flush_rewrite_rules();
		update_option( 'mbd_kpi_flush_needed', 1 );
		update_option( 'mbd_kpi_version', MBD_KPI_VERSION );
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Create all custom tables via dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( MBD_KPI_DB::schema() as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Seed default settings if not already set.
	 *
	 * @return void
	 */
	private static function default_settings() {
		if ( '' === mbd_kpi_get_setting( 'score_cap', '' ) ) {
			mbd_kpi_update_setting( 'score_cap', 120 );
		}
		if ( '' === mbd_kpi_get_setting( 'default_threshold_green', '' ) ) {
			mbd_kpi_update_setting( 'default_threshold_green', 100 );
			mbd_kpi_update_setting( 'default_threshold_yellow', 80 );
			mbd_kpi_update_setting( 'default_threshold_red', 0 );
		}
		if ( '' === mbd_kpi_get_setting( 'company_name', '' ) ) {
			mbd_kpi_update_setting( 'company_name', 'MBD Kontraktor' );
		}
		// Demo seed is opt-in and off by default to keep production clean.
		if ( '' === mbd_kpi_get_setting( 'enable_demo_seed', '' ) ) {
			mbd_kpi_update_setting( 'enable_demo_seed', false );
		}
	}

	/**
	 * Seed a handful of example KPIs (idempotent). Only runs once.
	 *
	 * @return void
	 */
	private static function seed_examples() {
		global $wpdb;

		// Opt-in only: never seed demo data unless explicitly enabled.
		if ( ! mbd_kpi_demo_seed_enabled() ) {
			return;
		}

		if ( get_option( 'mbd_kpi_seeded' ) ) {
			return;
		}

		$reg_table = MBD_KPI_DB::table( 'registry' );
		$existing  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$reg_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $existing > 0 ) {
			update_option( 'mbd_kpi_seeded', 1 );
			return;
		}

		// Seed a default division so KPIs have a home.
		$div_id = MBD_KPI_Service::save_division(
			array(
				'name'   => 'Company-wide',
				'code'   => 'CORP',
				'status' => 'active',
			)
		);

		$samples = array(
			array( 'Qualified Lead to Closing Rate', 'sales_revenue', 'customer', 'up', 'percent', 'sales' ),
			array( 'Daily Report Compliance', 'governance_improve', 'internal_process', 'up', 'percent', 'operations' ),
			array( 'Project Progress Variance', 'planning_quality', 'internal_process', 'down', 'percent', 'project' ),
			array( 'Financial Reporting Accuracy Rate', 'finance_commercial', 'financial', 'up', 'percent', 'finance' ),
			array( 'Campaign Execution Accuracy Rate', 'marketing_growth', 'customer', 'up', 'percent', 'marketing' ),
			array( 'Team Review & Evaluation Completion Score', 'people_learning', 'learning_growth', 'up', 'percent', 'hr' ),
			array( 'Improvement Findings Execution Score', 'governance_improve', 'internal_process', 'up', 'percent', 'improvement' ),
			array( 'Work Review Discipline Rate', 'governance_improve', 'internal_process', 'up', 'percent', 'operations' ),
			array( 'KOMISI Culture Implementation & Engagement Score', 'people_learning', 'learning_growth', 'up', 'index', 'culture' ),
			array( 'Client Satisfaction Score', 'client_experience', 'customer', 'up', 'index', 'client' ),
		);

		$i = 1;
		foreach ( $samples as $s ) {
			list( $name, $pillar, $perspective, $direction, $unit, $category ) = $s;
			MBD_KPI_Service::save_registry(
				array(
					'kpi_code'            => 'KPI-' . str_pad( (string) $i, 3, '0', STR_PAD_LEFT ),
					'kpi_name'            => $name,
					'business_definition' => $name . ' — sample KPI seeded for demonstration.',
					'category'            => $category,
					'bsc_perspective'     => $perspective,
					'strategic_pillar'    => $pillar,
					'division_id'         => $div_id,
					'owner_employee_id'   => 0,
					'formula_type'        => ( 'down' === $direction ) ? 'negative' : 'positive',
					'formula_description' => ( 'down' === $direction ) ? 'score = target / actual * 100' : 'score = actual / target * 100',
					'target_direction'    => $direction,
					'frequency'           => 'monthly',
					'data_source'         => 'Manual entry (MVP)',
					'evidence_required'   => 1,
					'threshold_green'     => 100,
					'threshold_yellow'    => 80,
					'threshold_red'       => 0,
					'unit'                => $unit,
					'weight'              => 1,
					'status'              => 'active',
				)
			);
			$i++;
		}

		update_option( 'mbd_kpi_seeded', 1 );
	}
}
