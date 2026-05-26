<?php
/**
 * KPI Registry & Dictionary (/kpi/kpi-registry).
 *
 * @package MBD_KPI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ctx        = $GLOBALS['mbd_kpi_ctx'];
$can_manage = current_user_can( 'mbd_kpi_manage_registry' );

$edit_id  = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$editing   = $edit_id ? MBD_KPI_Service::get_registry_item( $edit_id ) : null;
$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$registry   = MBD_KPI_Service::get_registry( array( 'search' => $search ) );
$dictionary = MBD_KPI_Service::get_dictionary();
$divisions  = MBD_KPI_Service::get_divisions();
$employees  = MBD_KPI_Service::get_employees();
$div_map    = MBD_KPI_Service::division_map();

$g = function ( $key, $default = '' ) use ( $editing ) {
	return $editing && isset( $editing[ $key ] ) ? $editing[ $key ] : $default;
};
?>

<div class="mbd-page-head">
	<h2><?php esc_html_e( 'KPI Registry', 'mbd-kpi' ); ?></h2>
	<form method="get" action="<?php echo esc_url( mbd_kpi_url( 'kpi-registry' ) ); ?>" class="mbd-search">
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search KPI…', 'mbd-kpi' ); ?>">
		<button type="submit" class="mbd-btn"><?php esc_html_e( 'Search', 'mbd-kpi' ); ?></button>
	</form>
</div>

<section class="mbd-card">
	<table class="mbd-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'KPI', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Perspective', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Pillar', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Division', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Dir.', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Freq.', 'mbd-kpi' ); ?></th>
				<th><?php esc_html_e( 'Status', 'mbd-kpi' ); ?></th>
				<?php if ( $can_manage ) : ?><th></th><?php endif; ?>
			</tr>
		</thead>
		<tbody>
		<?php if ( $registry ) : ?>
			<?php
			$perspectives = mbd_kpi_bsc_perspectives();
			$pillars      = mbd_kpi_strategic_pillars();
			foreach ( $registry as $reg ) : ?>
				<tr>
					<td><?php echo esc_html( $reg['kpi_code'] ); ?></td>
					<td><?php echo esc_html( $reg['kpi_name'] ); ?></td>
					<td><?php echo esc_html( $perspectives[ $reg['bsc_perspective'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( $pillars[ $reg['strategic_pillar'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( $div_map[ (int) $reg['division_id'] ] ?? '—' ); ?></td>
					<td><?php echo esc_html( 'down' === $reg['target_direction'] ? '↓' : '↑' ); ?></td>
					<td><?php echo esc_html( $reg['frequency'] ); ?></td>
					<td><?php echo mbd_kpi_pill( $reg['status'], 'default' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<?php if ( $can_manage ) : ?>
						<td><a class="mbd-link" href="<?php echo esc_url( mbd_kpi_url( 'kpi-registry', array( 'edit' => (int) $reg['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'mbd-kpi' ); ?></a></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="9" class="mbd-muted"><?php esc_html_e( 'No KPIs found.', 'mbd-kpi' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</section>

<?php if ( $can_manage ) : ?>
<section class="mbd-card">
	<h3><?php echo $editing ? esc_html__( 'Edit KPI', 'mbd-kpi' ) : esc_html__( 'Add KPI', 'mbd-kpi' ); ?></h3>
	<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'kpi-registry' ) ); ?>" class="mbd-form">
		<?php mbd_kpi_nonce_field( 'mbd_kpi_save_registry' ); ?>
		<input type="hidden" name="mbd_kpi_form" value="save_registry">
		<?php if ( $editing ) : ?><input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>"><?php endif; ?>

		<div class="mbd-form-row">
			<label><?php esc_html_e( 'KPI Code', 'mbd-kpi' ); ?><input type="text" name="kpi_code" value="<?php echo esc_attr( $g( 'kpi_code' ) ); ?>" required></label>
			<label><?php esc_html_e( 'KPI Name', 'mbd-kpi' ); ?><input type="text" name="kpi_name" value="<?php echo esc_attr( $g( 'kpi_name' ) ); ?>" required></label>
		</div>
		<label><?php esc_html_e( 'Business Definition', 'mbd-kpi' ); ?><textarea name="business_definition" rows="2"><?php echo esc_textarea( $g( 'business_definition' ) ); ?></textarea></label>

		<div class="mbd-form-row">
			<label><?php esc_html_e( 'Category', 'mbd-kpi' ); ?><input type="text" name="category" value="<?php echo esc_attr( $g( 'category' ) ); ?>"></label>
			<label><?php esc_html_e( 'BSC Perspective', 'mbd-kpi' ); ?>
				<select name="bsc_perspective">
					<option value="">&mdash;</option>
					<?php foreach ( mbd_kpi_bsc_perspectives() as $k => $l ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $g( 'bsc_perspective' ), $k ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Strategic Pillar', 'mbd-kpi' ); ?>
				<select name="strategic_pillar">
					<option value="">&mdash;</option>
					<?php foreach ( mbd_kpi_strategic_pillars() as $k => $l ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $g( 'strategic_pillar' ), $k ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<div class="mbd-form-row">
			<label><?php esc_html_e( 'Division', 'mbd-kpi' ); ?>
				<select name="division_id">
					<option value="0">&mdash;</option>
					<?php foreach ( $divisions as $d ) : ?>
						<option value="<?php echo (int) $d['id']; ?>" <?php selected( (int) $g( 'division_id' ), (int) $d['id'] ); ?>><?php echo esc_html( $d['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Owner', 'mbd-kpi' ); ?>
				<select name="owner_employee_id">
					<option value="0">&mdash;</option>
					<?php foreach ( $employees as $e ) : ?>
						<option value="<?php echo (int) $e['id']; ?>" <?php selected( (int) $g( 'owner_employee_id' ), (int) $e['id'] ); ?>><?php echo esc_html( $e['full_name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><?php esc_html_e( 'Dictionary Term', 'mbd-kpi' ); ?>
				<select name="dictionary_id">
					<option value="0">&mdash;</option>
					<?php foreach ( $dictionary as $d ) : ?>
						<option value="<?php echo (int) $d['id']; ?>" <?php selected( (int) $g( 'dictionary_id' ), (int) $d['id'] ); ?>><?php echo esc_html( $d['term_name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<div class="mbd-form-row">
			<label><?php esc_html_e( 'Formula Type', 'mbd-kpi' ); ?>
				<select name="formula_type">
					<option value="positive" <?php selected( $g( 'formula_type', 'positive' ), 'positive' ); ?>><?php esc_html_e( 'Positive (higher is better)', 'mbd-kpi' ); ?></option>
					<option value="negative" <?php selected( $g( 'formula_type' ), 'negative' ); ?>><?php esc_html_e( 'Negative (lower is better)', 'mbd-kpi' ); ?></option>
				</select>
			</label>
			<label><?php esc_html_e( 'Target Direction', 'mbd-kpi' ); ?>
				<select name="target_direction">
					<option value="up" <?php selected( $g( 'target_direction', 'up' ), 'up' ); ?>><?php esc_html_e( 'Up', 'mbd-kpi' ); ?></option>
					<option value="down" <?php selected( $g( 'target_direction' ), 'down' ); ?>><?php esc_html_e( 'Down', 'mbd-kpi' ); ?></option>
				</select>
			</label>
			<label><?php esc_html_e( 'Frequency', 'mbd-kpi' ); ?>
				<select name="frequency">
					<?php foreach ( array( 'daily', 'weekly', 'monthly', 'quarterly', 'yearly' ) as $f ) : ?>
						<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $g( 'frequency', 'monthly' ), $f ); ?>><?php echo esc_html( ucfirst( $f ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<label><?php esc_html_e( 'Formula Description', 'mbd-kpi' ); ?><textarea name="formula_description" rows="2"><?php echo esc_textarea( $g( 'formula_description' ) ); ?></textarea></label>

		<div class="mbd-form-row">
			<label><?php esc_html_e( 'Data Source', 'mbd-kpi' ); ?><input type="text" name="data_source" value="<?php echo esc_attr( $g( 'data_source' ) ); ?>"></label>
			<label><?php esc_html_e( 'Unit', 'mbd-kpi' ); ?><input type="text" name="unit" value="<?php echo esc_attr( $g( 'unit' ) ); ?>"></label>
			<label class="mbd-check"><input type="checkbox" name="evidence_required" value="1" <?php checked( (int) $g( 'evidence_required' ), 1 ); ?>> <?php esc_html_e( 'Evidence required', 'mbd-kpi' ); ?></label>
		</div>

		<div class="mbd-form-row">
			<label><?php esc_html_e( 'Threshold Green', 'mbd-kpi' ); ?><input type="number" step="any" name="threshold_green" value="<?php echo esc_attr( $g( 'threshold_green', mbd_kpi_get_setting( 'default_threshold_green', 100 ) ) ); ?>"></label>
			<label><?php esc_html_e( 'Threshold Yellow', 'mbd-kpi' ); ?><input type="number" step="any" name="threshold_yellow" value="<?php echo esc_attr( $g( 'threshold_yellow', mbd_kpi_get_setting( 'default_threshold_yellow', 80 ) ) ); ?>"></label>
			<label><?php esc_html_e( 'Threshold Red', 'mbd-kpi' ); ?><input type="number" step="any" name="threshold_red" value="<?php echo esc_attr( $g( 'threshold_red', 0 ) ); ?>"></label>
			<label><?php esc_html_e( 'Weight', 'mbd-kpi' ); ?><input type="number" step="any" name="weight" value="<?php echo esc_attr( $g( 'weight', 1 ) ); ?>"></label>
		</div>

		<label><?php esc_html_e( 'Status', 'mbd-kpi' ); ?>
			<select name="status">
				<option value="active" <?php selected( $g( 'status', 'active' ), 'active' ); ?>><?php esc_html_e( 'Active', 'mbd-kpi' ); ?></option>
				<option value="inactive" <?php selected( $g( 'status' ), 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'mbd-kpi' ); ?></option>
				<option value="draft" <?php selected( $g( 'status' ), 'draft' ); ?>><?php esc_html_e( 'Draft', 'mbd-kpi' ); ?></option>
			</select>
		</label>

		<div class="mbd-form-actions">
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save KPI', 'mbd-kpi' ); ?></button>
			<?php if ( $editing ) : ?><a class="mbd-btn" href="<?php echo esc_url( mbd_kpi_url( 'kpi-registry' ) ); ?>"><?php esc_html_e( 'Cancel', 'mbd-kpi' ); ?></a><?php endif; ?>
		</div>
	</form>
</section>

<section class="mbd-card">
	<h3><?php esc_html_e( 'KPI Dictionary', 'mbd-kpi' ); ?></h3>
	<table class="mbd-table">
		<thead><tr><th><?php esc_html_e( 'Code', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Term', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Category', 'mbd-kpi' ); ?></th><th><?php esc_html_e( 'Definition', 'mbd-kpi' ); ?></th></tr></thead>
		<tbody>
		<?php if ( $dictionary ) : ?>
			<?php foreach ( $dictionary as $d ) : ?>
				<tr><td><?php echo esc_html( $d['term_code'] ); ?></td><td><?php echo esc_html( $d['term_name'] ); ?></td><td><?php echo esc_html( $d['category'] ); ?></td><td><?php echo esc_html( wp_trim_words( $d['business_definition'], 18 ) ); ?></td></tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="4" class="mbd-muted"><?php esc_html_e( 'No dictionary terms yet.', 'mbd-kpi' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
	<details class="mbd-details">
		<summary><?php esc_html_e( 'Add Dictionary Term', 'mbd-kpi' ); ?></summary>
		<form method="post" action="<?php echo esc_url( mbd_kpi_url( 'kpi-registry' ) ); ?>" class="mbd-form">
			<?php mbd_kpi_nonce_field( 'mbd_kpi_save_dictionary' ); ?>
			<input type="hidden" name="mbd_kpi_form" value="save_dictionary">
			<div class="mbd-form-row">
				<label><?php esc_html_e( 'Code', 'mbd-kpi' ); ?><input type="text" name="term_code"></label>
				<label><?php esc_html_e( 'Term', 'mbd-kpi' ); ?><input type="text" name="term_name" required></label>
				<label><?php esc_html_e( 'Category', 'mbd-kpi' ); ?><input type="text" name="category"></label>
			</div>
			<label><?php esc_html_e( 'Business Definition', 'mbd-kpi' ); ?><textarea name="business_definition" rows="2"></textarea></label>
			<label><?php esc_html_e( 'Formula Description', 'mbd-kpi' ); ?><textarea name="formula_description" rows="2"></textarea></label>
			<button type="submit" class="mbd-btn mbd-btn-primary"><?php esc_html_e( 'Save Term', 'mbd-kpi' ); ?></button>
		</form>
	</details>
</section>
<?php endif; ?>
