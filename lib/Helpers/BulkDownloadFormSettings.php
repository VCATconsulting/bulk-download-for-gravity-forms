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
		add_action( 'gform_tooltips', [ $this, 'bdfgf_add_tooltips' ], 10, 2 );
	}

	/**
	 * Add an entry to the form settings menu.
	 *
	 * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 *
	 * @param array $setting_tabs The settings tabs.
	 * @param int   $form_id The ID of the form being accessed.
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
	 * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
	 *
	 * @param string $subview Used to complete the action name, allowing an additional subview to be detected.
	 */
	public function settings_page( $subview ) {
		GFFormSettings::page_header( __( 'Bulk Download', 'bulk-download-for-gravity-forms' ) );

		$form_id = absint( rgget( 'id' ) );

		BulkDownloadFormSettingsPage::form_settings( $form_id );

		GFFormSettings::page_footer();
	}

	/**
	 * Add the tooltip to the color.
	 *
	 * @param array $tooltips The array of tooltips.
	 *
	 * @return array The array of tooltips.
	 */
	public function bdfgf_add_tooltips( $tooltips ) {
		$tooltips['bulk_download_custom_archivename'] =
			'<strong>' . esc_html__( 'Custom archive name', 'bulk-download-for-gravity-forms' ) . '</strong> ' .
			esc_html__( 'Check this box to set a custom name for the archive. You can also use field merge tags.', 'bulk-download-for-gravity-forms' );

		$tooltips['bulk_download_download_foldername'] =
			'<strong>' . esc_html__( 'Custom folder name', 'bulk-download-for-gravity-forms' ) . '</strong> ' .
			esc_html__( 'Check this box if you would like to set a custom folder name inside the ZIP file. You can also use field merge tags.', 'bulk-download-for-gravity-forms' );

		$tooltips['bulk_download_custom_no_download_text'] =
			'<strong>' . esc_html__( 'No download', 'bulk-download-for-gravity-forms' ) . '</strong> ' .
			esc_html__( 'Check this box to set custom text that is shown when no downloadable files are available. This is only visible when using the {bulk_download_link} merge tag.', 'bulk-download-for-gravity-forms' );

		$tooltips['bulk_download_custom_no_upload_field'] =
			'<strong>' . esc_html__( 'No upload field', 'bulk-download-for-gravity-forms' ) . '</strong> ' .
			esc_html__( 'Check this box to set custom text that is shown when no file upload field exists. This is only visible when using the {bulk_download_link} merge tag.', 'bulk-download-for-gravity-forms' );

		$tooltips['bulk_download_delete_entry_files'] =
			'<strong><span style="color:red">' . esc_html__( 'WARNING:', 'bulk-download-for-gravity-forms' ) . '</span> ' .
			esc_html__( 'Delete entry files', 'bulk-download-for-gravity-forms' ) . '</strong> ' .
			esc_html__( 'Check this box to allow manual or bulk deletion of files attached to entries.', 'bulk-download-for-gravity-forms' );

		return $tooltips;
	}
}
