<?php
/**
 * Main plugin file to load other classes
 *
 * @package BDFGF
 */

namespace BDFGF;

use BDFGF\Helpers\AssetsLoader;
use BDFGF\Helpers\AdminSettings;
use BDFGF\Helpers\BulkDownloadFormSettings;
use BDFGF\Helpers\DownloadMergeTag;
use BDFGF\Helpers\FilterBulkAction;
use BDFGF\Helpers\RowActions;
use BDFGF\Helpers\BulkActionValidator;
use BDFGF\MetaBoxes\BulkDownload as BulkDownloadMetaBox;
use BDFGF\Actions\BulkDownload;
use BDFGF\Actions\BulkDelete;

/**
 * Init function of the plugin
 */
function init() {
	/*
	 * Only initialize the plugin when GravityForms is active.
	 */
	if ( ! class_exists( 'GFCommon' ) ) {
		return;
	}

	/*
	 * Construct all modules to initialize.
	 */
	$modules = [
		'helpers_assets_loader'               => new AssetsLoader(),
		'helpers_admin_settings'              => new AdminSettings(),
		'helpers_bulk_download_form_settings' => new BulkDownloadFormSettings(),
		'helpers_download_merge_tag'          => new DownloadMergeTag(),
		'helpers_filter_bulk_download'        => new FilterBulkAction(),
		'helpers_row_actions'                 => new RowActions(),
		'helpers_bulk_action_validator'       => new BulkActionValidator(),
		'meta_boxes_bulk_download'            => new BulkDownloadMetaBox(),
		'actions_bulk_delete'                 => new BulkDelete(),
		'actions_bulk_download'               => new BulkDownload(),
	];

	/*
	 * Initialize all modules.
	 */
	foreach ( $modules as $module ) {
		if ( is_callable( [ $module, 'init' ] ) ) {
			call_user_func( [ $module, 'init' ] );
		}
	}
}

add_action( 'plugins_loaded', 'BDFGF\init' );
