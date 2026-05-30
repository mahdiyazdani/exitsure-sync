<?php
/**
 * Database helper.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_DB' ) ) {
	/**
	 * Provides shared database helpers.
	 */
	final class ExitSure_Sync_DB {

		/**
		 * Gets a plugin table name.
		 *
		 * @param string $name Table key.
		 *
		 * @return string
		 */
		public static function get_table_name( $name ) {
			global $wpdb;

			if ( ! $wpdb instanceof wpdb ) {
				return '';
			}

			$tables = self::get_tables();

			if ( empty( $tables[ $name ] ) ) {
				return '';
			}

			return $wpdb->prefix . $tables[ $name ];
		}

		/**
		 * Gets plugin table keys and names.
		 *
		 * @return array
		 */
		public static function get_tables() {
			return array(
				'locations'     => 'exitsure_locations',
				'tasks'         => 'exitsure_task_templates',
				'runs'          => 'exitsure_checklist_runs',
				'run_items'     => 'exitsure_checklist_run_items',
			);
		}

		/**
		 * Gets the current UTC datetime for database storage.
		 *
		 * @return string
		 */
		public static function get_current_datetime() {
			return gmdate( 'Y-m-d H:i:s' );
		}

		/**
		 * Gets a UUID value.
		 *
		 * @return string
		 */
		public static function get_uuid() {
			return wp_generate_uuid4();
		}
	}
}
