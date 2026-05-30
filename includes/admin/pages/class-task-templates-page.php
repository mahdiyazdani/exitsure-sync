<?php
/**
 * Task templates admin page.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Task_Templates_Page' ) ) {
	/**
	 * Handles the task templates admin page.
	 */
	final class ExitSure_Sync_Task_Templates_Page {

		/**
		 * Admin page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'exitsure-sync-tasks';

		/**
		 * Add task action name.
		 *
		 * @var string
		 */
		const ADD_TASK_ACTION = 'exitsure_sync_add_task_template';

		/**
		 * Registers page hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'admin_post_' . self::ADD_TASK_ACTION, array( $this, 'handle_add_task' ) );
		}

		/**
		 * Renders the task templates page.
		 *
		 * @return void
		 */
		public function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$locations            = $this->get_locations();
			$selected_location_id = $this->get_selected_location_id( $locations );
			$tasks                = $selected_location_id > 0 ? $this->get_tasks( $selected_location_id ) : array();

			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Tasks', 'exitsure-sync' ); ?></h1>

				<?php $this->render_admin_notice(); ?>

				<?php if ( empty( $locations ) ) : ?>
					<div class="card">
						<h2><?php echo esc_html__( 'No Locations Found', 'exitsure-sync' ); ?></h2>

						<p>
							<?php echo esc_html__( 'Create a location first before adding enter or leave checklist tasks.', 'exitsure-sync' ); ?>
						</p>

						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=exitsure-sync-locations' ) ); ?>">
							<?php echo esc_html__( 'Manage Locations', 'exitsure-sync' ); ?>
						</a>
					</div>
				<?php else : ?>
					<div class="card">
						<h2><?php echo esc_html__( 'Select Location', 'exitsure-sync' ); ?></h2>

						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
							<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />

							<select name="location_id">
								<?php foreach ( $locations as $location ) : ?>
									<option
										value="<?php echo esc_attr( absint( $location['id'] ) ); ?>"
										<?php selected( $selected_location_id, absint( $location['id'] ) ); ?>
									>
										<?php echo esc_html( $location['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>

							<?php submit_button( esc_html__( 'View Tasks', 'exitsure-sync' ), 'secondary', '', false ); ?>
						</form>
					</div>

					<div class="card">
						<h2><?php echo esc_html__( 'Add Task', 'exitsure-sync' ); ?></h2>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( self::ADD_TASK_ACTION ); ?>" />
							<input type="hidden" name="location_id" value="<?php echo esc_attr( $selected_location_id ); ?>" />

							<?php wp_nonce_field( self::ADD_TASK_ACTION, '_exitsure_sync_nonce' ); ?>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="exitsure-sync-task-type">
												<?php echo esc_html__( 'Type', 'exitsure-sync' ); ?>
											</label>
										</th>
										<td>
											<select id="exitsure-sync-task-type" name="type" required>
												<option value="leave"><?php echo esc_html__( 'Leave', 'exitsure-sync' ); ?></option>
												<option value="enter"><?php echo esc_html__( 'Enter', 'exitsure-sync' ); ?></option>
											</select>
										</td>
									</tr>

									<tr>
										<th scope="row">
											<label for="exitsure-sync-task-title">
												<?php echo esc_html__( 'Title', 'exitsure-sync' ); ?>
											</label>
										</th>
										<td>
											<input
												type="text"
												id="exitsure-sync-task-title"
												name="title"
												class="regular-text"
												required
											/>
										</td>
									</tr>

									<tr>
										<th scope="row">
											<label for="exitsure-sync-task-description">
												<?php echo esc_html__( 'Description', 'exitsure-sync' ); ?>
											</label>
										</th>
										<td>
											<textarea
												id="exitsure-sync-task-description"
												name="description"
												class="large-text"
												rows="3"
											></textarea>
										</td>
									</tr>

									<tr>
										<th scope="row">
											<?php echo esc_html__( 'Required', 'exitsure-sync' ); ?>
										</th>
										<td>
											<label>
												<input type="checkbox" name="is_required" value="1" checked />
												<?php echo esc_html__( 'This task must be checked before the checklist can be completed.', 'exitsure-sync' ); ?>
											</label>
										</td>
									</tr>

									<tr>
										<th scope="row">
											<label for="exitsure-sync-task-sort-order">
												<?php echo esc_html__( 'Sort Order', 'exitsure-sync' ); ?>
											</label>
										</th>
										<td>
											<input
												type="number"
												id="exitsure-sync-task-sort-order"
												name="sort_order"
												value="10"
												min="0"
												step="1"
											/>
										</td>
									</tr>
								</tbody>
							</table>

							<?php submit_button( esc_html__( 'Add Task', 'exitsure-sync' ) ); ?>
						</form>
					</div>

					<div class="card">
						<h2><?php echo esc_html__( 'Active Tasks', 'exitsure-sync' ); ?></h2>

						<?php if ( empty( $tasks ) ) : ?>
							<p><?php echo esc_html__( 'No tasks have been added for this location yet.', 'exitsure-sync' ); ?></p>
						<?php else : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php echo esc_html__( 'ID', 'exitsure-sync' ); ?></th>
										<th><?php echo esc_html__( 'Type', 'exitsure-sync' ); ?></th>
										<th><?php echo esc_html__( 'Title', 'exitsure-sync' ); ?></th>
										<th><?php echo esc_html__( 'Required', 'exitsure-sync' ); ?></th>
										<th><?php echo esc_html__( 'Sort Order', 'exitsure-sync' ); ?></th>
										<th><?php echo esc_html__( 'Created', 'exitsure-sync' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $tasks as $task ) : ?>
										<tr>
											<td><?php echo esc_html( absint( $task['id'] ) ); ?></td>
											<td><?php echo esc_html( ucfirst( $task['type'] ) ); ?></td>
											<td><?php echo esc_html( $task['title'] ); ?></td>
											<td>
												<?php
												echo ! empty( $task['is_required'] )
													? esc_html__( 'Yes', 'exitsure-sync' )
													: esc_html__( 'No', 'exitsure-sync' );
												?>
											</td>
											<td><?php echo esc_html( absint( $task['sort_order'] ) ); ?></td>
											<td><?php echo esc_html( $task['created_at'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Handles adding a task template from the admin screen.
		 *
		 * @return void
		 */
		public function handle_add_task() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'exitsure-sync' ) );
			}

			check_admin_referer( self::ADD_TASK_ACTION, '_exitsure_sync_nonce' );

			$location_id = isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0;
			$type        = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
			$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
			$is_required = isset( $_POST['is_required'] ) ? 1 : 0;
			$sort_order  = isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0;

			if ( $location_id <= 0 ) {
				$this->redirect_to_tasks_page( 'missing_location', $location_id );
			}

			if ( ! $this->location_exists( $location_id ) ) {
				$this->redirect_to_tasks_page( 'missing_location', $location_id );
			}

			if ( ! $this->is_valid_task_type( $type ) ) {
				$this->redirect_to_tasks_page( 'invalid_type', $location_id );
			}

			if ( '' === $title ) {
				$this->redirect_to_tasks_page( 'missing_title', $location_id );
			}

			$created = $this->create_task( $location_id, $type, $title, $description, $is_required, $sort_order );

			if ( ! $created ) {
				$this->redirect_to_tasks_page( 'create_failed', $location_id );
			}

			$this->redirect_to_tasks_page( 'created', $location_id );
		}

		/**
		 * Gets active locations.
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
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE is_archived = %d ORDER BY name ASC",
					0
				),
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
		 * @param array $locations Active locations.
		 *
		 * @return int
		 */
		private function get_selected_location_id( $locations ) {
			$location_id = isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;

			if ( $location_id > 0 ) {
				return $location_id;
			}

			if ( empty( $locations[0]['id'] ) ) {
				return 0;
			}

			return absint( $locations[0]['id'] );
		}

		/**
		 * Gets active tasks for a location.
		 *
		 * @param int $location_id Location ID.
		 *
		 * @return array
		 */
		private function get_tasks( $location_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $table ) {
				return array();
			}

			$tasks = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE location_id = %d AND is_enabled = %d ORDER BY type ASC, sort_order ASC, id ASC",
					$location_id,
					1
				),
				ARRAY_A
			);

			if ( empty( $tasks ) ) {
				return array();
			}

			return $tasks;
		}

		/**
		 * Checks whether a location exists.
		 *
		 * @param int $location_id Location ID.
		 *
		 * @return bool
		 */
		private function location_exists( $location_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return false;
			}

			$location_id = absint( $location_id );

			if ( $location_id <= 0 ) {
				return false;
			}

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE id = %d AND is_archived = %d",
					$location_id,
					0
				)
			);

			return ! empty( $exists );
		}

		/**
		 * Creates a task template.
		 *
		 * @param int    $location_id Location ID.
		 * @param string $type        Task type.
		 * @param string $title       Task title.
		 * @param string $description Task description.
		 * @param int    $is_required Whether task is required.
		 * @param int    $sort_order  Sort order.
		 *
		 * @return bool
		 */
		private function create_task( $location_id, $type, $title, $description, $is_required, $sort_order ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $table ) {
				return false;
			}

			$datetime = ExitSure_Sync_DB::get_current_datetime();

			$inserted = $wpdb->insert(
				$table,
				array(
					'uuid'        => ExitSure_Sync_DB::get_uuid(),
					'location_id' => absint( $location_id ),
					'type'        => $type,
					'title'       => $title,
					'description' => $description,
					'is_required' => $is_required ? 1 : 0,
					'is_enabled'  => 1,
					'sort_order'  => absint( $sort_order ),
					'created_at'  => $datetime,
					'updated_at'  => $datetime,
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
				)
			);

			return false !== $inserted;
		}

		/**
		 * Checks whether a task type is valid.
		 *
		 * @param string $type Task type.
		 *
		 * @return bool
		 */
		private function is_valid_task_type( $type ) {
			return in_array( $type, array( 'enter', 'leave' ), true );
		}

		/**
		 * Renders an admin notice when needed.
		 *
		 * @return void
		 */
		private function render_admin_notice() {
			$status = isset( $_GET['exitsure_status'] ) ? sanitize_key( wp_unslash( $_GET['exitsure_status'] ) ) : '';

			if ( '' === $status ) {
				return;
			}

			if ( 'created' === $status ) {
				$this->render_notice( esc_html__( 'Task created successfully.', 'exitsure-sync' ), 'success' );

				return;
			}

			if ( 'missing_location' === $status ) {
				$this->render_notice( esc_html__( 'A valid location is required.', 'exitsure-sync' ), 'error' );

				return;
			}

			if ( 'invalid_type' === $status ) {
				$this->render_notice( esc_html__( 'A valid task type is required.', 'exitsure-sync' ), 'error' );

				return;
			}

			if ( 'missing_title' === $status ) {
				$this->render_notice( esc_html__( 'Task title is required.', 'exitsure-sync' ), 'error' );

				return;
			}

			if ( 'create_failed' === $status ) {
				$this->render_notice( esc_html__( 'Task could not be created.', 'exitsure-sync' ), 'error' );
			}
		}

		/**
		 * Renders an admin notice.
		 *
		 * @param string $message Notice message.
		 * @param string $type    Notice type.
		 *
		 * @return void
		 */
		private function render_notice( $message, $type ) {
			?>
			<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}

		/**
		 * Redirects to the tasks page with status.
		 *
		 * @param string $status      Status key.
		 * @param int    $location_id Location ID.
		 *
		 * @return void
		 */
		private function redirect_to_tasks_page( $status, $location_id ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'            => self::MENU_SLUG,
						'location_id'     => absint( $location_id ),
						'exitsure_status' => sanitize_key( $status ),
					),
					admin_url( 'admin.php' )
				)
			);

			exit;
		}
	}
}
