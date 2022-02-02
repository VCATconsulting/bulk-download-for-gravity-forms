<?php
/**
 * A class to add a meta box to single entries with a button to bulk download all files.
 *
 * @package BDFGF\MetaBoxes
 */

namespace BDFGF\MetaBoxes;

use BDFGF\Helpers\FormFields;
use GFCommon;

/**
 * Class BulkDownload
 */
class BulkDownload {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_filter( 'gform_entry_detail_meta_boxes', [ $this, 'bulk_download_meta_box' ], 10, 3 );
	}

	/**
	 * Add the bulk download button meta box.
	 *
	 * @param array $meta_boxes The properties for the meta boxes.
	 * @param array $entry      The entry currently being viewed/edited.
	 * @param array $form       The form object used to process the current entry.
	 *
	 * @return array
	 */
	public function bulk_download_meta_box( $meta_boxes, $entry, $form ) {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			return $meta_boxes;
		}

		if ( FormFields::has_uploaded_files( $entry ) ) {
			$meta_boxes['bulk_download'] = [
				'title'    => esc_html__( 'Bulk Download', 'bulk-download-for-gravity-forms' ),
				'callback' => [ $this, 'render_meta_box' ],
				'context'  => 'side',
			];
		}

		return $meta_boxes;
	}

	/**
	 * Render the meta box.
	 *
	 * @param array $args    An array with the form array and the entry array.
	 * @param array $metabox The metabox data array.
	 */
	public function render_meta_box( $args, $metabox ) {
		$entry = $args['entry'];

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
			'<a class="button" aria-label="%1$s" href="%2$s">%3$s</a>',
			esc_attr__( 'Bulk download all files from this entry', 'bulk-download-for-gravity-forms' ),
			esc_url( wp_nonce_url( $link, 'gf_bulk_download' ) ),
			esc_html__( 'Bulk download all files', 'bulk-download-for-gravity-forms' )
		);
	}
}
