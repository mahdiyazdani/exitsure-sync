<?php
/**
 * Base REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Abstract_REST_Controller' ) ) {
	/**
	 * Provides shared REST API helpers.
	 */
	abstract class ExitSure_Sync_Abstract_REST_Controller {

		/**
		 * REST API namespace.
		 *
		 * @var string
		 */
		const NAMESPACE = 'exitsure/v1';

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		abstract public function register_routes();

		/**
		 * Checks whether the current user can access admin-only API routes.
		 *
		 * @return bool
		 */
		public function can_manage_options() {
			return current_user_can( 'manage_options' );
		}

		/**
		 * Validates a required string value.
		 *
		 * @param mixed $value Value to validate.
		 *
		 * @return bool
		 */
		public function validate_required_string( $value ) {
			return is_string( $value ) && '' !== trim( $value );
		}

		/**
		 * Validates a task type.
		 *
		 * @param mixed $value Value to validate.
		 *
		 * @return bool
		 */
		public function validate_task_type( $value ) {
			if ( ! is_string( $value ) ) {
				return false;
			}

			return in_array( $value, array( 'enter', 'leave' ), true );
		}

		/**
		 * Gets a location by ID.
		 *
		 * @param int $location_id Location ID.
		 *
		 * @return array|null
		 */
		protected function get_location_by_id( $location_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return null;
			}

			$location = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE id = %d",
					$location_id
				),
				ARRAY_A
			);

			if ( empty( $location ) ) {
				return null;
			}

			return $location;
		}

		/**
		 * Gets a task template by ID.
		 *
		 * @param int $task_id Task template ID.
		 *
		 * @return array|null
		 */
		protected function get_task_template_by_id( $task_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $table ) {
				return null;
			}

			$task = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE id = %d",
					$task_id
				),
				ARRAY_A
			);

			if ( empty( $task ) ) {
				return null;
			}

			return $task;
		}

		/**
		 * Prepares a location for REST API response.
		 *
		 * @param array $location Location row.
		 *
		 * @return array
		 */
		protected function prepare_location_for_response( $location ) {
			return array(
				'id'          => absint( $location['id'] ),
				'uuid'        => $location['uuid'],
				'name'        => $location['name'],
				'description' => $location['description'],
				'is_archived' => (bool) $location['is_archived'],
				'created_at'  => $location['created_at'],
				'updated_at'  => $location['updated_at'],
			);
		}

		/**
		 * Prepares a task template for REST API response.
		 *
		 * @param array $task Task template row.
		 *
		 * @return array
		 */
		protected function prepare_task_template_for_response( $task ) {
			return array(
				'id'          => absint( $task['id'] ),
				'uuid'        => $task['uuid'],
				'location_id' => absint( $task['location_id'] ),
				'type'        => $task['type'],
				'title'       => $task['title'],
				'description' => $task['description'],
				'is_required' => (bool) $task['is_required'],
				'is_enabled'  => (bool) $task['is_enabled'],
				'sort_order'  => absint( $task['sort_order'] ),
				'created_at'  => $task['created_at'],
				'updated_at'  => $task['updated_at'],
			);
		}
	}
}
