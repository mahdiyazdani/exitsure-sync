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
			add_action( 'init', array( $this, 'load_textdomain' ) );
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
