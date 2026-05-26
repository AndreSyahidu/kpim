<?php
/**
 * Score engine: performance score, status classification, data health score,
 * and persistence of computed scores.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Score_Engine {

	/**
	 * Configurable maximum performance score cap.
	 *
	 * @return float
	 */
	public static function score_cap() {
		$cap = (float) mbd_kpi_get_setting( 'score_cap', 120 );
		return $cap > 0 ? $cap : 120;
	}

	/**
	 * Number of days after which an actual is considered stale, by frequency.
	 *
	 * @param string $frequency daily|weekly|monthly|quarterly|yearly.
	 * @return int Days.
	 */
	public static function staleness_days( $frequency ) {
		$map = array(
			'daily'     => 3,
			'weekly'    => 14,
			'monthly'   => 45,
			'quarterly' => 135,
			'yearly'    => 400,
		);
		return isset( $map[ $frequency ] ) ? (int) $map[ $frequency ] : 45;
	}

	/**
	 * Compute the raw performance score (uncapped) from target / actual.
	 *
	 * Positive KPI (target_direction up):  actual / target * 100
	 * Negative KPI (target_direction down): target / actual * 100
	 *
	 * @param float  $target           Target value.
	 * @param float  $actual           Actual value.
	 * @param string $target_direction up|down.
	 * @return float|null Score or null if not computable.
	 */
	public static function raw_score( $target, $actual, $target_direction = 'up' ) {
		$target = (float) $target;
		$actual = (float) $actual;

		if ( 'down' === $target_direction ) {
			// Lower is better. If actual is 0 it beats any positive target.
			if ( 0.0 === $actual ) {
				return ( $target > 0 ) ? self::score_cap() : 100.0;
			}
			return ( $target / $actual ) * 100;
		}

		// Positive: higher is better.
		if ( 0.0 === $target ) {
			return ( $actual > 0 ) ? self::score_cap() : 0.0;
		}
		return ( $actual / $target ) * 100;
	}

	/**
	 * Apply the configured cap to a raw score.
	 *
	 * @param float $score Raw score.
	 * @return float
	 */
	public static function cap_score( $score ) {
		$cap = self::score_cap();
		$score = max( 0, (float) $score );
		return min( $score, $cap );
	}

	/**
	 * Classify a status from a score and registry thresholds.
	 *
	 * @param float|null $score    Performance score (null = no data).
	 * @param array      $registry Registry row.
	 * @param bool       $is_stale Whether the underlying data is stale.
	 * @return string green|yellow|red|missing|stale
	 */
	public static function classify( $score, $registry, $is_stale = false ) {
		if ( null === $score ) {
			return 'missing';
		}
		if ( $is_stale ) {
			return 'stale';
		}
		$green  = (float) $registry['threshold_green'];
		$yellow = (float) $registry['threshold_yellow'];

		if ( $score >= $green ) {
			return 'green';
		}
		if ( $score >= $yellow ) {
			return 'yellow';
		}
		return 'red';
	}

	/**
	 * Compute a 0-100 data health score for a KPI period.
	 *
	 * Factors (weighted):
	 *  - on_time     : actual submitted within freshness window (25)
	 *  - evidence    : evidence present when required (25)
	 *  - verified    : actual verification approved (25)
	 *  - source      : automated source scores higher than manual (10)
	 *  - freshness   : how recent the submission is (15)
	 *
	 * @param array      $registry Registry row.
	 * @param array|null $actual   Actual row (or null).
	 * @param int        $evidence_count Approved evidence count for the entity.
	 * @return float
	 */
	public static function data_health( $registry, $actual, $evidence_count = 0 ) {
		if ( empty( $actual ) ) {
			return 0.0;
		}

		$score = 0.0;
		$now   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
		$submitted_ts = ! empty( $actual['submitted_at'] ) ? strtotime( $actual['submitted_at'] ) : 0;
		$age_days     = $submitted_ts ? ( ( $now - $submitted_ts ) / DAY_IN_SECONDS ) : 9999;
		$stale_after  = self::staleness_days( $registry['frequency'] );

		// On time.
		if ( $submitted_ts && $age_days <= $stale_after ) {
			$score += 25;
		}

		// Evidence requirement.
		if ( (int) $registry['evidence_required'] ) {
			$score += ( $evidence_count > 0 ) ? 25 : 0;
		} else {
			$score += 20; // Not required: most of the credit, but reward verified evidence below.
		}

		// Verified actual.
		if ( 'approved' === $actual['verification_status'] ) {
			$score += 25;
		} elseif ( 'need_revision' === $actual['verification_status'] ) {
			$score += 8;
		}

		// Source type: manual entries are inherently lower trust.
		$score += ( (int) $actual['is_manual'] ) ? 5 : 10;

		// Freshness gradient.
		if ( $submitted_ts ) {
			$ratio = max( 0, 1 - ( $age_days / max( 1, $stale_after ) ) );
			$score += 15 * $ratio;
		}

		return round( min( 100, $score ), 2 );
	}

	/**
	 * Compute the full score package for a registry + period and persist it
	 * to the scores table (non-snapshot live row).
	 *
	 * @param array  $registry Registry row.
	 * @param string $period   Period string.
	 * @return array Computed score package.
	 */
	public static function compute_and_store( $registry, $period ) {
		global $wpdb;

		$registry_id = (int) $registry['id'];
		$period       = mbd_kpi_sanitize_period( $period );

		$target_tbl = MBD_KPI_DB::table( 'targets' );
		$actual_tbl = MBD_KPI_DB::table( 'actuals' );
		$ev_tbl     = MBD_KPI_DB::table( 'evidence' );

		$target_value = $wpdb->get_var( $wpdb->prepare( "SELECT target_value FROM {$target_tbl} WHERE registry_id = %d AND period = %s", $registry_id, $period ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$actual       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$actual_tbl} WHERE registry_id = %d AND period = %s", $registry_id, $period ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		$evidence_count = 0;
		if ( $actual ) {
			$evidence_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$ev_tbl} WHERE entity_type = 'actual' AND entity_id = %d AND verification_status = 'approved'", (int) $actual['id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		$has_data = ( null !== $actual && '' !== $actual['actual_value'] );

		// Staleness.
		$is_stale = false;
		if ( $has_data && ! empty( $actual['submitted_at'] ) ) {
			$age_days    = ( current_time( 'timestamp' ) - strtotime( $actual['submitted_at'] ) ) / DAY_IN_SECONDS; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$is_stale    = $age_days > self::staleness_days( $registry['frequency'] );
		}

		if ( ! $has_data ) {
			$performance = null;
			$status      = 'missing';
		} else {
			$raw         = self::raw_score( (float) $target_value, (float) $actual['actual_value'], $registry['target_direction'] );
			$performance = self::cap_score( $raw );
			$status      = self::classify( $performance, $registry, $is_stale );
		}

		$data_health = self::data_health( $registry, $actual, $evidence_count );

		// Persist the live (non-snapshot) score.
		$wpdb->replace(
			MBD_KPI_DB::table( 'scores' ),
			array(
				'registry_id'       => $registry_id,
				'period'            => $period,
				'target_value'      => (float) $target_value,
				'actual_value'      => $has_data ? (float) $actual['actual_value'] : 0,
				'performance_score' => ( null === $performance ) ? 0 : $performance,
				'data_health_score' => $data_health,
				'status'            => $status,
				'is_snapshot'       => 0,
				'computed_at'       => mbd_kpi_now(),
				'created_at'        => mbd_kpi_now(),
			),
			array( '%d', '%s', '%f', '%f', '%f', '%f', '%s', '%d', '%s', '%s' )
		);

		return array(
			'registry_id'       => $registry_id,
			'period'            => $period,
			'target_value'      => ( null === $target_value ) ? null : (float) $target_value,
			'actual_value'      => $has_data ? (float) $actual['actual_value'] : null,
			'performance_score' => $performance,
			'data_health_score' => $data_health,
			'status'            => $status,
			'is_stale'          => $is_stale,
			'has_data'          => $has_data,
			'evidence_count'    => $evidence_count,
		);
	}

	/**
	 * Compute scores for many registry rows in a period (live, non-persisted
	 * read where a snapshot exists; otherwise compute + store).
	 *
	 * @param array  $registry_rows Registry rows.
	 * @param string $period        Period.
	 * @return array[] Keyed by registry id.
	 */
	public static function compute_many( $registry_rows, $period ) {
		$out = array();
		foreach ( $registry_rows as $row ) {
			$snapshot = self::get_snapshot( (int) $row['id'], $period );
			if ( $snapshot ) {
				$out[ (int) $row['id'] ] = array(
					'registry_id'       => (int) $row['id'],
					'period'            => $period,
					'target_value'      => (float) $snapshot['target_value'],
					'actual_value'      => (float) $snapshot['actual_value'],
					'performance_score' => 'missing' === $snapshot['status'] ? null : (float) $snapshot['performance_score'],
					'data_health_score' => (float) $snapshot['data_health_score'],
					'status'            => $snapshot['status'],
					'is_stale'          => 'stale' === $snapshot['status'],
					'has_data'          => 'missing' !== $snapshot['status'],
					'evidence_count'    => 0,
					'is_snapshot'       => true,
				);
			} else {
				$out[ (int) $row['id'] ] = self::compute_and_store( $row, $period );
			}
		}
		return $out;
	}

	/**
	 * Return a locked snapshot score row if it exists.
	 *
	 * @param int    $registry_id Registry id.
	 * @param string $period      Period.
	 * @return array|null
	 */
	public static function get_snapshot( $registry_id, $period ) {
		global $wpdb;
		$table = MBD_KPI_DB::table( 'scores' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE registry_id = %d AND period = %s AND is_snapshot = 1", (int) $registry_id, $period ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $row ?: null;
	}
}
