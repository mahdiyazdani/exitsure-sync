<?php
/**
 * Task templates REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Task_Templates_REST_Controller' ) ) {
	/**
	 * Handles task template REST API routes.
	 */
	final class ExitSure_Sync_Task_Templates_REST_Controller extends ExitSure_Sync_Abstract_REST_Controller {

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				self::NAMESPACE,
				'/locations/(?P<location_id>[\d]+)/tasks',
				array(
					'args' => array(
						'location_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_location_tasks' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'type' => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => array( $this, 'validate_task_type' ),
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_location_task' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'type'        => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => array( $this, 'validate_task_type' ),
							),
							'title'       => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, 'validate_required_string' ),
							),
							'description' => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_textarea_field',
							),
							'is_required' => array(
								'required'          => false,
								'type'              => 'boolean',
								'sanitize_callback' => 'rest_sanitize_boolean',
							),
							'sort_order'  => array(
								'required'          => false,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
							),
						),
					),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/tasks/(?P<task_id>[\d]+)',
				array(
					'args' => array(
						'task_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_task_template' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_task_template' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'type'        => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => array( $this, 'validate_task_type' ),
							),
							'title'       => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, 'validate_required_string' ),
							),
							'description' => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_textarea_field',
							),
							'is_required' => array(
								'required'          => false,
								'type'              => 'boolean',
								'sanitize_callback' => 'rest_sanitize_boolean',
							),
							'is_enabled'  => array(
								'required'          => false,
								'type'              => 'boolean',
								'sanitize_callback' => 'rest_sanitize_boolean',
							),
							'sort_order'  => array(
								'required'          => false,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'disable_task_template' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
					),
				)
			);
		}

		/**
		 * Gets task templates for a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_location_tasks( $request ) {
			global $wpdb;

			$tasks_table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $tasks_table ) {
				return new WP_Error(
					'exitsure_sync_missing_tasks_table',
					esc_html__( 'Tasks table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$location_id = absint( $request->get_param( 'location_id' ) );
			$location    = $this->get_location_by_id( $location_id );

			if ( empty( $location ) ) {
				return new WP_Error(
					'exitsure_sync_location_not_found',
					esc_html__( 'Location could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			$type = (string) $request->get_param( 'type' );

			if ( '' !== $type ) {
				$tasks = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$tasks_table} WHERE location_id = %d AND type = %s AND is_enabled = %d ORDER BY sort_order ASC, id ASC",
						$location_id,
						$type,
						1
					),
					ARRAY_A
				);

				return rest_ensure_response(
					array_map(
						array( $this, 'prepare_task_template_for_response' ),
						$tasks
					)
				);
			}

			$tasks = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$tasks_table} WHERE location_id = %d AND is_enabled = %d ORDER BY type ASC, sort_order ASC, id ASC",
					$location_id,
					1
				),
				ARRAY_A
			);

			return rest_ensure_response(
				array_map(
					array( $this, 'prepare_task_template_for_response' ),
					$tasks
				)
			);
		}

		/**
		 * Creates a task template for a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_location_task( $request ) {
			global $wpdb;

			$tasks_table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $tasks_table ) {
				return new WP_Error(
					'exitsure_sync_missing_tasks_table',
					esc_html__( 'Tasks table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$location_id = absint( $request->get_param( 'location_id' ) );
			$location    = $this->get_location_by_id( $location_id );

			if ( empty( $location ) ) {
				return new WP_Error(
					'exitsure_sync_location_not_found',
					esc_html__( 'Location could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			if ( ! empty( $location['is_archived'] ) ) {
				return new WP_Error(
					'exitsure_sync_location_archived',
					esc_html__( 'Tasks cannot be added to an archived location.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$datetime    = ExitSure_Sync_DB::get_current_datetime();
			$is_required = $request->has_param( 'is_required' ) ? rest_sanitize_boolean( $request->get_param( 'is_required' ) ) : true;
			$sort_order  = $request->has_param( 'sort_order' ) ? absint( $request->get_param( 'sort_order' ) ) : 0;

			$inserted = $wpdb->insert(
				$tasks_table,
				array(
					'uuid'        => ExitSure_Sync_DB::get_uuid(),
					'location_id' => $location_id,
					'type'        => (string) $request->get_param( 'type' ),
					'title'       => (string) $request->get_param( 'title' ),
					'description' => (string) $request->get_param( 'description' ),
					'is_required' => $is_required ? 1 : 0,
					'is_enabled'  => 1,
					'sort_order'  => $sort_order,
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

			if ( false === $inserted ) {
				return new WP_Error(
					'exitsure_sync_task_create_failed',
					esc_html__( 'Task could not be created.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$task = $this->get_task_template_by_id( (int) $wpdb->insert_id );

			if ( empty( $task ) ) {
				return new WP_Error(
					'exitsure_sync_task_not_found',
					esc_html__( 'Created task could not be loaded.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			return new WP_REST_Response(
				$this->prepare_task_template_for_response( $task ),
				201
			);
		}

		/**
		 * Gets a single task template.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_task_template( $request ) {
			$task_id = absint( $request->get_param( 'task_id' ) );
			$task    = $this->get_task_template_by_id( $task_id );

			if ( empty( $task ) ) {
				return new WP_Error(
					'exitsure_sync_task_not_found',
					esc_html__( 'Task could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			return rest_ensure_response( $this->prepare_task_template_for_response( $task ) );
		}

		/**
		 * Updates a task template.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_task_template( $request ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $table ) {
				return new WP_Error(
					'exitsure_sync_missing_tasks_table',
					esc_html__( 'Tasks table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$task_id = absint( $request->get_param( 'task_id' ) );
			$task    = $this->get_task_template_by_id( $task_id );

			if ( empty( $task ) ) {
				return new WP_Error(
					'exitsure_sync_task_not_found',
					esc_html__( 'Task could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			$data    = array();
			$formats = array();

			if ( $request->has_param( 'type' ) ) {
				$data['type'] = (string) $request->get_param( 'type' );
				$formats[]    = '%s';
			}

			if ( $request->has_param( 'title' ) ) {
				$data['title'] = (string) $request->get_param( 'title' );
				$formats[]     = '%s';
			}

			if ( $request->has_param( 'description' ) ) {
				$data['description'] = (string) $request->get_param( 'description' );
				$formats[]           = '%s';
			}

			if ( $request->has_param( 'is_required' ) ) {
				$data['is_required'] = rest_sanitize_boolean( $request->get_param( 'is_required' ) ) ? 1 : 0;
				$formats[]           = '%d';
			}

			if ( $request->has_param( 'is_enabled' ) ) {
				$data['is_enabled'] = rest_sanitize_boolean( $request->get_param( 'is_enabled' ) ) ? 1 : 0;
				$formats[]          = '%d';
			}

			if ( $request->has_param( 'sort_order' ) ) {
				$data['sort_order'] = absint( $request->get_param( 'sort_order' ) );
				$formats[]          = '%d';
			}

			if ( empty( $data ) ) {
				return rest_ensure_response( $this->prepare_task_template_for_response( $task ) );
			}

			$data['updated_at'] = ExitSure_Sync_DB::get_current_datetime();
			$formats[]          = '%s';

			$updated = $wpdb->update(
				$table,
				$data,
				array(
					'id' => $task_id,
				),
				$formats,
				array(
					'%d',
				)
			);

			if ( false === $updated ) {
				return new WP_Error(
					'exitsure_sync_task_update_failed',
					esc_html__( 'Task could not be updated.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$task = $this->get_task_template_by_id( $task_id );

			return rest_ensure_response( $this->prepare_task_template_for_response( $task ) );
		}

		/**
		 * Disables a task template.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function disable_task_template( $request ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $table ) {
				return new WP_Error(
					'exitsure_sync_missing_tasks_table',
					esc_html__( 'Tasks table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$task_id = absint( $request->get_param( 'task_id' ) );
			$task    = $this->get_task_template_by_id( $task_id );

			if ( empty( $task ) ) {
				return new WP_Error(
					'exitsure_sync_task_not_found',
					esc_html__( 'Task could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			$updated = $wpdb->update(
				$table,
				array(
					'is_enabled' => 0,
					'updated_at' => ExitSure_Sync_DB::get_current_datetime(),
				),
				array(
					'id' => $task_id,
				),
				array(
					'%d',
					'%s',
				),
				array(
					'%d',
				)
			);

			if ( false === $updated ) {
				return new WP_Error(
					'exitsure_sync_task_disable_failed',
					esc_html__( 'Task could not be disabled.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$task = $this->get_task_template_by_id( $task_id );

			return rest_ensure_response( $this->prepare_task_template_for_response( $task ) );
		}
	}
}
