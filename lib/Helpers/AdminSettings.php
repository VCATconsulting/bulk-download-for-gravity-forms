<?php
/**
 * Class to add a settings page per form.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

/**
 * Class AdminSettings
 */
class AdminSettings {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'initialize_admin_settings' ] );
	}

	/**
	 * Initialize all of the admin settings based on the current admin page.
	 */
	public function initialize_admin_settings() {
		$gf_page = self::get_page();

		// Initialize Personal Data settings.
		if ( 'bulk_download_edit' === $gf_page ) {
			BulkDownloadFormSettingsPage::initialize_settings_renderer();
		}
	}

	/**
	 * Gets current page name.
	 */
	public static function get_page() {
		if ( 'gf_edit_forms' === rgget( 'page' ) && 'settings' === rgget( 'view' ) && 'bulk_download' === rgget( 'subview' ) ) {
			return 'bulk_download_edit';
		}
	}
}
