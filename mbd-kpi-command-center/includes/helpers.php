<?php
/**
 * Shared helper functions used across services and views.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a plugin setting from the settings table, with default fallback.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value if missing.
 * @return mixed
 */
function mbd_kpi_get_setting( $key, $default = '' ) {
	global $wpdb;
	$table = MBD_KPI_DB::table( 'settings' );
	$value = $wpdb->get_var( $wpdb->prepare( "SELECT setting_value FROM {$table} WHERE setting_key = %s", $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	if ( null === $value ) {
		return $default;
	}
	$decoded = json_decode( $value, true );
	return ( null === $decoded && 'null' !== $value ) ? $value : $decoded;
}

/**
 * Persist a plugin setting.
 *
 * @param string $key   Setting key.
 * @param mixed  $value Value (will be JSON encoded).
 * @return void
 */
function mbd_kpi_update_setting( $key, $value ) {
	global $wpdb;
	$table = MBD_KPI_DB::table( 'settings' );
	$now = current_time( 'mysql' );
	$wpdb->replace(
		$table,
		array(
			'setting_key'   => $key,
			'setting_value' => wp_json_encode( $value ),
			'autoload'      => 'yes',
			'created_at'    => $now,
			'updated_at'    => $now,
		),
		array( '%s', '%s', '%s', '%s', '%s' )
	);
}

/**
 * Current MySQL timestamp using the site timezone.
 *
 * @return string
 */
function mbd_kpi_now() {
	return current_time( 'mysql' );
}

/**
 * Whether demonstration seed data (example KPIs) is allowed.
 *
 * Disabled by default so production installs are never silently populated
 * with sample data. Enable via the `MBD_KPI_ENABLE_DEMO_SEED` constant
 * (e.g. in wp-config.php) or the `enable_demo_seed` plugin setting, then
 * (re)activate the plugin.
 *
 * @return bool
 */
function mbd_kpi_demo_seed_enabled() {
	if ( defined( 'MBD_KPI_ENABLE_DEMO_SEED' ) && MBD_KPI_ENABLE_DEMO_SEED ) {
		return true;
	}
	return (bool) mbd_kpi_get_setting( 'enable_demo_seed', false );
}

/**
 * Sanitize a period string. Accepts: 2026-05 (month), 2026-Q2 (quarter), 2026 (year).
 *
 * @param string $period Raw period.
 * @return string Sanitized period or '' if invalid.
 */
function mbd_kpi_sanitize_period( $period ) {
	$period = strtoupper( trim( (string) $period ) );
	if ( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $period ) ) {
		return $period; // Monthly.
	}
	if ( preg_match( '/^\d{4}-Q[1-4]$/', $period ) ) {
		return $period; // Quarterly.
	}
	if ( preg_match( '/^\d{4}$/', $period ) ) {
		return $period; // Yearly.
	}
	return '';
}

/**
 * Detect the period type from a period string.
 *
 * @param string $period Period string.
 * @return string month|quarter|year|unknown
 */
function mbd_kpi_period_type( $period ) {
	if ( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $period ) ) {
		return 'month';
	}
	if ( preg_match( '/^\d{4}-Q[1-4]$/', $period ) ) {
		return 'quarter';
	}
	if ( preg_match( '/^\d{4}$/', $period ) ) {
		return 'year';
	}
	return 'unknown';
}

/**
 * Default current monthly period e.g. 2026-05.
 *
 * @return string
 */
