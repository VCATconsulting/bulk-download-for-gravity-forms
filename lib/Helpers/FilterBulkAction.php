<?php
/**
 * Class to create a bulk action.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFCommon;


/**
 * Class FilterBulkAction
 */
class FilterBulkAction {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_filter( 'gform_entry_list_bulk_actions', [ $this, 'add_bulk_download_dropdown' ], 10, 2 );
	}

	/**
	 * Add bulk download to bulk actions.
	 *
	 * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 *
	 * @param array $actions The actions array.
	 * @param int   $form_id The form ID.
	 *
	 * @retun array
	 */
	public function add_bulk_download_dropdown( $actions, $form_id ) {
		/*
		 * Hide on trash view.
		 */
		$view = rgget( 'filter' ) ? rgget( 'filter' ) : rgget( 'status' );
		if ( in_array( $view, [ 'trash', 'spam' ], true ) ) {
			return $actions;
		}

		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			return $actions;
		}
		$form = \GFAPI::get_form( $form_id );

		$actions['gf_bulk_download'] = esc_html__( 'Bulk Download', 'bulk-download-for-gravity-forms' );

		if ( isset( $form['bulkDownloadSettings']['customDeleteEntryFiles'] ) && true === $form['bulkDownloadSettings']['customDeleteEntryFiles'] ) {
			if ( ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
				return $actions;
			}
			$actions['bdfgf_bulk_delete'] = esc_html__( 'Bulk Delete Entry Files', 'bulk-download-for-gravity-forms' );
		}

		return $actions;
	}
}
