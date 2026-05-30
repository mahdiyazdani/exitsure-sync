<?php
/**
 * Plugin Name: ExitSure Sync
 * Plugin URI:  https://github.com/mahdiyazdani/exitsure-sync
 * Description: WordPress backend and REST API for the ExitSure mobile app.
 * Version:     0.1.0
 * Author:      Mahdi Yazdani
 * Author URI:  https://github.com/mahdiyazdani
 * Text Domain: exitsure-sync
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'EXITSURE_SYNC_VERSION' ) ) {
	define( 'EXITSURE_SYNC_VERSION', '0.1.0' );
}

if ( ! defined( 'EXITSURE_SYNC_FILE' ) ) {
	define( 'EXITSURE_SYNC_FILE', __FILE__ );
}

if ( ! defined( 'EXITSURE_SYNC_PATH' ) ) {
	define( 'EXITSURE_SYNC_PATH', plugin_dir_path( EXITSURE_SYNC_FILE ) );
}

if ( ! defined( 'EXITSURE_SYNC_URL' ) ) {
	define( 'EXITSURE_SYNC_URL', plugin_dir_url( EXITSURE_SYNC_FILE ) );
}

/**
 * Loads the plugin.
 *
 * @return void
 */
function exitsure_sync_load_plugin() {
	$plugin_file = EXITSURE_SYNC_PATH . 'includes/class-plugin.php';

	if ( ! file_exists( $plugin_file ) ) {
		return;
	}

	require_once $plugin_file;

	if ( ! class_exists( 'ExitSure_Sync_Plugin' ) ) {
		return;
	}

	ExitSure_Sync_Plugin::instance()->init();
}

add_action( 'plugins_loaded', 'exitsure_sync_load_plugin' );
