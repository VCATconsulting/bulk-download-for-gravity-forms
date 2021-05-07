<?php
/**
 * Class to download all files of an entry in a ZIP file.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFAPI;
use GFCommon;
use ZipArchive;

/**
 * Class BulkDownload
 */
class BulkDownload {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'bulk_download' ], 1 );
	}

	/**
	 * Bulk download all files of an entry.
	 */
	public function bulk_download() {
		if ( isset( $_GET['action'] ) && 'gf_bulk_download' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			// Check permissions.
			check_admin_referer( 'gf_bulk_download' );

			if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
				die();
			}

			if ( isset( $_GET['gf_entry_id'] ) ) {
				$entry_id = wp_unslash( (int) $_GET['gf_entry_id'] );
			}

			if ( empty( $entry_id ) ) {
				wp_die( esc_html( __( 'The bulk download link is missing an entry ID.', 'bulk-download-for-gravity-forms' ) ) );
			}

			// Create a nice filename for the download.
			$download_filename = sprintf(
			// translators: %s: The entry ID.
				__( 'filename-%d', 'bulk-download-for-gravity-forms' ),
				$entry_id
			);

			try {
				// Get the entry and it's corresponding form object.
				$entry = GFAPI::get_entry( $entry_id );
				// Get the upload fields.
				$upload_fields = FormFields::get_form_upload_fields( $entry['form_id'] );

				$uploaded_files = [];
				// If the field is a multi file upload, add all files from the JSON object to the array of uploaded files.
				foreach ( $upload_fields as $upload_field ) {
					$field_files = json_decode( $entry[ $upload_field ] );
					if ( is_null( $field_files ) ) {
						$field_files = [ $entry[ $upload_field ] ];
					}
					$uploaded_files = array_merge( $uploaded_files, $field_files );
				}

				$wp_upload_dir = wp_upload_dir();
				// Replace the URL path with the file system path for all files.
				foreach ( $uploaded_files as $key => $uploaded_file ) {
					$uploaded_files[ $key ] = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $uploaded_file );
				}

				// Create a temp file, so even if the process dies, the file might eventually get deleted.
				$zip_filename = wp_tempnam( $download_filename . '.zip' );
				// Create the ZipArchive.
				$zip = new ZipArchive();
				$zip->open( $zip_filename, ZipArchive::CREATE );

				foreach ( $uploaded_files as $uploaded_file ) {
					$zip->addFile( $uploaded_file, basename( $uploaded_file ) );
				}

				$zip->close();

				// Send ZIP file.
				header( 'Pragma: public' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Content-Type: application/octet-stream;' );
				header( 'Content-Disposition: attachment; filename=' . $download_filename . '.zip;' );
				readfile( $zip_filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
				unlink( $zip_filename );
			} catch ( \Exception $e ) {
				// translators: %s: The error message.
				wp_die( esc_html( sprintf( __( 'There was an error creating the ZIP file: %s', 'bulk-download-for-gravity-forms' ), $e->getMessage() ) ) );
			}
			die();
		}
	}
}
