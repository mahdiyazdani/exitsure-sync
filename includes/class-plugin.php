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
			$this->init_rest_api();
				
			add_action( 'init', array( $this, 'load_textdomain' ) );
		}

		/**
		 * Loads plugin dependencies.
		 *
		 * @return void
		 */
		private function load_dependencies() {
			$files = array(
				EXITSURE_SYNC_PATH . 'includes/class-db.php',
				EXITSURE_SYNC_PATH . 'includes/class-rest-controller.php',
			);

			foreach ( $files as $file ) {
				if ( ! file_exists( $file ) ) {
					continue;
				}

				require_once $file;
			}
		}

		/**
		 * Initializes REST API controllers.
		 *
		 * @return void
		 */
		private function init_rest_api() {
			if ( ! class_exists( 'ExitSure_Sync_REST_Controller' ) ) {
				return;
			}

			$rest_controller = new ExitSure_Sync_REST_Controller();
			$rest_controller->init();
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
