<?php
/**
 * History admin page.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_History_Page' ) ) {
	/**
	 * Handles the checklist history admin page.
	 */
	final class ExitSure_Sync_History_Page {

		/**
		 * Admin page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'exitsure-sync-history';

		/**
		 * Registers page hooks.
		 *
		 * @return void
		 */
		public function init() {}

		/**
		 * Renders the history page.
		 *
		 * @return void
		 */
		public function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$locations           = $this->get_locations();
			$selected_location_id = $this->get_selected_location_id();
			$selected_type       = $this->get_selected_type();
			$runs                = $this->get_completed_runs( $selected_location_id, $selected_type );

			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'History', 'exitsure-sync' ); ?></h1>

				<div class="card">
					<h2><?php echo esc_html__( 'Filters', 'exitsure-sync' ); ?></h2>

					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
						<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />

						<label for="exitsure-sync-history-location">
							<?php echo esc_html__( 'Location', 'exitsure-sync' ); ?>
						</label>

						<select id="exitsure-sync-history-location" name="location_id">
							<option value="0"><?php echo esc_html__( 'All locations', 'exitsure-sync' ); ?></option>

							<?php foreach ( $locations as $location ) : ?>
								<option
									value="<?php echo esc_attr( absint( $location['id'] ) ); ?>"
									<?php selected( $selected_location_id, absint( $location['id'] ) ); ?>
								>
									<?php
									echo esc_html(
										! empty( $location['is_archived'] )
											? sprintf(
												/* translators: %s: Location name. */
												__( '%s (Archived)', 'exitsure-sync' ),
												$location['name']
											)
											: $location['name']
									);
									?>
								</option>
							<?php endforeach; ?>
						</select>

						<label for="exitsure-sync-history-type">
							<?php echo esc_html__( 'Type', 'exitsure-sync' ); ?>
						</label>

						<select id="exitsure-sync-history-type" name="type">
							<option value=""><?php echo esc_html__( 'All types', 'exitsure-sync' ); ?></option>
							<option value="leave" <?php selected( $selected_type, 'leave' ); ?>>
								<?php echo esc_html__( 'Leave', 'exitsure-sync' ); ?>
							</option>
							<option value="enter" <?php selected( $selected_type, 'enter' ); ?>>
								<?php echo esc_html__( 'Enter', 'exitsure-sync' ); ?>
							</option>
						</select>

						<?php submit_button( esc_html__( 'Filter', 'exitsure-sync' ), 'secondary', '', false ); ?>
					</form>
				</div>

				<div class="card">
					<h2><?php echo esc_html__( 'Completed Checklists', 'exitsure-sync' ); ?></h2>

					<?php if ( empty( $runs ) ) : ?>
						<p><?php echo esc_html__( 'No completed checklist runs found.', 'exitsure-sync' ); ?></p>
					<?php else : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'ID', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Location', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Type', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Started', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Completed', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Note', 'exitsure-sync' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $runs as $run ) : ?>
									<tr>
										<td><?php echo esc_html( absint( $run['id'] ) ); ?></td>
										<td><?php echo esc_html( $run['location_name'] ); ?></td>
										<td><?php echo esc_html( ucfirst( $run['type'] ) ); ?></td>
										<td><?php echo esc_html( $run['started_at'] ); ?></td>
										<td><?php echo esc_html( $run['completed_at'] ); ?></td>
										<td><?php echo esc_html( $run['note'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Gets locations.
		 *
		 * @return array
		 */
		private function get_locations() {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return array();
			}

			$locations = $wpdb->get_results(
				"SELECT * FROM {$table} ORDER BY is_archived ASC, name ASC",
				ARRAY_A
			);

			if ( empty( $locations ) ) {
				return array();
			}

			return $locations;
		}

		/**
		 * Gets selected location ID.
		 *
		 * @return int
		 */
		private function get_selected_location_id() {
			return isset( $_GET['location_id'] ) ? absint( wp_unslash( $_GET['location_id'] ) ) : 0;
		}

		/**
		 * Gets selected checklist type.
		 *
		 * @return string
		 */
		private function get_selected_type() {
			$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';

			if ( ! in_array( $type, array( 'enter', 'leave' ), true ) ) {
				return '';
			}

			return $type;
		}

		/**
		 * Gets completed checklist runs.
		 *
		 * @param int    $location_id Location ID.
		 * @param string $type        Checklist type.
		 *
		 * @return array
		 */
		private function get_completed_runs( $location_id, $type ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$locations_table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $runs_table || '' === $locations_table ) {
				return array();
			}

			if ( $location_id > 0 && '' !== $type ) {
				return $this->get_completed_runs_by_location_and_type( $location_id, $type );
			}

			if ( $location_id > 0 ) {
				return $this->get_completed_runs_by_location( $location_id );
			}

			if ( '' !== $type ) {
				return $this->get_completed_runs_by_type( $type );
			}

			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT runs.*, locations.name AS location_name
					FROM {$runs_table} AS runs
					LEFT JOIN {$locations_table} AS locations ON locations.id = runs.location_id
					WHERE runs.status = %s
					ORDER BY runs.completed_at DESC, runs.id DESC
					LIMIT %d",
					'completed',
					50
				),
				ARRAY_A
			);

			if ( empty( $runs ) ) {
				return array();
			}

			return $runs;
		}

		/**
		 * Gets completed checklist runs by location and type.
		 *
		 * @param int    $location_id Location ID.
		 * @param string $type        Checklist type.
		 *
		 * @return array
		 */
		private function get_completed_runs_by_location_and_type( $location_id, $type ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$locations_table = ExitSure_Sync_DB::get_table_name( 'locations' );

			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT runs.*, locations.name AS location_name
					FROM {$runs_table} AS runs
					LEFT JOIN {$locations_table} AS locations ON locations.id = runs.location_id
					WHERE runs.location_id = %d AND runs.type = %s AND runs.status = %s
					ORDER BY runs.completed_at DESC, runs.id DESC
					LIMIT %d",
					absint( $location_id ),
					$type,
					'completed',
					50
				),
				ARRAY_A
			);

			if ( empty( $runs ) ) {
				return array();
			}

			return $runs;
		}

		/**
		 * Gets completed checklist runs by location.
		 *
		 * @param int $location_id Location ID.
		 *
		 * @return array
		 */
		private function get_completed_runs_by_location( $location_id ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$locations_table = ExitSure_Sync_DB::get_table_name( 'locations' );

			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT runs.*, locations.name AS location_name
					FROM {$runs_table} AS runs
					LEFT JOIN {$locations_table} AS locations ON locations.id = runs.location_id
					WHERE runs.location_id = %d AND runs.status = %s
					ORDER BY runs.completed_at DESC, runs.id DESC
					LIMIT %d",
					absint( $location_id ),
					'completed',
					50
				),
				ARRAY_A
			);

			if ( empty( $runs ) ) {
				return array();
			}

			return $runs;
		}

		/**
		 * Gets completed checklist runs by type.
		 *
		 * @param string $type Checklist type.
		 *
		 * @return array
		 */
		private function get_completed_runs_by_type( $type ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$locations_table = ExitSure_Sync_DB::get_table_name( 'locations' );

			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT runs.*, locations.name AS location_name
					FROM {$runs_table} AS runs
					LEFT JOIN {$locations_table} AS locations ON locations.id = runs.location_id
					WHERE runs.type = %s AND runs.status = %s
					ORDER BY runs.completed_at DESC, runs.id DESC
					LIMIT %d",
					$type,
					'completed',
					50
				),
				ARRAY_A
			);

			if ( empty( $runs ) ) {
				return array();
			}

			return $runs;
		}
	}
}
