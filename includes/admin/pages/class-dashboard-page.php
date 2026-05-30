<?php
/**
 * Dashboard admin page.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Dashboard_Page' ) ) {
	/**
	 * Handles the dashboard admin page.
	 */
	final class ExitSure_Sync_Dashboard_Page {

		/**
		 * Admin page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'exitsure-sync';

		/**
		 * Renders the dashboard page.
		 *
		 * @return void
		 */
		public function render() {
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
					<h2><?php echo esc_html__( 'Locations', 'exitsure-sync' ); ?></h2>

					<p>
						<?php echo esc_html__( 'Create and manage places that will have enter and leave checklist templates.', 'exitsure-sync' ); ?>
					</p>

					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=exitsure-sync-locations' ) ); ?>">
						<?php echo esc_html__( 'Manage Locations', 'exitsure-sync' ); ?>
					</a>
				</div>
			</div>
			<?php
		}
	}
}
