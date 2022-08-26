<?php
/**
 * Class to add a settings page per form.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFFormSettings;

/**
 * Class BulkDownloadFormSettings
 */
class BulkDownloadFormSettings {

	/**
	 * Initialize the class
	 */
	public function init() {
		/* Tell Gravity Forms to add our form PDF settings pages */
		add_action( 'gform_form_settings_menu', [ $this, 'form_settings_menu' ], 10, 2 );
		add_action( 'gform_form_settings_page_bulk_download', [ $this, 'settings_page' ], 10, 1 );
	}

	/**
	 * Add an entry to the form settings menu.
	 *
	 * @param array $setting_tabs The settings tabs.
	 * @param int   $form_id      The ID of the form being accessed.
	 */
	public function form_settings_menu( $setting_tabs, $form_id ) {
		$setting_tabs[] = [
			'name'  => 'bulk_download',
			'label' => __( 'Bulk Download', 'bulk-download-for-gravity-forms' ),
			'icon'  => 'dashicons-media-archive dashicons',
		];

		return $setting_tabs;
	}

	/**
	 * Render the settings page.
	 *
	 * @param string $subview Used to complete the action name, allowing an additional subview to be detected.
	 */
	public function settings_page( $subview ) {
		GFFormSettings::page_header( __( 'Bulk Download', 'bulk-download-for-gravity-forms' ) );

		$form_id = absint( rgget( 'id' ) );

		BulkDownloadFormSettingsPage::form_settings( $form_id );

		GFFormSettings::page_footer();
	}
}
