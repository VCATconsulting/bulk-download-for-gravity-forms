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
			?>
			<span class="bulk-download">
				|
				<a aria-label="<?php esc_attr_e( 'Bulk download all files from this entry', 'bulk-download-for-gravity-forms' ); ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php' ) . '?page=gf_entries&action=gf_bulk_download&gf_entry_id=' . esc_attr( $entry['id'] ), 'gf_bulk_download' ) ); ?>">
					<?php esc_html_e( 'Bulk Download', 'bulk-download-for-gravity-forms' ); ?>
				</a>
			</span>
			<?php
		}
	}
}