function mbd_kpi_current_period() {
	return gmdate( 'Y-m', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
}

/**
 * Compute the period immediately preceding the given period.
 *
 * @param string $period Period string (monthly or quarterly).
 * @return string Previous period, or '' if not derivable.
 */
function mbd_kpi_previous_period( $period ) {
	$type = mbd_kpi_period_type( $period );
	if ( 'month' === $type ) {
		$ts = strtotime( $period . '-01 -1 month' );
		return $ts ? gmdate( 'Y-m', $ts ) : '';
	}
	if ( 'quarter' === $type ) {
		list( $year, $q ) = explode( '-Q', $period );
		$year = (int) $year;
		$q    = (int) $q;
		$q--;
		if ( $q < 1 ) {
			$q = 4;
			$year--;
		}
		return $year . '-Q' . $q;
	}
	if ( 'year' === $type ) {
		return (string) ( (int) $period - 1 );
	}
	return '';
}

/**
 * Build a list of selectable periods (recent months + quarters).
 *
 * @return string[]
 */
function mbd_kpi_period_options() {
	$out = array();
	$ts  = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	for ( $i = 0; $i < 12; $i++ ) {
		$out[] = gmdate( 'Y-m', strtotime( "-{$i} month", $ts ) );
	}
	$year    = (int) gmdate( 'Y', $ts );
	$quarter = (int) ceil( ( (int) gmdate( 'n', $ts ) ) / 3 );
	for ( $q = $quarter; $q >= 1; $q-- ) {
		$out[] = $year . '-Q' . $q;
	}
	$out[] = (string) $year;
	return array_values( array_unique( $out ) );
}

/**
 * BSC perspectives map.
 *
 * @return array<string,string>
 */
function mbd_kpi_bsc_perspectives() {
	return array(
		'financial'        => __( 'Financial', 'mbd-kpi' ),
		'customer'         => __( 'Customer', 'mbd-kpi' ),
		'internal_process' => __( 'Internal Process', 'mbd-kpi' ),
		'learning_growth'  => __( 'Learning & Growth', 'mbd-kpi' ),
	);
}

/**
 * MBD strategic pillars map.
 *
 * @return array<string,string>
 */
function mbd_kpi_strategic_pillars() {
	return array(
		'sales_revenue'        => __( 'Sales & Revenue Growth', 'mbd-kpi' ),
		'planning_quality'     => __( 'Planning, Quality & Delivery Excellence', 'mbd-kpi' ),
		'finance_commercial'   => __( 'Finance & Commercial Control', 'mbd-kpi' ),
		'client_experience'    => __( 'Client Experience & Trust', 'mbd-kpi' ),
		'marketing_growth'     => __( 'Marketing & Growth System', 'mbd-kpi' ),
		'people_learning'      => __( 'People, Learning & Culture', 'mbd-kpi' ),
		'governance_improve'   => __( 'Governance, Compliance & Improvement', 'mbd-kpi' ),
	);
}

/**
 * Action plan status labels.
 *
 * @return array<string,string>
 */
function mbd_kpi_action_statuses() {
	return array(
		'open'                     => __( 'Open', 'mbd-kpi' ),
		'in_progress'              => __( 'In Progress', 'mbd-kpi' ),
		'blocked'                  => __( 'Blocked', 'mbd-kpi' ),
		'done_pending_verification' => __( 'Done – Pending Verification', 'mbd-kpi' ),
		'verified_effective'       => __( 'Verified Effective', 'mbd-kpi' ),
		'closed_ineffective'       => __( 'Closed – Ineffective', 'mbd-kpi' ),
		'reopened'                 => __( 'Reopened', 'mbd-kpi' ),
	);
}

/**
 * Evidence verification statuses.
 *
 * @return array<string,string>
 */
function mbd_kpi_verification_statuses() {
	return array(
		'pending'       => __( 'Pending', 'mbd-kpi' ),
		'approved'      => __( 'Approved', 'mbd-kpi' ),
		'rejected'      => __( 'Rejected', 'mbd-kpi' ),
		'need_revision' => __( 'Need Revision', 'mbd-kpi' ),
	);
}

/**
 * Evidence types.
 *
 * @return array<string,string>
 */
function mbd_kpi_evidence_types() {
	return array(
		'file'        => __( 'File Upload', 'mbd-kpi' ),
		'image'       => __( 'Image', 'mbd-kpi' ),
		'pdf'         => __( 'PDF', 'mbd-kpi' ),
		'gdrive'      => __( 'Google Drive Link', 'mbd-kpi' ),
		'screenshot'  => __( 'Screenshot', 'mbd-kpi' ),
		'document'    => __( 'Document Link', 'mbd-kpi' ),
		'review_note' => __( 'Review Note', 'mbd-kpi' ),
	);
}

/**
 * Review types.
 *
 * @return array<string,string>
 */
function mbd_kpi_review_types() {
	return array(
		'daily_check'        => __( 'Daily Check', 'mbd-kpi' ),
		'weekly_executive'   => __( 'Weekly Executive Review', 'mbd-kpi' ),
		'weekly_division'    => __( 'Weekly Division Review', 'mbd-kpi' ),
		'monthly_business'   => __( 'Monthly Business Performance Review', 'mbd-kpi' ),
		'monthly_improve'    => __( 'Monthly Improvement Review', 'mbd-kpi' ),
		'quarterly_strategic' => __( 'Quarterly Strategic Review', 'mbd-kpi' ),
	);
}

/**
 * Render a status badge for a KPI status.
 *
 * @param string $status green|yellow|red|missing|stale.
 * @return string HTML.
 */
function mbd_kpi_status_badge( $status ) {
	$labels = array(
		'green'   => __( 'Green', 'mbd-kpi' ),
		'yellow'  => __( 'Yellow', 'mbd-kpi' ),
		'red'     => __( 'Red', 'mbd-kpi' ),
		'missing' => __( 'Missing', 'mbd-kpi' ),
		'stale'   => __( 'Stale', 'mbd-kpi' ),
	);
	$class = isset( $labels[ $status ] ) ? $status : 'missing';
	$label = isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
	return '<span class="mbd-badge mbd-badge-' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Generic pill badge for arbitrary text.
 *
 * @param string $text  Text to display.
 * @param string $tone  Tone class suffix.
 * @return string HTML.
 */
function mbd_kpi_pill( $text, $tone = 'default' ) {
	return '<span class="mbd-pill mbd-pill-' . esc_attr( $tone ) . '">' . esc_html( $text ) . '</span>';
}

/**
 * Build a /kpi app URL for the given page slug.
 *
 * @param string $page  Page slug ('' for dashboard).
 * @param array  $args  Query args.
 * @return string
 */
function mbd_kpi_url( $page = '', $args = array() ) {
	$base = home_url( '/kpi' . ( $page ? '/' . $page : '' ) );
	if ( ! empty( $args ) ) {
		$base = add_query_arg( $args, $base );
	}
	return $base;
}

/**
 * Format a numeric value for display.
 *
 * @param mixed  $value Value.
 * @param string $unit  Optional unit.
 * @return string
 */
function mbd_kpi_format_value( $value, $unit = '' ) {
	if ( null === $value || '' === $value ) {
		return '&mdash;';
	}
	$num = (float) $value;
	$str = ( floor( $num ) == $num ) ? number_format_i18n( $num ) : number_format_i18n( $num, 2 );
	return $unit ? $str . ' ' . esc_html( $unit ) : $str;
}

/**
 * Verify the standard plugin nonce for a POST action.
 *
 * @param string $action Nonce action.
 * @return bool
 */
function mbd_kpi_verify_nonce( $action ) {
	$nonce = isset( $_POST['mbd_kpi_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mbd_kpi_nonce'] ) ) : '';
	return (bool) wp_verify_nonce( $nonce, $action );
}

/**
 * Output a nonce field for a POST action.
 *
 * @param string $action Nonce action.
 * @return void
 */
function mbd_kpi_nonce_field( $action ) {
	wp_nonce_field( $action, 'mbd_kpi_nonce' );
}
