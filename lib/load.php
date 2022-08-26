<?php
/**
 * Main plugin file to load other classes
 *
 * @package BDFGF
 */

namespace BDFGF;

use BDFGF\Helpers\BulkDownload;
use BDFGF\Helpers\BulkDownloadFormSettings;
use BDFGF\Helpers\DownloadMergeTag;
use BDFGF\Helpers\FilterBulkAction;
use BDFGF\Helpers\RowActions;
use BDFGF\MetaBoxes\BulkDownload as BulkDownloadMetaBox;
use BulkDownloadAddon;
use GFAddOn;
use GFForms;

/**
 * Init function of the plugin
 */
function init() {
	// Only initialize the plugin when GravityForms is active.
	if ( ! class_exists( 'GFCommon' ) ) {
		return;
	}

	// Construct all modules to initialize.
	$modules = [
		'filter_bulk_download'                => new FilterBulkAction(),
		'helpers_bulk_download_form_settings' => new BulkDownloadFormSettings(),
		'helpers_bulk_download'               => new BulkDownload(),
		'helpers_row_actions'                 => new RowActions(),
		'download_merge_tag'                  => new DownloadMergeTag(),
		'meta_boxes_bulk_download'            => new BulkDownloadMetaBox(),
	];

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', 'BDFGF\init' );

/**
 * Initialize the GF addon.
 */
function init_gform_addon() {
	// Include the Gravity Forms add-on framework.
	GFForms::include_addon_framework();
	// Register our addon.
	GFAddOn::register( BulkDownloadAddon::class );
}

add_action( 'gform_loaded', 'BDFGF\init_gform_addon', 1 );
