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
		 * Admin page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'exitsure-sync';

		/**
		 * Registers admin hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
		}

		/**
		 * Registers plugin admin menu.
		 *
		 * @return void
		 */
		public function register_menu() {
			add_menu_page(
				esc_html__( 'ExitSure Sync', 'exitsure-sync' ),
				esc_html__( 'ExitSure Sync', 'exitsure-sync' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render_page' ),
				'dashicons-yes-alt',
				56
			);
		}

		/**
		 * Renders the main admin page.
		 *
		 * @return void
		 */
		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'ExitSure Sync', 'exitsure-sync' ); ?></h1>

				<p>
					<?php echo esc_html__( 'Manage checklist data used by the ExitSure mobile app.', 'exitsure-sync' ); ?>
				</p>

				<div class="card">
					<h2><?php echo esc_html__( 'REST API', 'exitsure-sync' ); ?></h2>

					<p>
						<?php echo esc_html__( 'The plugin REST API is available under this namespace:', 'exitsure-sync' ); ?>
					</p>

					<code><?php echo esc_html( rest_url( 'exitsure/v1' ) ); ?></code>
				</div>

				<div class="card">
					<h2><?php echo esc_html__( 'Next setup step', 'exitsure-sync' ); ?></h2>

					<p>
						<?php echo esc_html__( 'The next screen will add location management so checklist places can be created and edited from WordPress admin.', 'exitsure-sync' ); ?>
					</p>
				</div>
			</div>
			<?php
		}
	}
}
