<?php
/**
 * Health REST API controller.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Health_REST_Controller' ) ) {
	/**
	 * Handles health REST API routes.
	 */
	final class ExitSure_Sync_Health_REST_Controller extends ExitSure_Sync_Abstract_REST_Controller {

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
