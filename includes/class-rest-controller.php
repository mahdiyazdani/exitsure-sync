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
	}
}
