<?php
/**
 * Admin handler.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Admin' ) ) {
	/**
	 * Handles WordPress admin screens.
	 */
	final class ExitSure_Sync_Admin {

		/**
		 * Registers admin hooks.
		 *
		 * @return void
		 */
		public function init() {
			$this->load_dependencies();

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
		}

		/**
		 * Loads admin dependencies.
		 *
		 * @return void
		 */
		private function load_dependencies() {
			$files = array(
				EXITSURE_SYNC_PATH . 'includes/admin/pages/class-dashboard-page.php',
				EXITSURE_SYNC_PATH . 'includes/admin/pages/class-locations-page.php',
			);

			foreach ( $files as $file ) {
				if ( ! file_exists( $file ) ) {
					continue;
				}

				require_once $file;
			}
		}

		/**
		 * Registers plugin admin menu.
		 *
		 * @return void
		 */
		public function register_menu() {
			$dashboard_page = class_exists( 'ExitSure_Sync_Dashboard_Page' ) ? new ExitSure_Sync_Dashboard_Page() : null;
			$locations_page = class_exists( 'ExitSure_Sync_Locations_Page' ) ? new ExitSure_Sync_Locations_Page() : null;

			add_menu_page(
				esc_html__( 'ExitSure Sync', 'exitsure-sync' ),
				esc_html__( 'ExitSure Sync', 'exitsure-sync' ),
				'manage_options',
				'exitsure-sync',
				null !== $dashboard_page ? array( $dashboard_page, 'render' ) : '__return_null',
				'dashicons-yes-alt',
				56
			);

			add_submenu_page(
				'exitsure-sync',
				esc_html__( 'Dashboard', 'exitsure-sync' ),
				esc_html__( 'Dashboard', 'exitsure-sync' ),
				'manage_options',
				'exitsure-sync',
				null !== $dashboard_page ? array( $dashboard_page, 'render' ) : '__return_null'
			);

			add_submenu_page(
				'exitsure-sync',
				esc_html__( 'Locations', 'exitsure-sync' ),
				esc_html__( 'Locations', 'exitsure-sync' ),
				'manage_options',
				'exitsure-sync-locations',
				null !== $locations_page ? array( $locations_page, 'render' ) : '__return_null'
			);
		}
	}
}
