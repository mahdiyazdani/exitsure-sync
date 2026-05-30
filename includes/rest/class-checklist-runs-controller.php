<?php
/**
 * Checklist runs REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Checklist_Runs_REST_Controller' ) ) {
	/**
	 * Handles checklist run REST API routes.
	 */
	final class ExitSure_Sync_Checklist_Runs_REST_Controller extends ExitSure_Sync_Abstract_REST_Controller {

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_routes() {
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

			register_rest_route(
				self::NAMESPACE,
				'/locations/(?P<location_id>[\d]+)/checklists/draft',
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
						'callback'            => array( $this, 'get_draft_checklist_run' ),
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
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/checklists/(?P<run_id>[\d]+)',
				array(
					'args' => array(
						'run_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_checklist_run' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
					),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/checklists/(?P<run_id>[\d]+)/items',
				array(
					'args' => array(
						'run_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_checklist_run_items' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'checked_item_ids' => array(
								'required'          => true,
								'type'              => 'array',
								'validate_callback' => array( $this, 'validate_positive_integer_array' ),
							),
						),
					),
				)
			);

			register_rest_route(
				self::NAMESPACE,
				'/checklists/(?P<run_id>[\d]+)/cancel',
				array(
					'args' => array(
						'run_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'cancel_checklist_run' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'note' => array(
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
				'/checklists/(?P<run_id>[\d]+)/complete',
				array(
					'args' => array(
						'run_id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'complete_checklist_run' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'checked_item_ids' => array(
								'required'          => true,
								'type'              => 'array',
								'validate_callback' => array( $this, 'validate_positive_integer_array' ),
							),
							'note'             => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_textarea_field',
							),
						),
					),
				)
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
		 * Gets the latest draft checklist run for a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_draft_checklist_run( $request ) {
			global $wpdb;

			$runs_table = ExitSure_Sync_DB::get_table_name( 'runs' );

			if ( '' === $runs_table ) {
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

			$type = (string) $request->get_param( 'type' );

			if ( '' !== $type ) {
				$run = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$runs_table} WHERE location_id = %d AND type = %s AND status = %s ORDER BY updated_at DESC, id DESC LIMIT 1",
						$location_id,
						$type,
						'draft'
					),
					ARRAY_A
				);

				if ( empty( $run ) ) {
					return rest_ensure_response( null );
				}

				return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
			}

			$run = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$runs_table} WHERE location_id = %d AND status = %s ORDER BY updated_at DESC, id DESC LIMIT 1",
					$location_id,
					'draft'
				),
				ARRAY_A
			);

			if ( empty( $run ) ) {
				return rest_ensure_response( null );
			}

			return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
		}

		/**
		 * Gets a checklist run.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_checklist_run( $request ) {
			$run_id = absint( $request->get_param( 'run_id' ) );
			$run    = $this->get_checklist_run_by_id( $run_id );

			if ( empty( $run ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_not_found',
					esc_html__( 'Checklist run could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
		}

		/**
		 * Updates checklist run items without completing the run.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function update_checklist_run_items( $request ) {
			global $wpdb;

			$run_items_table = ExitSure_Sync_DB::get_table_name( 'run_items' );

			if ( '' === $run_items_table ) {
				return new WP_Error(
					'exitsure_sync_missing_checklist_tables',
					esc_html__( 'Checklist tables could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$run_id = absint( $request->get_param( 'run_id' ) );
			$run    = $this->get_checklist_run_by_id( $run_id );

			if ( empty( $run ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_not_found',
					esc_html__( 'Checklist run could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			if ( 'completed' === $run['status'] ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_completed',
					esc_html__( 'Completed checklist runs cannot be updated.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$items            = $this->get_checklist_run_items( $run_id );
			$checked_item_ids = $this->normalize_id_array( $request->get_param( 'checked_item_ids' ) );

			if ( empty( $items ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_items_not_found',
					esc_html__( 'Checklist run items could not be found.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$item_ids         = wp_list_pluck( $items, 'id' );
			$item_ids         = array_map( 'absint', $item_ids );
			$unknown_item_ids = array_diff( $checked_item_ids, $item_ids );

			if ( ! empty( $unknown_item_ids ) ) {
				return new WP_Error(
					'exitsure_sync_invalid_checklist_item_ids',
					esc_html__( 'One or more checklist items do not belong to this checklist run.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$datetime = ExitSure_Sync_DB::get_current_datetime();

			foreach ( $items as $item ) {
				$item_id    = absint( $item['id'] );
				$is_checked = in_array( $item_id, $checked_item_ids, true );

				$wpdb->update(
					$run_items_table,
					array(
						'is_checked' => $is_checked ? 1 : 0,
						'checked_at' => $is_checked ? $datetime : null,
						'updated_at' => $datetime,
					),
					array(
						'id' => $item_id,
					),
					array(
						'%d',
						'%s',
						'%s',
					),
					array(
						'%d',
					)
				);
			}

			$run = $this->get_checklist_run_by_id( $run_id );

			return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
		}

		/**
		 * Cancels a draft checklist run.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function cancel_checklist_run( $request ) {
			global $wpdb;

			$runs_table = ExitSure_Sync_DB::get_table_name( 'runs' );

			if ( '' === $runs_table ) {
				return new WP_Error(
					'exitsure_sync_missing_checklist_tables',
					esc_html__( 'Checklist tables could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$run_id = absint( $request->get_param( 'run_id' ) );
			$run    = $this->get_checklist_run_by_id( $run_id );

			if ( empty( $run ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_not_found',
					esc_html__( 'Checklist run could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			if ( 'completed' === $run['status'] ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_completed',
					esc_html__( 'Completed checklist runs cannot be cancelled.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			if ( 'cancelled' === $run['status'] ) {
				return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
			}

			$updated = $wpdb->update(
				$runs_table,
				array(
					'status'     => 'cancelled',
					'note'       => (string) $request->get_param( 'note' ),
					'updated_at' => ExitSure_Sync_DB::get_current_datetime(),
				),
				array(
					'id' => $run_id,
				),
				array(
					'%s',
					'%s',
					'%s',
				),
				array(
					'%d',
				)
			);

			if ( false === $updated ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_cancel_failed',
					esc_html__( 'Checklist run could not be cancelled.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$run = $this->get_checklist_run_by_id( $run_id );

			return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
		}

		/**
		 * Completes a checklist run.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function complete_checklist_run( $request ) {
			global $wpdb;

			$runs_table      = ExitSure_Sync_DB::get_table_name( 'runs' );
			$run_items_table = ExitSure_Sync_DB::get_table_name( 'run_items' );

			if ( '' === $runs_table || '' === $run_items_table ) {
				return new WP_Error(
					'exitsure_sync_missing_checklist_tables',
					esc_html__( 'Checklist tables could not be resolved.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$run_id = absint( $request->get_param( 'run_id' ) );
			$run    = $this->get_checklist_run_by_id( $run_id );

			if ( empty( $run ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_not_found',
					esc_html__( 'Checklist run could not be found.', 'exitsure-sync' ),
					array( 'status' => 404 )
				);
			}

			if ( 'completed' === $run['status'] ) {
				return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
			}

			$items            = $this->get_checklist_run_items( $run_id );
			$checked_item_ids = $this->normalize_id_array( $request->get_param( 'checked_item_ids' ) );

			if ( empty( $items ) ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_items_not_found',
					esc_html__( 'Checklist run items could not be found.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			foreach ( $items as $item ) {
				if ( empty( $item['is_required_snapshot'] ) ) {
					continue;
				}

				if ( in_array( absint( $item['id'] ), $checked_item_ids, true ) ) {
					continue;
				}

				return new WP_Error(
					'exitsure_sync_required_item_missing',
					esc_html__( 'All required checklist items must be checked before completion.', 'exitsure-sync' ),
					array( 'status' => 400 )
				);
			}

			$datetime = ExitSure_Sync_DB::get_current_datetime();

			foreach ( $items as $item ) {
				$is_checked = in_array( absint( $item['id'] ), $checked_item_ids, true );

				$wpdb->update(
					$run_items_table,
					array(
						'is_checked' => $is_checked ? 1 : 0,
						'checked_at' => $is_checked ? $datetime : null,
						'updated_at' => $datetime,
					),
					array(
						'id' => absint( $item['id'] ),
					),
					array(
						'%d',
						'%s',
						'%s',
					),
					array(
						'%d',
					)
				);
			}

			$updated = $wpdb->update(
				$runs_table,
				array(
					'status'       => 'completed',
					'note'         => (string) $request->get_param( 'note' ),
					'completed_at' => $datetime,
					'updated_at'   => $datetime,
				),
				array(
					'id' => $run_id,
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array(
					'%d',
				)
			);

			if ( false === $updated ) {
				return new WP_Error(
					'exitsure_sync_checklist_run_complete_failed',
					esc_html__( 'Checklist run could not be completed.', 'exitsure-sync' ),
					array( 'status' => 500 )
				);
			}

			$run = $this->get_checklist_run_by_id( $run_id );

			return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
		}

		/**
		 * Normalizes an array of IDs.
		 *
		 * @param mixed $ids Raw IDs.
		 *
		 * @return array
		 */
		private function normalize_id_array( $ids ) {
			if ( ! is_array( $ids ) ) {
				return array();
			}

			$ids = array_map( 'absint', $ids );
			$ids = array_filter( $ids );

			return array_values( array_unique( $ids ) );
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
