<?php
/**
 * Plugin activation handler.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Activator' ) ) {
	/**
	 * Handles plugin activation tasks.
	 */
	final class ExitSure_Sync_Activator {

		/**
		 * Database schema version.
		 *
		 * @var string
		 */
		const DB_VERSION = '0.1.0';

		/**
		 * Runs activation tasks.
		 *
		 * @return void
		 */
		public static function activate() {
			self::create_tables();

			update_option( 'exitsure_sync_db_version', self::DB_VERSION );
		}

		/**
		 * Creates custom plugin tables.
		 *
		 * @return void
		 */
		private static function create_tables() {
			global $wpdb;

			if ( ! $wpdb instanceof wpdb ) {
				return;
			}

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$charset_collate = $wpdb->get_charset_collate();
			$tables          = self::get_schema( $charset_collate );

			foreach ( $tables as $table ) {
				dbDelta( $table );
			}
		}

		/**
		 * Gets database schema statements.
		 *
		 * @param string $charset_collate Database charset and collation.
		 *
		 * @return array
		 */
		private static function get_schema( $charset_collate ) {
			global $wpdb;

			$locations_table     = $wpdb->prefix . 'exitsure_locations';
			$tasks_table         = $wpdb->prefix . 'exitsure_task_templates';
			$runs_table          = $wpdb->prefix . 'exitsure_checklist_runs';
			$run_items_table     = $wpdb->prefix . 'exitsure_checklist_run_items';

			return array(
				"CREATE TABLE {$locations_table} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					uuid char(36) NOT NULL,
					name varchar(191) NOT NULL,
					description text NULL,
					is_archived tinyint(1) NOT NULL DEFAULT 0,
					created_at datetime NOT NULL,
					updated_at datetime NOT NULL,
					PRIMARY KEY  (id),
					UNIQUE KEY uuid (uuid),
					KEY is_archived (is_archived)
				) {$charset_collate};",

				"CREATE TABLE {$tasks_table} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					uuid char(36) NOT NULL,
					location_id bigint(20) unsigned NOT NULL,
					type varchar(20) NOT NULL,
					title varchar(191) NOT NULL,
					description text NULL,
					is_required tinyint(1) NOT NULL DEFAULT 1,
					is_enabled tinyint(1) NOT NULL DEFAULT 1,
					sort_order int(11) NOT NULL DEFAULT 0,
					created_at datetime NOT NULL,
					updated_at datetime NOT NULL,
					PRIMARY KEY  (id),
					UNIQUE KEY uuid (uuid),
					KEY location_id (location_id),
					KEY type (type),
					KEY is_enabled (is_enabled),
					KEY sort_order (sort_order)
				) {$charset_collate};",

				"CREATE TABLE {$runs_table} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					uuid char(36) NOT NULL,
					client_uuid char(36) NOT NULL,
					location_id bigint(20) unsigned NOT NULL,
					type varchar(20) NOT NULL,
					status varchar(20) NOT NULL,
					note text NULL,
					latitude decimal(10,8) NULL,
					longitude decimal(11,8) NULL,
					started_at datetime NOT NULL,
					completed_at datetime NULL,
					created_at datetime NOT NULL,
					updated_at datetime NOT NULL,
					PRIMARY KEY  (id),
					UNIQUE KEY uuid (uuid),
					UNIQUE KEY client_uuid (client_uuid),
					KEY location_id (location_id),
					KEY type (type),
					KEY status (status),
					KEY completed_at (completed_at)
				) {$charset_collate};",

				"CREATE TABLE {$run_items_table} (
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					uuid char(36) NOT NULL,
					run_id bigint(20) unsigned NOT NULL,
					task_template_id bigint(20) unsigned NOT NULL,
					title_snapshot varchar(191) NOT NULL,
					is_required_snapshot tinyint(1) NOT NULL DEFAULT 1,
					is_checked tinyint(1) NOT NULL DEFAULT 0,
					checked_at datetime NULL,
					note text NULL,
					created_at datetime NOT NULL,
					updated_at datetime NOT NULL,
					PRIMARY KEY  (id),
					UNIQUE KEY uuid (uuid),
					KEY run_id (run_id),
					KEY task_template_id (task_template_id),
					KEY is_checked (is_checked)
				) {$charset_collate};",
			);
		}
	}
}
