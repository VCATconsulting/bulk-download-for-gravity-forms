<?php
/**
 * Class to render the settings page.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFAPI;
use GFCommon;
use GFFormsModel;
use Gravity_Forms\Gravity_Forms\Settings\Settings;
use WP_Error;

/**
 * Class BulkDownloadFormSettingsPage
 *
 * @phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 */
class BulkDownloadFormSettingsPage {

	/**
	 * The cached form array.
	 *
	 * @var array $form
	 */
	private static $_form;

	/**
	 * The cached array of forms.
	 *
	 * @var array $forms
	 */
	private static $_forms;

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @var false|Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Renders the form settings.
	 *
	 * @param int $form_id The form ID.
	 */
	public static function form_settings( $form_id ) {
		if ( ! self::get_settings_renderer() ) {
			self::initialize_settings_renderer();
		}

		self::get_settings_renderer()->render();
	}

	/**
	 * Get Personal Data settings fields.
	 *
	 * @param int $form_id The current Form ID.
	 *
	 * @return array
	 */
	private static function settings_fields( $form_id ) {
		return [
			[
				'class'  => 'gform-settings-panel--full',
				'title'  => esc_html__( 'General Settings', 'bulk-download-for-gravity-forms' ),
				'fields' => [
					[
						'name'    => 'customArchivename',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Set a custom (static) filename for the archive', 'bulk-download-for-gravity-forms' ),
						'tooltip' => gform_tooltip( 'bulk_download_custom_archivename', null, true ),
					],
					[
						'name'       => 'downloadArchivename',
						'type'       => 'text',
						'class'      => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'label'      => esc_html__( 'The name for the downloaded archive', 'bulk-download-for-gravity-forms' ),
						'tooltip'    => gform_tooltip( 'bulk_download_download_archivename', null, true ),
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'customArchivename',
								],
							],
						],
					],
					[
						'name'    => 'customFoldername',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Set a custom (static) filename for the files inside the archive', 'bulk-download-for-gravity-forms' ),
						'tooltip' => gform_tooltip( 'bulk_download_custom_folderame', null, true ),
					],
					[
						'name'       => 'downloadFoldername',
						'type'       => 'text',
						'class'      => 'merge-tag-support mt-position-right mt-hide_all_fields',
						'label'      => esc_html__( 'The name for the foldername inside the downloaded archive', 'bulk-download-for-gravity-forms' ),
						'tooltip'    => gform_tooltip( 'bulk_download_download_foldername', null, true ),
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'customFoldername',
								],
							],
						],
					],
					[
						'name'    => 'customNoDownloadText',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Set a custom text if no files for download are exists ', 'bulk-download-for-gravity-forms' ),
						'tooltip' => gform_tooltip( 'bulk_download_custom_no_download_text', null, true ),
					],
					[
						'name'       => 'noDownloadText',
						'type'       => 'text',
						'class'      => '',
						'label'      => esc_html__( 'The text when no files for download are available', 'bulk-download-for-gravity-forms' ),
						'tooltip'    => gform_tooltip( 'bulk_download_no_download_text', null, true ),
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'customNoDownloadText',
								],
							],
						],
					],
					[
						'name'    => 'customNoDownloadFieldText',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Set a custom text if no download fields are exists ', 'bulk-download-for-gravity-forms' ),
						'tooltip' => gform_tooltip( 'bulk_download_custom_no_download_field', null, true ),
					],
					[
						'name'       => 'noDownloadFieldText',
						'type'       => 'text',
						'class'      => '',
						'label'      => esc_html__( 'The text when no download fields are in the form', 'bulk-download-for-gravity-forms' ),
						'tooltip'    => gform_tooltip( 'bulk_download_no_download_field', null, true ),
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'customNoDownloadFieldText',
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Saves the form settings.
	 *
	 * @param array $values Submitted settings values.
	 */
	public static function process_form_settings( $values ) {
		// Get form object.
		$form = self::get_form( rgget( 'form_id' ) );

		// Save settings.
		$form['bulkDownloadSettings']['customArchivename']         = (bool) rgar( $values, 'customArchivename' );
		$form['bulkDownloadSettings']['downloadArchivename']       = rgar( $values, 'downloadArchivename' );
		$form['bulkDownloadSettings']['customFoldername']          = (bool) rgar( $values, 'customFoldername' );
		$form['bulkDownloadSettings']['downloadFoldername']        = rgar( $values, 'downloadFoldername' );
		$form['bulkDownloadSettings']['customNoDownloadText']      = (bool) rgar( $values, 'customNoDownloadText' );
		$form['bulkDownloadSettings']['noDownloadText']            = rgar( $values, 'noDownloadText' );
		$form['bulkDownloadSettings']['customNoDownloadFieldText'] = (bool) rgar( $values, 'customNoDownloadFieldText' );
		$form['bulkDownloadSettings']['noDownloadFieldText']       = rgar( $values, 'noDownloadFieldText' );

		// Save form.
		GFAPI::update_form( $form );

		// Update cached form object.
		self::$_form = $form;
	}

	/**
	 * Initializes the Settings renderer at the beginning of page load.
	 */
	public static function initialize_settings_renderer() {
		// Get form object.
		$form_id = absint( rgget( 'id' ) );
		$form    = self::get_form( $form_id );

		$renderer = new Settings(
			[
				'header'         => [
					'icon'  => 'fa fa-lock',
					'title' => esc_html__( 'Bulk Download', 'bulk-download-for-gravity-forms' ),
				],
				'fields'         => self::settings_fields( $form_id ),
				'initial_values' => rgar( $form, 'bulkDownloadSettings' ),
				'save_callback'  => [ self::class, 'process_form_settings' ],
				'before_fields'  => function () use ( $form ) {
					return sprintf(
						'<script type="text/javascript">var form = %s;</script>',
						wp_json_encode( $form )
					);
				},
			]
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}
	}

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @return false|Settings
	 */
	private static function get_settings_renderer() {
		return self::$_settings_renderer;
	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @param Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	private static function set_settings_renderer( $renderer ) {
		// Ensure renderer is an instance of Settings.
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;
	}

	/**
	 * Returns the form array for use in the form settings.
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return array|mixed
	 */
	public static function get_form( $form_id ) {
		if ( empty( self::$_form ) ) {
			self::$_form = GFAPI::get_form( $form_id );
		}

		return self::$_form;
	}

	/**
	 * Returns an associative array of all the form metas with the form ID as the key.
	 *
	 * @return array|null
	 */
	public static function get_forms() {
		if ( is_null( self::$_forms ) ) {
			$form_ids = GFFormsModel::get_form_ids( null );

			if ( empty( $form_ids ) ) {
				return [
					'data' => [],
					'done' => true,
				];
			}

			$forms_by_id = GFFormsModel::get_form_meta_by_id( $form_ids );

			self::$_forms = [];
			foreach ( $forms_by_id as $form ) {
				self::$_forms[ $form['id'] ] = $form;
			}
		}

		return self::$_forms;
	}

	/**
	 * Writes a message to the debug log
	 *
	 * @param string $message Message to log.
	 */
	public static function log_debug( $message ) {
		GFCommon::log_debug( $message );
	}

	/**
	 * Flushes the forms
	 */
	public static function flush_current_forms() {
		self::$_forms = null;
	}
}
