<?php
/**
 * Locations admin page.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Locations_Page' ) ) {
	/**
	 * Handles the locations admin page.
	 */
	final class ExitSure_Sync_Locations_Page {

		/**
		 * Admin page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'exitsure-sync-locations';

		/**
		 * Add location action name.
		 *
		 * @var string
		 */
		const ADD_LOCATION_ACTION = 'exitsure_sync_add_location';

		/**
		 * Registers page hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'admin_post_' . self::ADD_LOCATION_ACTION, array( $this, 'handle_add_location' ) );
		}

		/**
		 * Renders the locations page.
		 *
		 * @return void
		 */
		public function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$locations = $this->get_locations();

			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Locations', 'exitsure-sync' ); ?></h1>

				<?php $this->render_admin_notice(); ?>

				<div class="card">
					<h2><?php echo esc_html__( 'Add Location', 'exitsure-sync' ); ?></h2>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ADD_LOCATION_ACTION ); ?>" />

						<?php wp_nonce_field( self::ADD_LOCATION_ACTION, '_exitsure_sync_nonce' ); ?>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="exitsure-sync-location-name">
											<?php echo esc_html__( 'Name', 'exitsure-sync' ); ?>
										</label>
									</th>
									<td>
										<input
											type="text"
											id="exitsure-sync-location-name"
											name="name"
											class="regular-text"
											required
										/>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="exitsure-sync-location-description">
											<?php echo esc_html__( 'Description', 'exitsure-sync' ); ?>
										</label>
									</th>
									<td>
										<textarea
											id="exitsure-sync-location-description"
											name="description"
											class="large-text"
											rows="3"
										></textarea>
									</td>
								</tr>
							</tbody>
						</table>

						<?php submit_button( esc_html__( 'Add Location', 'exitsure-sync' ) ); ?>
					</form>
				</div>

				<div class="card">
					<h2><?php echo esc_html__( 'Active Locations', 'exitsure-sync' ); ?></h2>

					<?php if ( empty( $locations ) ) : ?>
						<p><?php echo esc_html__( 'No locations have been added yet.', 'exitsure-sync' ); ?></p>
					<?php else : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'ID', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Name', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Description', 'exitsure-sync' ); ?></th>
									<th><?php echo esc_html__( 'Created', 'exitsure-sync' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $locations as $location ) : ?>
									<tr>
										<td><?php echo esc_html( absint( $location['id'] ) ); ?></td>
										<td><?php echo esc_html( $location['name'] ); ?></td>
										<td><?php echo esc_html( $location['description'] ); ?></td>
										<td><?php echo esc_html( $location['created_at'] ); ?></td>
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
		 * Handles adding a location from the admin screen.
		 *
		 * @return void
		 */
		public function handle_add_location() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'exitsure-sync' ) );
			}

			check_admin_referer( self::ADD_LOCATION_ACTION, '_exitsure_sync_nonce' );

			$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

			if ( '' === $name ) {
				$this->redirect_to_locations_page( 'missing_name' );
			}

			$created = $this->create_location( $name, $description );

			if ( ! $created ) {
				$this->redirect_to_locations_page( 'create_failed' );
			}

			$this->redirect_to_locations_page( 'created' );
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
				$this->render_notice( esc_html__( 'Location created successfully.', 'exitsure-sync' ), 'success' );

				return;
			}

			if ( 'missing_name' === $status ) {
				$this->render_notice( esc_html__( 'Location name is required.', 'exitsure-sync' ), 'error' );

				return;
			}

			if ( 'create_failed' === $status ) {
				$this->render_notice( esc_html__( 'Location could not be created.', 'exitsure-sync' ), 'error' );
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
		 * Creates a location.
		 *
		 * @param string $name        Location name.
		 * @param string $description Location description.
		 *
		 * @return bool
		 */
		private function create_location( $name, $description ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return false;
			}

			$datetime = ExitSure_Sync_DB::get_current_datetime();

			$inserted = $wpdb->insert(
				$table,
				array(
					'uuid'        => ExitSure_Sync_DB::get_uuid(),
					'name'        => $name,
					'description' => $description,
					'is_archived' => 0,
					'created_at'  => $datetime,
					'updated_at'  => $datetime,
				),
				array(
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
				)
			);

			return false !== $inserted;
		}

		/**
		 * Redirects to the locations page with status.
		 *
		 * @param string $status Status key.
		 *
		 * @return void
		 */
		private function redirect_to_locations_page( $status ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'            => self::MENU_SLUG,
						'exitsure_status' => sanitize_key( $status ),
					),
					admin_url( 'admin.php' )
				)
			);

			exit;
		}
	}
}
