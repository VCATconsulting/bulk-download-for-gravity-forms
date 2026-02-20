<?php
/**
 * Class to register client-side assets (scripts and stylesheets) for the Gutenberg block.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

/**
 * Class AssetsLoader
 */
class AssetsLoader {
	/**
	 * Registers all block assets so that they can be enqueued through Gutenberg in the corresponding context.
	 *
	 * @see https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type/#enqueuing-block-scripts
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_bdfgf_assets' ], 11 );
	}

	/**
	 * Register the assets for the plugin.
	 */
	public function register_assets() {
		$admin_bdfgf_path         = 'build/bdfgf-admin.asset.php';
		$admin_bdfgf_scripts_path = 'build/bdfgf-admin.js';

		if ( file_exists( BDFGF_PATH . $admin_bdfgf_path ) ) {
			$bdfgf_admin_asset = require BDFGF_PATH . $admin_bdfgf_path;
		} else {
			$bdfgf_admin_asset = [
				'dependencies' => [],
				'version'      => BDFGF_VERSION,
			];
		}

		// Register the bundled gravity forms editor JS file.
		if ( file_exists( BDFGF_PATH . $admin_bdfgf_path ) ) {
			wp_register_script(
				'bdfgf-admin',
				BDFGF_URL . $admin_bdfgf_scripts_path,
				$bdfgf_admin_asset['dependencies'],
				$bdfgf_admin_asset['version'],
				true
			);
		}

		wp_set_script_translations( 'bdfgf-admin', 'bulk-download-for-gravity-forms', plugin_dir_path( BDFGF_FILE ) . 'languages' );

		wp_localize_script(
			'bdfgf-admin',
			'bdfgf_bulk_delete',
			[
				'confirmMessage'      => __( 'Are you sure you want to permanently delete all files for the selected entries?', 'bulk-download-for-gravity-forms' ),
				'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'bdfgf_bulk_validate' ),
				'msgNoDownloadables'  => __( 'No downloadable files exist for the selected entries.', 'bulk-download-for-gravity-forms' ),
				'msgNoDeletables'     => __( 'No deletable files exist for the selected entries.', 'bulk-download-for-gravity-forms' ),
				'msgValidationFailed' => __( 'Could not validate the selected entries.', 'bulk-download-for-gravity-forms' ),
				'formId'              => isset( $_GET['id'] ) ? (int) $_GET['id'] : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			]
		);
	}


	/**
	 * Enqueue the gravity forms editor assets.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_bdfgf_assets( $hook ) {
		if ( 'forms_page_gf_entries' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'bdfgf-admin' );
	}
}
