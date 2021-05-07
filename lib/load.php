<?php
/**
 * Main plugin file to load other classes
 *
 * @package BDFGF
 */

namespace BDFGF;

use BDFGF\Helpers\BulkDownload;
use BDFGF\Helpers\RowActions;
use BDFGF\MetaBoxes\BulkDownload as BulkDownloadMetaBox;

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
		'helpers_bulk_download'    => new BulkDownload(),
		'helpers_row_actions'      => new RowActions(),
		'meta_boxes_bulk_download' => new BulkDownloadMetaBox(),
	];

	// Initialize all modules.
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', 'BDFGF\init' );
