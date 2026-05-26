<?php
/**
 * WordPress admin settings screen for system administration.
 *
 * The daily app lives at /kpi; wp-admin only hosts configuration,
 * role information, formula defaults and system status.
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBD_KPI_Admin_Settings {

	/**
	 * Wire admin hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_mbd_kpi_admin_save', array( $this, 'handle_save' ) );
	}

	/**
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			__( 'MBD KPI', 'mbd-kpi' ),
			__( 'MBD KPI', 'mbd-kpi' ),
			'mbd_kpi_manage_settings',
			'mbd-kpi-settings',
			array( $this, 'render' ),
			'dashicons-chart-area',
			58
		);
	}

	/**
	 * Process the admin settings form.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'mbd_kpi_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'mbd-kpi' ) );
		}
		check_admin_referer( 'mbd_kpi_admin_save' );

		mbd_kpi_update_setting( 'company_name', sanitize_text_field( wp_unslash( $_POST['company_name'] ?? '' ) ) );
		mbd_kpi_update_setting( 'score_cap', max( 1, (float) ( $_POST['score_cap'] ?? 120 ) ) );
		mbd_kpi_update_setting( 'default_threshold_green', (float) ( $_POST['default_threshold_green'] ?? 100 ) );
		mbd_kpi_update_setting( 'default_threshold_yellow', (float) ( $_POST['default_threshold_yellow'] ?? 80 ) );
		mbd_kpi_update_setting( 'default_threshold_red', (float) ( $_POST['default_threshold_red'] ?? 0 ) );

		MBD_KPI_Audit_Log::log( 'settings.admin_update', 'settings', 0, null, array( 'via' => 'wp-admin' ) );

		wp_safe_redirect( add_query_arg( array( 'page' => 'mbd-kpi-settings', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'mbd_kpi_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'mbd-kpi' ) );
		}

		$role_caps = MBD_KPI_Permissions::role_caps();
		$roles     = MBD_KPI_Permissions::roles();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MBD KPI Command Center', 'mbd-kpi' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'mbd-kpi' ); ?></p></div>
			<?php endif; ?>

			<p>
				<?php esc_html_e( 'The daily application is served at the front-end route:', 'mbd-kpi' ); ?>
				<a href="<?php echo esc_url( home_url( '/kpi' ) ); ?>" target="_blank"><strong><?php echo esc_html( home_url( '/kpi' ) ); ?></strong></a>.
				<?php esc_html_e( 'This admin screen is only for configuration and system administration.', 'mbd-kpi' ); ?>
			</p>

			<h2><?php esc_html_e( 'Formula & Scoring Settings', 'mbd-kpi' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mbd_kpi_admin_save">
				<?php wp_nonce_field( 'mbd_kpi_admin_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="company_name"><?php esc_html_e( 'Company Name', 'mbd-kpi' ); ?></label></th>
						<td><input name="company_name" id="company_name" type="text" class="regular-text" value="<?php echo esc_attr( mbd_kpi_get_setting( 'company_name', 'MBD Kontraktor' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="score_cap"><?php esc_html_e( 'Score Cap', 'mbd-kpi' ); ?></label></th>
						<td><input name="score_cap" id="score_cap" type="number" step="any" value="<?php echo esc_attr( mbd_kpi_get_setting( 'score_cap', 120 ) ); ?>"> <p class="description"><?php esc_html_e( 'Maximum performance score (default 120).', 'mbd-kpi' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default Thresholds', 'mbd-kpi' ); ?></th>
						<td>
							<label><?php esc_html_e( 'Green', 'mbd-kpi' ); ?> <input name="default_threshold_green" type="number" step="any" value="<?php echo esc_attr( mbd_kpi_get_setting( 'default_threshold_green', 100 ) ); ?>"></label>
							<label><?php esc_html_e( 'Yellow', 'mbd-kpi' ); ?> <input name="default_threshold_yellow" type="number" step="any" value="<?php echo esc_attr( mbd_kpi_get_setting( 'default_threshold_yellow', 80 ) ); ?>"></label>
							<label><?php esc_html_e( 'Red', 'mbd-kpi' ); ?> <input name="default_threshold_red" type="number" step="any" value="<?php echo esc_attr( mbd_kpi_get_setting( 'default_threshold_red', 0 ) ); ?>"></label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'mbd-kpi' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Roles & Capabilities', 'mbd-kpi' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Role', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Capabilities', 'mbd-kpi' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $roles as $rk => $rl ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $rl ); ?></strong><br><code><?php echo esc_html( $rk ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', $role_caps[ $rk ] ?? array() ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'System Status', 'mbd-kpi' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr><td><?php esc_html_e( 'Plugin Version', 'mbd-kpi' ); ?></td><td><?php echo esc_html( MBD_KPI_VERSION ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Database Tables', 'mbd-kpi' ); ?></td><td><?php echo esc_html( $this->table_status() ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Front-end Route', 'mbd-kpi' ); ?></td><td><code>/kpi</code></td></tr>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'If the /kpi route returns 404, visit Settings → Permalinks and click Save to flush rewrite rules.', 'mbd-kpi' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Quick table-existence summary.
	 *
	 * @return string
	 */
	private function table_status() {
		global $wpdb;
		$ok      = 0;
		$total   = 0;
		foreach ( MBD_KPI_DB::table_keys() as $key ) {
			$total++;
			$table = MBD_KPI_DB::table( $key );
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			if ( $found === $table ) {
				$ok++;
			}
		}
		/* translators: 1: present count, 2: total count */
		return sprintf( __( '%1$d of %2$d tables present', 'mbd-kpi' ), $ok, $total );
	}
}
