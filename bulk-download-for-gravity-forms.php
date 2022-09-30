<?php
/**
 * Bulk Download for Gravity Forms
 *
 * @package BDFGF
 * @author  VCAT Consulting GmbH
 * @license GPLv3
 *
 * @wordpress-plugin
 * Plugin Name: Bulk Download for Gravity Forms
 * Plugin URI: https://github.com/VCATconsulting/bulk-download-for-gravity-forms
 * Description: Bulk download all files from one or multiple Gravity Forms entries in one go.
 * Version: 2.5.0
 * Author: VCAT Consulting GmbH
 * Author URI: https://www.vcat.de
 * Text Domain: bulk-download-for-gravity-forms
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

define( 'BDFGF_VERSION', '2.5.0' );
define( 'BDFGF_FILE', __FILE__ );
define( 'BDFGF_PATH', plugin_dir_path( BDFGF_FILE ) );
define( 'BDFGF_URL', plugin_dir_url( BDFGF_FILE ) );

// The pre_init functions check the compatibility of the plugin and calls the init function, if check were successful.
bulk_download_for_gravity_forms_pre_init();

/**
 * Pre init function to check the plugins compatibility.
 */
function bulk_download_for_gravity_forms_pre_init() {
	// Check, if the min. required PHP version is available and if not, show an admin notice.
	if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
		add_action( 'admin_notices', 'bulk_download_for_gravity_forms_min_php_version_error' );

		// Stop the further processing of the plugin.
		return;
	}

	// Check, if the PHP ZIP extension is installed and the necessary class is available.
	if ( ! class_exists( 'ZipArchive' ) ) {
		add_action( 'admin_notices', 'bulk_download_for_gravity_forms_zip_extension_missing' );

		// Stop the further processing of the plugin.
		return;
	}

	if ( file_exists( BDFGF_PATH . 'composer.json' ) && ! file_exists( BDFGF_PATH . 'vendor/autoload.php' ) ) {
		add_action( 'admin_notices', 'bulk_download_for_gravity_forms_autoloader_missing' );

		// Stop the further processing of the plugin.
		return;
	} else {
		$autoloader = BDFGF_PATH . 'vendor/autoload.php';

		if ( is_readable( $autoloader ) ) {
			include $autoloader;
		}
	}

	// If all checks were succcessful, load the plugin.
	require_once BDFGF_PATH . 'lib/load.php';
}

/**
 * Show an admin notice error message, if the PHP version is too low.
 */
function bulk_download_for_gravity_forms_min_php_version_error() {
	printf(
		'<div class="error"><p>%s</p></div>',
		esc_html__( 'Bulk Download for Gravity Forms requires PHP version 5.6 or higher to function properly. Please upgrade PHP or deactivate Bulk Download for Gravity Forms.', 'bulk-download-for-gravity-forms' )
	);
}

/**
 * Show an admin notice error message, if the Composer autoloader is missing.
 */
function bulk_download_for_gravity_forms_zip_extension_missing() {
	printf(
		'<div class="error"><p>%s</p></div>',
		esc_html__( 'Bulk Download for Gravity Forms requires the PHP ZIP extension. Please activate it or ask your hoster to do so.', 'bulk-download-for-gravity-forms' )
	);
}

/**
 * Show an admin notice error message, if the Composer autoloader is missing.
 */
function bulk_download_for_gravity_forms_autoloader_missing() {
	printf(
		'<div class="error"><p>%s</p></div>',
		esc_html__( 'Bulk Download for Gravity Forms is missing the Composer autoloader file. Please run `composer install` in the root folder of the plugin or use a release version including the `vendor` folder.', 'bulk-download-for-gravity-forms' )
	);
}
