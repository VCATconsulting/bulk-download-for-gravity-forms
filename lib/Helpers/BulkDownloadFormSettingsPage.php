<?php
/**
 * Class to render the settings page.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFAPI;
use GFFormsModel;
use Gravity_Forms\Gravity_Forms\Settings\Settings;
use WP_Error;

/**
 * Class BulkDownloadFormSettingsPage
 */
class BulkDownloadFormSettingsPage {

	/**
	 * The cached form array.
	 *
	 * @since 2.4
	 *
	 * @var array $form
	 */
	private static $_form;

	/**
	 * The cached array of forms.
	 *
	 * @since 2.4
	 *
	 * @var array $forms
	 */
	private static $_forms;

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Renders the form settings.
	 *
	 * @param $form_id
	 *
	 * @since 2.4
	 *
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
	 * @since 2.5
	 *
	 */
	private static function settings_fields( $form_id ) {

		// Get form object.
		$form = self::get_form( $form_id );

		return [
			[
				'class'  => 'gform-settings-panel--full',
				'title'  => esc_html__( 'General Settings', 'bulk-download-for-gravity-forms' ),
				'fields' => [
					[
						'name'    => 'customFilename',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Set a custom (static) filename for the archive', 'bulk-download-for-gravity-forms' ),
						'tooltip' => gform_tooltip( 'bulk_download_custom_filename', null, true ),
					],
					[
						'name'       => 'downloadFilename',
						'type'       => 'text',
						'label'      => esc_html__( 'The name for the downloaded archive', 'bulk-download-for-gravity-forms' ),
						'tooltip'    => gform_tooltip( 'bulk_download_download_filename', null, true ),
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'customFilename',
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
	 *
	 * @since 2.4
	 *
	 */
	public static function process_form_settings( $values ) {

		// Get form object.
		$form = self::get_form( rgget( 'form_id' ) );

		// Prevent IP address storage.
		$form['bulkDownload']['preventIP'] = (bool) rgar( $values, 'preventIP' );

		// Retention Policy
		$form['bulkDownload']['retention'] = rgar( $values, 'retention' );

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
				'initial_values' => rgar( $form, 'bulkDownload' ),
				'save_callback'  => [ self::class, 'process_form_settings' ],
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
	 * @return false|\Gravity_Forms\Gravity_Forms\Settings
	 * @since 2.5
	 *
	 */
	private static function get_settings_renderer() {

		return self::$_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 * @since 2.5
	 *
	 */
	private static function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Settings
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;

	}


	/**
	 * Returns the form array for use in the form settings.
	 *
	 * @param int $form_id
	 *
	 * @return array|mixed
	 * @since 2.4
	 *
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
	 * @since 2.4
	 *
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
	 * @param $message
	 *
	 * @since 2.4
	 *
	 */
	public static function log_debug( $message ) {
		GFCommon::log_debug( $message );
	}

	/**
	 * Flushes the forms
	 *
	 * @since 2.4
	 */
	public static function flush_current_forms() {
		self::$_forms = null;
	}
}
