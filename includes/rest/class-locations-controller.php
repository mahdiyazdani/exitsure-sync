<?php
/**
 * Locations REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Locations_REST_Controller' ) ) {
	/**
	 * Handles location REST API routes.
	 */
	final class ExitSure_Sync_Locations_REST_Controller extends ExitSure_Sync_Abstract_REST_Controller {

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_routes() {
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
	}
}
