<?php
/**
 * Class to add row action link to GF entries list table.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFCommon;

/**
 * Class RowActions
 */
class RowActions {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'gform_entries_first_column_actions', [ $this, 'bulk_download_row_action' ], 10, 5 );
	}

	/**
	 * Add bulk download row action link to GF entries list table.
	 *
	 * @param int    $form_id      The form ID.
	 * @param int    $field_id     The field ID.
	 * @param string $value        The field value.
	 * @param array  $entry        The entry data.
	 * @param string $query_string The URL query string.
	 */
	public function bulk_download_row_action( $form_id, $field_id, $value, $entry, $query_string ) {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			return;
		}

		if ( FormFields::has_uploaded_files( $entry ) ) {
			$link = add_query_arg(
				[
					'page'        => 'gf_entries',
					'action'      => 'gf_bulk_download',
					'gf_entry_id' => esc_attr( $entry['id'] ),
					'gf_form_id'  => esc_attr( $entry['form_id'] ),
				],
				admin_url( 'admin.php' )
			);

			printf(
				'<span class="bulk-download"> | <a aria-label="%1$s" href="%2$s">%3$s</a></span>',
				esc_attr__( 'Bulk download all files from this entry', 'bulk-download-for-gravity-forms' ),
				esc_url( $link, 'gf_bulk_download' ),
				esc_html__( 'Bulk Download', 'bulk-download-for-gravity-forms' )
			);
		}
	}
}
