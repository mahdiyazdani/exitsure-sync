<?php
/**
 * Sync REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Sync_REST_Controller' ) ) {
	/**
	 * Handles app sync REST API routes.
	 */
	final class ExitSure_Sync_Sync_REST_Controller extends ExitSure_Sync_Abstract_REST_Controller {

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				self::NAMESPACE,
				'/sync',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sync_data' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
				)
			);
		}

		/**
		 * Gets app sync data.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_sync_data() {
			global $wpdb;

			$locations_table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $locations_table ) {
				return new WP_Error(
					'exitsure_sync_missing_locations_table',
					esc_html__( 'Locations table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$locations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$locations_table} WHERE is_archived = %d ORDER BY name ASC",
					0
				),
				ARRAY_A
			);

			return rest_ensure_response(
				array(
					'generated_at' => ExitSure_Sync_DB::get_current_datetime(),
					'locations'    => array_map(
						array( $this, 'prepare_sync_location_for_response' ),
						! empty( $locations ) ? $locations : array()
					),
				)
			);
		}

		/**
		 * Prepares a location for sync response.
		 *
		 * @param array $location Location row.
		 *
		 * @return array
		 */
		private function prepare_sync_location_for_response( $location ) {
			$location_id = absint( $location['id'] );

			return array(
				'id'          => $location_id,
				'uuid'        => $location['uuid'],
				'name'        => $location['name'],
				'description' => $location['description'],
				'is_archived' => (bool) $location['is_archived'],
				'created_at'  => $location['created_at'],
				'updated_at'  => $location['updated_at'],
				'tasks'       => array(
					'enter' => $this->get_location_tasks_by_type( $location_id, 'enter' ),
					'leave' => $this->get_location_tasks_by_type( $location_id, 'leave' ),
				),
				'latest'      => array(
					'enter' => $this->get_latest_checklist_run_summary( $location_id, 'enter' ),
					'leave' => $this->get_latest_checklist_run_summary( $location_id, 'leave' ),
				),
			);
		}

		/**
		 * Gets enabled tasks for a location and type.
		 *
		 * @param int    $location_id Location ID.
		 * @param string $type        Checklist type.
		 *
		 * @return array
		 */
		private function get_location_tasks_by_type( $location_id, $type ) {
			global $wpdb;

			$tasks_table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $tasks_table ) {
				return array();
			}

			$tasks = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$tasks_table} WHERE location_id = %d AND type = %s AND is_enabled = %d ORDER BY sort_order ASC, id ASC",
					$location_id,
					$type,
					1
				),
				ARRAY_A
			);

			if ( empty( $tasks ) ) {
				return array();
			}

			return array_map(
				array( $this, 'prepare_task_template_for_response' ),
				$tasks
			);
		}

		/**
		 * Gets latest completed checklist run summary.
		 *
		 * @param int    $location_id Location ID.
		 * @param string $type        Checklist type.
		 *
		 * @return array|null
		 */
		private function get_latest_checklist_run_summary( $location_id, $type ) {
			global $wpdb;

			$runs_table = ExitSure_Sync_DB::get_table_name( 'runs' );

			if ( '' === $runs_table ) {
				return null;
			}

			$run = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$runs_table} WHERE location_id = %d AND type = %s AND status = %s ORDER BY completed_at DESC, id DESC LIMIT 1",
					$location_id,
					$type,
					'completed'
				),
				ARRAY_A
			);

			if ( empty( $run ) ) {
				return null;
			}

			return array(
				'id'           => absint( $run['id'] ),
				'uuid'         => $run['uuid'],
				'client_uuid'  => $run['client_uuid'],
				'location_id'  => absint( $run['location_id'] ),
				'type'         => $run['type'],
				'status'       => $run['status'],
				'note'         => $run['note'],
				'started_at'   => $run['started_at'],
				'completed_at' => $run['completed_at'],
				'created_at'   => $run['created_at'],
				'updated_at'   => $run['updated_at'],
			);
		}
	}
}
