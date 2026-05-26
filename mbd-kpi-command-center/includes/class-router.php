<?php
/**
 * Front-end router for the /kpi application.
 *
 * Registers rewrite rules, gates access, handles POST (PRG pattern) and
 * renders the layout + the requested app view.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Router {

	/**
	 * Valid page slug => app view file.
	 *
	 * @var array<string,string>
	 */
	private $pages = array(
		'dashboard'    => 'dashboard.php',
		'scorecard'    => 'scorecard.php',
		'okr'          => 'okr.php',
		'kpi-registry' => 'kpi-registry.php',
		'kpi-actual'   => 'kpi-actual.php',
		'action-plan'  => 'action-plan.php',
		'evidence'     => 'evidence.php',
		'review'       => 'review.php',
		'team'         => 'team.php',
		'my'           => 'my-kpi.php',
		'exception'    => 'exception.php',
		'settings'     => 'settings.php',
	);

	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'dispatch' ) );
	}

	/**
	 * Register the /kpi rewrite rules.
	 *
	 * @return void
	 */
	public static function register_rewrite_rules() {
		add_rewrite_rule( '^kpi/?$', 'index.php?mbd_kpi_page=dashboard', 'top' );
		add_rewrite_rule( '^kpi/([a-z0-9\-]+)/?$', 'index.php?mbd_kpi_page=$matches[1]', 'top' );
	}

	/**
	 * Register query vars.
	 *
	 * @param string[] $vars Existing vars.
	 * @return string[]
	 */
	public function query_vars( $vars ) {
		$vars[] = 'mbd_kpi_page';
		return $vars;
	}

	/**
	 * Dispatch the request: auth gate, POST handling, view rendering.
	 *
	 * @return void
	 */
	public function dispatch() {
		$page = get_query_var( 'mbd_kpi_page' );
		if ( '' === $page || null === $page ) {
			return; // Not our route.
		}

		$page = sanitize_key( $page );
		if ( ! isset( $this->pages[ $page ] ) ) {
			$page = 'dashboard';
		}

		// --- Auth gate -----------------------------------------------------
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( mbd_kpi_url( 'dashboard' === $page ? '' : $page ) ) );
			exit;
		}
		if ( ! MBD_KPI_Permissions::can_access() ) {
			$this->render_denied();
			exit;
		}

		// --- POST handling (before any output) -----------------------------
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['mbd_kpi_form'] ) ) {
			$this->handle_post( $page );
			exit; // handle_post always redirects.
		}

		// --- Render --------------------------------------------------------
		$this->enqueue_assets();
		$this->render( $page );
		exit;
	}

	/**
	 * Enqueue front-end CSS/JS for the app.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		MBD_KPI_Assets::enqueue_app();
	}

	/**
	 * Store a flash message for the current user.
	 *
	 * @param string $type    success|error|info.
	 * @param string $message Message text.
	 * @return void
	 */
	public static function flash( $type, $message ) {
		set_transient( 'mbd_kpi_flash_' . get_current_user_id(), array( 'type' => $type, 'message' => $message ), 60 );
	}

	/**
	 * Pull (and clear) the current user's flash message.
	 *
	 * @return array|null
	 */
	public static function get_flash() {
		$key   = 'mbd_kpi_flash_' . get_current_user_id();
		$flash = get_transient( $key );
		if ( $flash ) {
			delete_transient( $key );
			return $flash;
		}
		return null;
	}

	/**
	 * Redirect back to a /kpi page after a POST.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query args.
	 * @return void
	 */
	private function redirect_back( $page, $args = array() ) {
		$target = ( 'dashboard' === $page ) ? '' : $page;
		wp_safe_redirect( mbd_kpi_url( $target, $args ) );
		exit;
	}

	/**
	 * Handle a POST submission. Each form supplies mbd_kpi_form + nonce.
	 *
	 * @param string $page Current page slug.
	 * @return void
	 */
	private function handle_post( $page ) {
		$form = sanitize_key( wp_unslash( $_POST['mbd_kpi_form'] ) );

		// Every form uses the same nonce action namespaced by the form key.
		if ( ! mbd_kpi_verify_nonce( 'mbd_kpi_' . $form ) ) {
			self::flash( 'error', __( 'Security check failed. Please try again.', 'mbd-kpi' ) );
			$this->redirect_back( $page );
		}

		$data = wp_unslash( $_POST );

		switch ( $form ) {

			case 'save_division':
				$this->require_cap( 'mbd_kpi_manage_settings', $page );
				MBD_KPI_Service::save_division( $data );
				self::flash( 'success', __( 'Division saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_employee':
				$this->require_cap( 'mbd_kpi_manage_settings', $page );
				MBD_KPI_Service::save_employee( $data );
				self::flash( 'success', __( 'Employee saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_dictionary':
				$this->require_cap( 'mbd_kpi_manage_registry', $page );
				MBD_KPI_Service::save_dictionary_term( $data );
				self::flash( 'success', __( 'Dictionary term saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_registry':
				$this->require_cap( 'mbd_kpi_manage_registry', $page );
				MBD_KPI_Service::save_registry( $data );
				self::flash( 'success', __( 'KPI saved to registry.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_target':
				$this->require_cap( 'mbd_kpi_manage_registry', $page );
				$res = MBD_KPI_Service::save_target( $data );
				self::flash( $res['ok'] ? 'success' : 'error', $res['message'] );
				$this->redirect_back( $page, array( 'registry_id' => (int) ( $data['registry_id'] ?? 0 ) ) );
				break;

			case 'save_actual':
				$this->require_cap( 'mbd_kpi_input_actual', $page );
				$res = MBD_KPI_Service::save_actual( $data );
				self::flash( $res['ok'] ? 'success' : 'error', $res['message'] );
				$this->redirect_back( $page );
				break;

			case 'verify_actual':
				$this->require_cap( 'mbd_kpi_verify', $page );
				MBD_KPI_Service::verify_actual( (int) ( $data['actual_id'] ?? 0 ), sanitize_text_field( $data['verification_status'] ?? '' ) );
				self::flash( 'success', __( 'Actual verification updated.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_objective':
				$this->require_cap( 'mbd_kpi_manage_okr', $page );
				MBD_KPI_OKR_Service::save_objective( $data );
				self::flash( 'success', __( 'Objective saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_key_result':
				$this->require_cap( 'mbd_kpi_manage_okr', $page );
				MBD_KPI_OKR_Service::save_key_result( $data );
				self::flash( 'success', __( 'Key result saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page, array( 'objective_id' => (int) ( $data['objective_id'] ?? 0 ) ) );
				break;

			case 'save_action_plan':
				$this->require_cap( 'mbd_kpi_manage_action', $page );
				$res = MBD_KPI_Action_Plan_Service::save_action_plan( $data );
				self::flash( $res['ok'] ? 'success' : 'error', $res['message'] );
				$this->redirect_back( $page );
				break;

			case 'add_action_update':
				$this->require_cap( 'mbd_kpi_manage_action', $page );
				MBD_KPI_Action_Plan_Service::add_update( (int) ( $data['action_plan_id'] ?? 0 ), $data['update_note'] ?? '', sanitize_text_field( $data['status'] ?? '' ) );
				self::flash( 'success', __( 'Update added.', 'mbd-kpi' ) );
				$this->redirect_back( $page, array( 'action_plan_id' => (int) ( $data['action_plan_id'] ?? 0 ) ) );
				break;

			case 'save_evidence':
				$this->require_cap( 'mbd_kpi_input_actual', $page );
				$file = isset( $_FILES['evidence_file'] ) ? $_FILES['evidence_file'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$res  = MBD_KPI_Action_Plan_Service::save_evidence( $data, $file );
				self::flash( $res['ok'] ? 'success' : 'error', $res['message'] );
				$this->redirect_back( $page );
				break;

			case 'verify_evidence':
				$this->require_cap( 'mbd_kpi_verify', $page );
				MBD_KPI_Action_Plan_Service::verify_evidence( (int) ( $data['evidence_id'] ?? 0 ), sanitize_text_field( $data['verification_status'] ?? '' ) );
				self::flash( 'success', __( 'Evidence verification updated.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			case 'save_review':
				$this->require_cap( 'mbd_kpi_manage_review', $page );
				$rid = MBD_KPI_Review_Service::save_review( $data );
				self::flash( 'success', __( 'Review session saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page, array( 'review_id' => $rid ) );
				break;

			case 'add_review_item':
				$this->require_cap( 'mbd_kpi_manage_review', $page );
				MBD_KPI_Review_Service::add_item( $data );
				self::flash( 'success', __( 'Review item added.', 'mbd-kpi' ) );
				$this->redirect_back( $page, array( 'review_id' => (int) ( $data['review_id'] ?? 0 ) ) );
				break;

			case 'add_review_decision':
				$this->require_cap( 'mbd_kpi_manage_review', $page );
				MBD_KPI_Review_Service::add_decision( $data );
				self::flash( 'success', __( 'Decision recorded.', 'mbd-kpi' ) );
				$this->redirect_back( $page, array( 'review_id' => (int) ( $data['review_id'] ?? 0 ) ) );
				break;

			case 'lock_period':
				$this->require_cap( 'mbd_kpi_manage_settings', $page );
				$this->lock_period( $data, true );
				$this->redirect_back( $page );
				break;

			case 'unlock_period':
				$this->require_cap( 'mbd_kpi_manage_settings', $page );
				$this->lock_period( $data, false );
				$this->redirect_back( $page );
				break;

			case 'save_settings':
				$this->require_cap( 'mbd_kpi_manage_settings', $page );
				$this->save_settings( $data );
				self::flash( 'success', __( 'Settings saved.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;

			default:
				self::flash( 'error', __( 'Unknown action.', 'mbd-kpi' ) );
				$this->redirect_back( $page );
				break;
		}
	}

	/**
	 * Enforce a capability or bail with an access-denied redirect.
	 *
	 * @param string $cap  Capability.
	 * @param string $page Current page (for redirect target).
	 * @return void
	 */
	private function require_cap( $cap, $page ) {
		if ( ! current_user_can( $cap ) ) {
			self::flash( 'error', __( 'You do not have permission to perform that action.', 'mbd-kpi' ) );
			$this->redirect_back( $page );
		}
	}

	/**
	 * Lock or unlock a period and store/clear a snapshot.
	 *
	 * @param array $data   Raw input.
	 * @param bool  $lock   True to lock.
	 * @return void
	 */
	private function lock_period( $data, $lock ) {
		global $wpdb;
		$period = mbd_kpi_sanitize_period( $data['period'] ?? '' );
		if ( ! $period ) {
			self::flash( 'error', __( 'Invalid period.', 'mbd-kpi' ) );
			return;
		}
		$table = MBD_KPI_DB::table( 'period_locks' );

		if ( $lock ) {
			// Snapshot current live scores into snapshot rows.
			$this->snapshot_period( $period );
			$wpdb->replace(
				$table,
				array(
					'period_type' => mbd_kpi_period_type( $period ),
					'period'      => $period,
					'status'      => 'locked',
					'note'        => sanitize_textarea_field( $data['note'] ?? '' ),
					'locked_by'   => get_current_user_id(),
					'locked_at'   => mbd_kpi_now(),
					'created_at'  => mbd_kpi_now(),
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
			MBD_KPI_Audit_Log::log( 'period.lock', 'period_lock', 0, null, array( 'period' => $period ) );
			self::flash( 'success', sprintf( /* translators: %s period */ __( 'Period %s locked and snapshot saved.', 'mbd-kpi' ), $period ) );
		} else {
			$wpdb->delete( $table, array( 'period' => $period ), array( '%s' ) );
			MBD_KPI_Audit_Log::log( 'period.unlock', 'period_lock', 0, array( 'period' => $period ), null );
			self::flash( 'success', sprintf( /* translators: %s period */ __( 'Period %s unlocked.', 'mbd-kpi' ), $period ) );
		}
	}

	/**
	 * Persist immutable snapshot scores for a period.
	 *
	 * @param string $period Period.
	 * @return void
	 */
	private function snapshot_period( $period ) {
		global $wpdb;
		$reg_table  = MBD_KPI_DB::table( 'registry' );
		$registries = (array) $wpdb->get_results( "SELECT * FROM {$reg_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		$payload = array();
		foreach ( $registries as $reg ) {
			$pkg = MBD_KPI_Score_Engine::compute_and_store( $reg, $period );
			// Persist snapshot copy (is_snapshot = 1).
			$wpdb->replace(
				MBD_KPI_DB::table( 'scores' ),
				array(
					'registry_id'       => (int) $reg['id'],
					'period'            => $period,
					'target_value'      => null === $pkg['target_value'] ? 0 : $pkg['target_value'],
					'actual_value'      => null === $pkg['actual_value'] ? 0 : $pkg['actual_value'],
					'performance_score' => null === $pkg['performance_score'] ? 0 : $pkg['performance_score'],
					'data_health_score' => $pkg['data_health_score'],
					'status'            => $pkg['status'],
					'is_snapshot'       => 1,
					'computed_at'       => mbd_kpi_now(),
					'created_at'        => mbd_kpi_now(),
				),
				array( '%d', '%s', '%f', '%f', '%f', '%f', '%s', '%d', '%s', '%s' )
			);
			$payload[ (int) $reg['id'] ] = $pkg;
		}

		$wpdb->insert(
			MBD_KPI_DB::table( 'snapshots' ),
			array(
				'period'        => $period,
				'snapshot_type' => 'period_lock',
				'payload'       => wp_json_encode( $payload ),
				'created_by'    => get_current_user_id(),
				'created_at'    => mbd_kpi_now(),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Persist general settings from the front-end settings form.
	 *
	 * @param array $data Raw input.
	 * @return void
	 */
	private function save_settings( $data ) {
		if ( isset( $data['score_cap'] ) ) {
			mbd_kpi_update_setting( 'score_cap', max( 1, (float) $data['score_cap'] ) );
		}
		if ( isset( $data['default_threshold_green'] ) ) {
			mbd_kpi_update_setting( 'default_threshold_green', (float) $data['default_threshold_green'] );
		}
		if ( isset( $data['default_threshold_yellow'] ) ) {
			mbd_kpi_update_setting( 'default_threshold_yellow', (float) $data['default_threshold_yellow'] );
		}
		if ( isset( $data['company_name'] ) ) {
			mbd_kpi_update_setting( 'company_name', sanitize_text_field( $data['company_name'] ) );
		}
		MBD_KPI_Audit_Log::log( 'settings.update', 'settings', 0, null, array( 'keys' => array_keys( $data ) ) );
	}

	/**
	 * Render the layout with the requested view.
	 *
	 * @param string $page Page slug.
	 * @return void
	 */
	private function render( $page ) {
		$view_file = MBD_KPI_DIR . 'app/' . $this->pages[ $page ];

		// Context exposed to templates.
		$ctx = array(
			'page'       => $page,
			'view_file'  => $view_file,
			'pages'      => $this->pages,
			'flash'      => self::get_flash(),
			'employee'   => MBD_KPI_Permissions::current_employee(),
			'scope'      => MBD_KPI_Permissions::current_scope(),
			'period'     => $this->current_request_period(),
		);

		// Expose context globally for partials.
		$GLOBALS['mbd_kpi_ctx'] = $ctx;

		status_header( 200 );
		nocache_headers();

		include MBD_KPI_DIR . 'templates/layout.php';
	}

	/**
	 * Resolve the active period from the request (?period=) or default.
	 *
	 * @return string
	 */
	private function current_request_period() {
		$req = isset( $_GET['period'] ) ? mbd_kpi_sanitize_period( wp_unslash( $_GET['period'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $req ?: mbd_kpi_current_period();
	}

	/**
	 * Render the access-denied page.
	 *
	 * @return void
	 */
	private function render_denied() {
		$this->enqueue_assets();
		status_header( 403 );
		nocache_headers();
		$GLOBALS['mbd_kpi_ctx'] = array(
			'page'      => 'denied',
			'view_file' => '',
			'pages'     => $this->pages,
			'flash'     => null,
			'employee'  => null,
			'scope'     => array( 'scope' => 'self', 'division_id' => 0, 'employee_id' => 0 ),
			'period'    => mbd_kpi_current_period(),
		);
		include MBD_KPI_DIR . 'app/access-denied.php';
	}
}
