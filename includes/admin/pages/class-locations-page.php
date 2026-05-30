<?php
/**
 * Locations admin page.
 *
 * @package ExitSureSync
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ExitSure_Sync_Locations_Page' ) ) {
	/**
	 * Handles the locations admin page.
	 */
	final class ExitSure_Sync_Locations_Page {

		/**
		 * Admin page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'exitsure-sync-locations';

		/**
		 * Renders the locations page.
		 *
		 * @return void
		 */
		public function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Locations', 'exitsure-sync' ); ?></h1>

				<div class="card">
					<h2><?php echo esc_html__( 'Location Management', 'exitsure-sync' ); ?></h2>

					<p>
						<?php echo esc_html__( 'This screen will manage checklist locations such as home, office, apartment, or any other place.', 'exitsure-sync' ); ?>
					</p>
				</div>
			</div>
			<?php
		}
	}
}
