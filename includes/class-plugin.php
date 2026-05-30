<?php
/**
 * Main plugin class.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Plugin' ) ) {
	/**
	 * Main plugin coordinator.
	 */
	final class ExitSure_Sync_Plugin {

		/**
		 * Plugin instance.
		 *
		 * @var ExitSure_Sync_Plugin|null
		 */
		private static $instance = null;

		/**
		 * Gets the plugin instance.
		 *
		 * @return ExitSure_Sync_Plugin
		 */
		public static function instance() {
			if ( null !== self::$instance ) {
				return self::$instance;
			}

			self::$instance = new self();

			return self::$instance;
		}

		/**
		 * Prevents direct class construction.
		 */
		private function __construct() {}

		/**
		 * Prevents cloning the plugin instance.
		 *
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Prevents unserializing the plugin instance.
		 *
		 * @return void
		 */
		public function __wakeup() {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'ExitSure Sync plugin instance should not be unserialized.', 'exitsure-sync' ),
				'0.1.0'
			);
		}

		/**
		 * Initializes the plugin.
		 *
		 * @return void
		 */
		public function init() {
			$this->load_dependencies();

			add_action( 'init', array( $this, 'load_textdomain' ) );
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}

		/**
		 * Loads plugin dependencies.
		 *
		 * @return void
		 */
		private function load_dependencies() {
			$files = array(
				EXITSURE_SYNC_PATH . 'includes/class-db.php',
				EXITSURE_SYNC_PATH . 'includes/rest/abstract-class-rest-controller.php',
				EXITSURE_SYNC_PATH . 'includes/rest/class-health-controller.php',
				EXITSURE_SYNC_PATH . 'includes/rest/class-history-controller.php',
				EXITSURE_SYNC_PATH . 'includes/rest/class-locations-controller.php',
				EXITSURE_SYNC_PATH . 'includes/rest/class-sync-controller.php',
				EXITSURE_SYNC_PATH . 'includes/rest/class-task-templates-controller.php',
				EXITSURE_SYNC_PATH . 'includes/rest/class-checklist-runs-controller.php',
			);

			foreach ( $files as $file ) {
				if ( ! file_exists( $file ) ) {
					continue;
				}

				require_once $file;
			}
		}

		/**
		 * Registers REST API routes.
		 *
		 * @return void
		 */
		public function register_rest_routes() {
			$controllers = array(
				'ExitSure_Sync_Health_REST_Controller',
				'ExitSure_Sync_History_REST_Controller',
				'ExitSure_Sync_Locations_REST_Controller',
				'ExitSure_Sync_Sync_REST_Controller',
				'ExitSure_Sync_Task_Templates_REST_Controller',
				'ExitSure_Sync_Checklist_Runs_REST_Controller',
			);

			foreach ( $controllers as $controller_class ) {
				if ( ! class_exists( $controller_class ) ) {
					continue;
				}

				$controller = new $controller_class();

				if ( ! method_exists( $controller, 'register_routes' ) ) {
					continue;
				}

				$controller->register_routes();
			}
		}

		/**
		 * Loads plugin translations.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain(
				'exitsure-sync',
				false,
				dirname( plugin_basename( EXITSURE_SYNC_FILE ) ) . '/languages'
			);
		}
	}
}
