<?php
/**
 * Checklist history REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_History_REST_Controller' ) ) {
	/**
	 * Handles checklist history REST API routes.
	 */
	final class ExitSure_Sync_History_REST_Controller extends ExitSure_Sync_Abstract_REST_Controller {

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				self::NAMESPACE,
				'/locations/(?P<location_id>[\d]+)/checklists/latest',
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
						'callback'            => array( $this, 'get_latest_checklist_run' ),
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
				'/locations/(?P<location_id>[\d]+)/checklists/history',
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
						'callback'            => array( $this, 'get_checklist_history' ),
						'permission_callback' => array( $this, 'can_manage_options' ),
						'args'                => array(
							'type'  => array(
								'required'          => false,
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => array( $this, 'validate_task_type' ),
							),
							'page'  => array(
								'required'          => false,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
							),
							'limit' => array(
								'required'          => false,
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
							),
						),
					),
				)
			);
		}

		/**
		 * Gets the latest completed checklist run for a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_latest_checklist_run( $request ) {
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
						"SELECT * FROM {$runs_table} WHERE location_id = %d AND type = %s AND status = %s ORDER BY completed_at DESC, id DESC LIMIT 1",
						$location_id,
						$type,
						'completed'
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
					"SELECT * FROM {$runs_table} WHERE location_id = %d AND status = %s ORDER BY completed_at DESC, id DESC LIMIT 1",
					$location_id,
					'completed'
				),
				ARRAY_A
			);

			if ( empty( $run ) ) {
				return rest_ensure_response( null );
			}

			return rest_ensure_response( $this->prepare_checklist_run_for_response( $run ) );
		}

		/**
		 * Gets completed checklist history for a location.
		 *
		 * @param WP_REST_Request $request Request object.
		 *
		 * @return WP_REST_Response|WP_Error
		 */
		public function get_checklist_history( $request ) {
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

			$page   = max( 1, absint( $request->get_param( 'page' ) ) );
			$limit  = absint( $request->get_param( 'limit' ) );
			$limit  = $limit > 0 ? min( 50, $limit ) : 20;
			$offset = ( $page - 1 ) * $limit;
			$type   = (string) $request->get_param( 'type' );

			if ( '' !== $type ) {
				$total = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(id) FROM {$runs_table} WHERE location_id = %d AND type = %s AND status = %s",
						$location_id,
						$type,
						'completed'
					)
				);

				$runs = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$runs_table} WHERE location_id = %d AND type = %s AND status = %s ORDER BY completed_at DESC, id DESC LIMIT %d OFFSET %d",
						$location_id,
						$type,
						'completed',
						$limit,
						$offset
					),
					ARRAY_A
				);

				return rest_ensure_response(
					$this->prepare_history_response( $runs, $page, $limit, $total )
				);
			}

			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id) FROM {$runs_table} WHERE location_id = %d AND status = %s",
					$location_id,
					'completed'
				)
			);

			$runs = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$runs_table} WHERE location_id = %d AND status = %s ORDER BY completed_at DESC, id DESC LIMIT %d OFFSET %d",
					$location_id,
					'completed',
					$limit,
					$offset
				),
				ARRAY_A
			);

			return rest_ensure_response(
				$this->prepare_history_response( $runs, $page, $limit, $total )
			);
		}

		/**
		 * Prepares checklist history response.
		 *
		 * @param array $runs  Checklist run rows.
		 * @param int   $page  Current page.
		 * @param int   $limit Items per page.
		 * @param int   $total Total matched items.
		 *
		 * @return array
		 */
		private function prepare_history_response( $runs, $page, $limit, $total ) {
			return array(
				'page'        => absint( $page ),
				'limit'       => absint( $limit ),
				'total'       => absint( $total ),
				'total_pages' => $limit > 0 ? (int) ceil( $total / $limit ) : 0,
				'items'       => array_map(
					array( $this, 'prepare_checklist_run_for_response' ),
					! empty( $runs ) ? $runs : array()
				),
			);
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
