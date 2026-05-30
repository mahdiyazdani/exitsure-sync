<?php
/**
 * REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_REST_Controller' ) ) {
	/**
	 * Handles ExitSure Sync REST API routes.
	 */
	final class ExitSure_Sync_REST_Controller {

		/**
		 * REST API namespace.
		 *
		 * @var string
		 */
		const NAMESPACE = 'exitsure/v1';

		/**
		 * Registers REST API hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				self::NAMESPACE,
				'/health',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_health' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/locations',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_locations' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_location' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'name'        => array(
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
						),
					),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/locations/(?P<location_id>[\d]+)',
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
						'callback'            => array( $this, 'get_location' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_location' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'name'        => array(
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
						),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'archive_location' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
					),
				)
			);

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

			register_rest_route(
				self::NAMESPACE,
				'/locations/(?P<location_id>[\d]+)/checklists/start',
				array(
					'args' => array(
						'location_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'start_checklist_run' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'type'        => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => array( $this, 'validate_task_type' ),
							),
							'client_uuid' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, 'validate_uuid' ),
							),
						),
					),
				)
			);
		}

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
		 * Validates a UUID value.
		 *
		 * @param mixed $value Value to validate.
		 *
		 * @return bool
		 */
		public function validate_uuid( $value ) {
			if ( ! is_string( $value ) ) {
				return false;
			}

			return wp_is_uuid( $value );
		}

		/**
		 * Gets plugin health information.
		 *
		 * @return WP_REST_Response
		 */
		public function get_health() {
			return rest_ensure_response(
				array(
					'name'       => 'ExitSure Sync',
					'version'    => EXITSURE_SYNC_VERSION,
					'namespace'  => self::NAMESPACE,
					'db_version' => get_option( 'exitsure_sync_db_version', '' ),
				)
			);
		}

		/**
		 * Gets active locations.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_locations() {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return new WP_Error(
					'exitsure_sync_missing_locations_table',
					esc_html__( 'Locations table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$locations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE is_archived = %d ORDER BY name ASC",
					0
				),
				ARRAY_A
			);

			if ( empty( $locations ) ) {
				return rest_ensure_response( array() );
			}

			return rest_ensure_response(
				array_map(
					array( $this, 'prepare_location_for_response' ),
					$locations
				)
			);
		}

		/**
		 * Creates a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function create_location( $request ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return new WP_Error(
					'exitsure_sync_missing_locations_table',
					esc_html__( 'Locations table could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$name        = (string) $request->get_param( 'name' );
			$description = (string) $request->get_param( 'description' );
			$datetime    = ExitSure_Sync_DB::get_current_datetime();

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

			if ( false === $inserted ) {
				return new WP_Error(
					'exitsure_sync_location_create_failed',
					esc_html__( 'Location could not be created.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$location = $this->get_location_by_id( (int) $wpdb->insert_id );

			if ( empty( $location ) ) {
				return new WP_Error(
					'exitsure_sync_location_not_found',
					esc_html__( 'Created location could not be loaded.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			return new WP_REST_Response(
				$this->prepare_location_for_response( $location ),
				201
			);
		}

		/**
		 * Gets a single location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_location( $request ) {
			$location_id = absint( $request->get_param( 'location_id' ) );
			$location    = $this->get_location_by_id( $location_id );

			if ( empty( $location ) ) {
				return new WP_Error(
					'exitsure_sync_location_not_found',
					esc_html__( 'Location could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			return rest_ensure_response( $this->prepare_location_for_response( $location ) );
		}

		/**
		 * Updates a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_location( $request ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return new WP_Error(
					'exitsure_sync_missing_locations_table',
					esc_html__( 'Locations table could not be resolved.', 'exitsure-sync' ),
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

			$data    = array();
			$formats = array();

			if ( $request->has_param( 'name' ) ) {
				$data['name'] = (string) $request->get_param( 'name' );
				$formats[]    = '%s';
			}

			if ( $request->has_param( 'description' ) ) {
				$data['description'] = (string) $request->get_param( 'description' );
				$formats[]           = '%s';
			}

			if ( empty( $data ) ) {
				return rest_ensure_response( $this->prepare_location_for_response( $location ) );
			}

			$data['updated_at'] = ExitSure_Sync_DB::get_current_datetime();
			$formats[]          = '%s';

			$updated = $wpdb->update(
				$table,
				$data,
				array(
					'id' => $location_id,
				),
				$formats,
				array(
					'%d',
				)
			);

			if ( false === $updated ) {
				return new WP_Error(
					'exitsure_sync_location_update_failed',
					esc_html__( 'Location could not be updated.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$location = $this->get_location_by_id( $location_id );

			return rest_ensure_response( $this->prepare_location_for_response( $location ) );
		}

		/**
		 * Archives a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function archive_location( $request ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'locations' );

			if ( '' === $table ) {
				return new WP_Error(
					'exitsure_sync_missing_locations_table',
					esc_html__( 'Locations table could not be resolved.', 'exitsure-sync' ),
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

			$updated = $wpdb->update(
				$table,
				array(
					'is_archived' => 1,
					'updated_at'  => ExitSure_Sync_DB::get_current_datetime(),
				),
				array(
					'id' => $location_id,
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
					'exitsure_sync_location_archive_failed',
					esc_html__( 'Location could not be archived.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$location = $this->get_location_by_id( $location_id );

			return rest_ensure_response( $this->prepare_location_for_response( $location ) );
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
		 * Starts a checklist run.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function start_checklist_run( $request ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$run_items_table = ExitSure_Sync_DB::get_table_name( 'run_items' );
			$tasks_table     = ExitSure_Sync_DB::get_table_name( 'tasks' );

			if ( '' === $runs_table || '' === $run_items_table || '' === $tasks_table ) {
				return new WP_Error(
					'exitsure_sync_missing_checklist_tables',
					esc_html__( 'Checklist tables could not be resolved.', 'exitsure-sync' ),
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
					esc_html__( 'Checklist runs cannot be started for an archived location.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$type        = (string) $request->get_param( 'type' );
			$client_uuid = (string) $request->get_param( 'client_uuid' );
			$existing    = $this->get_checklist_run_by_client_uuid( $client_uuid );

			if ( ! empty( $existing ) ) {
				return rest_ensure_response( $this->prepare_checklist_run_for_response( $existing ) );
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
				return new WP_Error(
					'exitsure_sync_no_tasks_found',
					esc_html__( 'No enabled tasks were found for this checklist type.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$datetime = ExitSure_Sync_DB::get_current_datetime();

			$inserted = $wpdb->insert(
				$runs_table,
				array(
					'uuid'         => ExitSure_Sync_DB::get_uuid(),
					'client_uuid'  => $client_uuid,
					'location_id'  => $location_id,
					'type'         => $type,
					'status'       => 'draft',
					'note'         => '',
					'latitude'     => null,
					'longitude'    => null,
					'started_at'   => $datetime,
					'completed_at' => null,
					'created_at'   => $datetime,
					'updated_at'   => $datetime,
				),
				array(
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%f',
					'%f',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			if ( false === $inserted ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_create_failed',
					esc_html__( 'Checklist run could not be created.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$run_id = (int) $wpdb->insert_id;

			foreach ( $tasks as $task ) {
				$wpdb->insert(
					$run_items_table,
					array(
						'uuid'                 => ExitSure_Sync_DB::get_uuid(),
						'run_id'               => $run_id,
						'task_template_id'     => absint( $task['id'] ),
						'title_snapshot'       => $task['title'],
						'is_required_snapshot' => ! empty( $task['is_required'] ) ? 1 : 0,
						'is_checked'           => 0,
						'checked_at'           => null,
						'note'                 => '',
						'created_at'           => $datetime,
						'updated_at'           => $datetime,
					),
					array(
						'%s',
						'%d',
						'%d',
						'%s',
						'%d',
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				);
			}

			$run = $this->get_checklist_run_by_id( $run_id );

			if ( empty( $run ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_not_found',
					esc_html__( 'Created checklist run could not be loaded.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			return new WP_REST_Response(
				$this->prepare_checklist_run_for_response( $run ),
				201
			);
		}

		/**
		 * Gets a location by ID.
		 *
		 * @param int $location_id Location ID.
		 *
		 * @return array|null
		 */
		private function get_location_by_id( $location_id ) {
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
		 * Prepares a location for REST API response.
		 *
		 * @param array $location Location row.
		 *
		 * @return array
		 */
		private function prepare_location_for_response( $location ) {
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
		 * Gets a task template by ID.
		 *
		 * @param int $task_id Task template ID.
		 *
		 * @return array|null
		 */
		private function get_task_template_by_id( $task_id ) {
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
		 * Prepares a task template for REST API response.
		 *
		 * @param array $task Task template row.
		 *
		 * @return array
		 */
		private function prepare_task_template_for_response( $task ) {
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

		/**
		 * Gets a checklist run by ID.
		 *
		 * @param int $run_id Checklist run ID.
		 *
		 * @return array|null
		 */
		private function get_checklist_run_by_id( $run_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'runs' );

			if ( '' === $table ) {
				return null;
			}

			$run = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE id = %d",
					$run_id
				),
				ARRAY_A
			);

			if ( empty( $run ) ) {
				return null;
			}

			return $run;
		}

		/**
		 * Gets a checklist run by client UUID.
		 *
		 * @param string $client_uuid Client UUID.
		 *
		 * @return array|null
		 */
		private function get_checklist_run_by_client_uuid( $client_uuid ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'runs' );

			if ( '' === $table ) {
				return null;
			}

			$run = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE client_uuid = %s",
					$client_uuid
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
		private function get_checklist_run_items( $run_id ) {
			global $wpdb;

			$table = ExitSure_Sync_DB::get_table_name( 'run_items' );

			if ( '' === $table ) {
				return array();
			}

			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE run_id = %d ORDER BY id ASC",
					$run_id
				),
				ARRAY_A
			);

			if ( empty( $items ) ) {
				return array();
			}

			return $items;
		}

		/**
		 * Prepares a checklist run for REST API response.
		 *
		 * @param array $run Checklist run row.
		 *
		 * @return array
		 */
		private function prepare_checklist_run_for_response( $run ) {
			return array(
				'id'           => absint( $run['id'] ),
				'uuid'         => $run['uuid'],
				'client_uuid'  => $run['client_uuid'],
				'location_id'  => absint( $run['location_id'] ),
				'type'         => $run['type'],
				'status'       => $run['status'],
				'note'         => $run['note'],
				'latitude'     => null !== $run['latitude'] ? (float) $run['latitude'] : null,
				'longitude'    => null !== $run['longitude'] ? (float) $run['longitude'] : null,
				'started_at'   => $run['started_at'],
				'completed_at' => $run['completed_at'],
				'created_at'   => $run['created_at'],
				'updated_at'   => $run['updated_at'],
				'items'        => array_map(
					array( $this, 'prepare_checklist_run_item_for_response' ),
					$this->get_checklist_run_items( absint( $run['id'] ) )
				),
			);
		}

		/**
		 * Prepares a checklist run item for REST API response.
		 *
		 * @param array $item Checklist run item row.
		 *
		 * @return array
		 */
		private function prepare_checklist_run_item_for_response( $item ) {
			return array(
				'id'                   => absint( $item['id'] ),
				'uuid'                 => $item['uuid'],
				'run_id'               => absint( $item['run_id'] ),
				'task_template_id'     => absint( $item['task_template_id'] ),
				'title_snapshot'       => $item['title_snapshot'],
				'is_required_snapshot' => (bool) $item['is_required_snapshot'],
				'is_checked'           => (bool) $item['is_checked'],
				'checked_at'           => $item['checked_at'],
				'note'                 => $item['note'],
				'created_at'           => $item['created_at'],
				'updated_at'           => $item['updated_at'],
			);
		}
	}
}
