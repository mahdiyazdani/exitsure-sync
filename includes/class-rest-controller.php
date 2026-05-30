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
	}
}
