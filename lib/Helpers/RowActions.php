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
	 * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 *
	 * @param int    $form_id The form ID.
	 * @param int    $field_id The field ID.
	 * @param string $value The field value.
	 * @param array  $entry The entry data.
	 * @param string $query_string The URL query string.
	 */
	public function bulk_download_row_action( $form_id, $field_id, $value, $entry, $query_string ) {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			return;
		}

		if ( FormFields::has_uploaded_files( $entry ) && $this->entry_has_readable_files( $entry, (int) $entry['form_id'] ) ) {
			$form = \GFAPI::get_form( $form_id );

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

			if ( ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
				return;
			}

			if ( isset( $form['bulkDownloadSettings']['customDeleteEntryFiles'] ) && true === $form['bulkDownloadSettings']['customDeleteEntryFiles'] ) {
				$link = add_query_arg(
					[
						'action'      => 'bdfgf_bulk_delete',
						'gf_entry_id' => esc_attr( $entry['id'] ),
						'gf_form_id'  => esc_attr( $entry['form_id'] ),
					],
					admin_url( 'admin-post.php' )
				);

				$link = wp_nonce_url( $link, 'bdfgf_bulk_delete_entry_' . $entry['id'] );

				printf(
					'<span class="delete bulk-download"> | <a class="bdfgf-confirm-delete-link"  aria-label="%1$s" href="%2$s">%3$s</a></span>',
					esc_attr__( 'Bulk delete all files from this entry', 'bulk-download-for-gravity-forms' ),
					esc_url( $link ),
					esc_html__( 'Bulk delete files', 'bulk-download-for-gravity-forms' )
				);
			}
		}
	}

	/**
	 * Check if the entry has any readable files.
	 *
	 * @param array $entry The entry data.
	 * @param int   $form_id The form ID.
	 *
	 * @return bool
	 */
	private function entry_has_readable_files( array $entry, int $form_id ): bool {
		$upload_field_ids = FormFields::get_form_upload_fields( $form_id );
		if ( empty( $upload_field_ids ) ) {
			return false;
		}

		$entry_id = (int) rgar( $entry, 'id' );
		if ( ! $entry_id ) {
			return false;
		}

		/*
		 * Build a set of deleted URLs for quick lookup. This allows us to skip any URLs that have been marked as deleted, even if they are still referenced in the entry's upload fields.
		 * The deleted URLs are stored as an array in the entry meta with the key 'bdfgf_deleted_file_urls'. We unserialize it and filter out any non-string values just to be safe.
		 * Then we create an associative array (set) where the keys are the deleted URLs and the values are true. This allows for O(1) lookups when checking if a URL is deleted.
		 * By doing this upfront, we can efficiently skip any URLs that have been marked as deleted while iterating through the upload field URLs.
		 */
		$deleted_urls = gform_get_meta( $entry_id, 'bdfgf_deleted_file_urls' );
		$deleted_urls = maybe_unserialize( $deleted_urls );
		$deleted_urls = is_array( $deleted_urls ) ? $deleted_urls : [];
		$deleted_urls = array_values( array_filter( $deleted_urls, 'is_string' ) );
		$deleted_set  = array_fill_keys( $deleted_urls, true );

		$wp_upload_dir = wp_upload_dir();

		foreach ( $upload_field_ids as $field_id ) {
			$raw = (string) rgar( $entry, (string) $field_id );
			if ( '' === $raw ) {
				continue;
			}

			$decoded = json_decode( $raw, true );
			$urls    = is_array( $decoded ) ? $decoded : [ $raw ];

			foreach ( (array) $urls as $url ) {
				if ( ! is_string( $url ) || '' === $url ) {
					continue;
				}

				/*
				 * Check if the URL is in the deleted set. If it is, we skip it because it has been marked as deleted, even if it is still referenced in the entry's upload fields.
				 * This ensures that we only consider URLs that are not marked as deleted when checking for readability.
				 */
				if ( isset( $deleted_set[ $url ] ) ) {
					continue;
				}

				$path = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $url );

				if ( is_readable( $path ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
