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

			$locations            = $this->get_locations();
			$selected_location_id = $this->get_selected_location_id();
			$selected_type        = $this->get_selected_type();
			$runs                 = $this->get_completed_runs( $selected_location_id, $selected_type );
			$selected_run_id      = $this->get_selected_run_id();
			$selected_run         = $selected_run_id > 0 ? $this->get_completed_run( $selected_run_id ) : null;
			$selected_run_items   = ! empty( $selected_run ) ? $this->get_run_items( $selected_run_id ) : array();

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

				<?php if ( $selected_run_id > 0 && empty( $selected_run ) ) : ?>
					<div class="notice notice-error">
						<p><?php echo esc_html__( 'Checklist run could not be found.', 'exitsure-sync' ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $selected_run ) ) : ?>
					<?php $this->render_checklist_run_detail( $selected_run, $selected_run_items ); ?>
				<?php endif; ?>

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
									<th><?php echo esc_html__( 'Actions', 'exitsure-sync' ); ?></th>
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
										<td>
											<a class="button button-small" href="<?php echo esc_url( $this->get_view_run_url( $run ) ); ?>">
												<?php echo esc_html__( 'View', 'exitsure-sync' ); ?>
											</a>
										</td>
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

		/**
		 * Gets selected checklist run ID.
		 *
		 * @return int
		 */
		private function get_selected_run_id() {
			return isset( $_GET['view_run_id'] ) ? absint( wp_unslash( $_GET['view_run_id'] ) ) : 0;
		}

		/**
		 * Gets a completed checklist run.
		 *
		 * @param int $run_id Checklist run ID.
		 *
		 * @return array|null
		 */
		private function get_completed_run( $run_id ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$locations_table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $runs_table || '' === $locations_table ) {
				return null;
			}

			$run = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT runs.*, locations.name AS location_name
					FROM {$runs_table} AS runs
					LEFT JOIN {$locations_table} AS locations ON locations.id = runs.location_id
					WHERE runs.id = %d AND runs.status = %s
					LIMIT 1",
					absint( $run_id ),
					'completed'
				),
				ARRAY_A
			);

			if ( empty( $run ) ) {
				return null;
			}

			return $run;
		}

		/**
		 * Gets checklist run items.
		 *
		 * @param int $run_id Checklist run ID.
		 *
		 * @return array
		 */
		private function get_run_items( $run_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'run_items' );

			if ( '' === $table ) {
				return array();
			}

			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE run_id = %d ORDER BY id ASC",
					absint( $run_id )
				),
				ARRAY_A
			);

			if ( empty( $items ) ) {
				return array();
			}

			return $items;
		}

		/**
		 * Gets the view run URL.
		 *
		 * @param array $run Checklist run row.
		 *
		 * @return string
		 */
		private function get_view_run_url( $run ) {
			return add_query_arg(
				array(
					'page'        => self::MENU_SLUG,
					'location_id' => absint( $run['location_id'] ),
					'type'        => sanitize_key( $run['type'] ),
					'view_run_id' => absint( $run['id'] ),
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Renders checklist run detail.
		 *
		 * @param array $run   Checklist run row.
		 * @param array $items Checklist run item rows.
		 *
		 * @return void
		 */
		private function render_checklist_run_detail( $run, $items ) {
			?>
			<div class="card">
				<h2>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: Checklist run ID. */
							__( 'Checklist Run #%d', 'exitsure-sync' ),
							absint( $run['id'] )
						)
					);
					?>
				</h2>

				<table class="widefat striped">
					<tbody>
						<tr>
							<th><?php echo esc_html__( 'Location', 'exitsure-sync' ); ?></th>
							<td><?php echo esc_html( $run['location_name'] ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Type', 'exitsure-sync' ); ?></th>
							<td><?php echo esc_html( ucfirst( $run['type'] ) ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Started', 'exitsure-sync' ); ?></th>
							<td><?php echo esc_html( $run['started_at'] ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Completed', 'exitsure-sync' ); ?></th>
							<td><?php echo esc_html( $run['completed_at'] ); ?></td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Note', 'exitsure-sync' ); ?></th>
							<td><?php echo esc_html( $run['note'] ); ?></td>
						</tr>
					</tbody>
				</table>

				<h3><?php echo esc_html__( 'Checklist Items', 'exitsure-sync' ); ?></h3>

				<?php if ( empty( $items ) ) : ?>
					<p><?php echo esc_html__( 'No checklist items found for this run.', 'exitsure-sync' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Task', 'exitsure-sync' ); ?></th>
								<th><?php echo esc_html__( 'Required', 'exitsure-sync' ); ?></th>
								<th><?php echo esc_html__( 'Checked', 'exitsure-sync' ); ?></th>
								<th><?php echo esc_html__( 'Checked At', 'exitsure-sync' ); ?></th>
								<th><?php echo esc_html__( 'Note', 'exitsure-sync' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $item ) : ?>
								<tr>
									<td><?php echo esc_html( $item['title_snapshot'] ); ?></td>
									<td>
										<?php
										echo ! empty( $item['is_required_snapshot'] )
											? esc_html__( 'Yes', 'exitsure-sync' )
											: esc_html__( 'No', 'exitsure-sync' );
										?>
									</td>
									<td>
										<?php
										echo ! empty( $item['is_checked'] )
											? esc_html__( 'Yes', 'exitsure-sync' )
											: esc_html__( 'No', 'exitsure-sync' );
										?>
									</td>
									<td><?php echo esc_html( $item['checked_at'] ); ?></td>
									<td><?php echo esc_html( $item['note'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
