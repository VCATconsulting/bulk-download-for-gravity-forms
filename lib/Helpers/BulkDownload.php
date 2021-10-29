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
		if ( isset( $_REQUEST['action'] ) && 'gf_bulk_download' === $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
				die();
			}

			if ( isset( $_REQUEST['gf_entry_id'] ) && isset( $_REQUEST['gf_form_id'] ) ) {
				// Check permissions.
				check_admin_referer( 'gf_bulk_download' );
				$entry_ids = [ wp_unslash( (int) $_REQUEST['gf_entry_id'] ) ];
				$form_id   = wp_unslash( (int) $_REQUEST['gf_form_id'] );
			} elseif ( isset( $_REQUEST['entry'] ) && isset( $_REQUEST['id'] ) ) {
				// Check permissions.
				check_admin_referer( 'gforms_entry_list', 'gforms_entry_list' );
				$entry_ids = wp_unslash( array_map( 'intval', $_REQUEST['entry'] ) );
				$form_id   = wp_unslash( (int) $_REQUEST['id'] );
			}

			if ( empty( $entry_ids ) ) {
				wp_die( esc_html( __( 'The bulk download link is missing an entry ID.', 'bulk-download-for-gravity-forms' ) ) );
			}

			// Get the form object.
			$form = GFAPI::get_form( $form_id );

			// Create a nice filename for the download.
			$download_filename = $this->get_download_filename( $form, $entry_ids );

			// Get the upload fields.
			$upload_fields = FormFields::get_form_upload_fields( $form_id );

			// Get upload files.
			$uploaded_files = $this->get_uploaded_files( $upload_fields, $entry_ids );

			try {
				// Create a temp file, so even if the process dies, the file might eventually get deleted.
				$zip_filename = wp_tempnam( $download_filename . '.zip' );
				// Create the ZipArchive.
				$zip = new ZipArchive();
				$zip->open( $zip_filename, ZipArchive::CREATE );

				$this->zip_uplodead_files( $uploaded_files, $zip );

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

	/**
	 * Create download filename.
	 *
	 * @param object $form The form object.
	 * @param array  $entry_ids Array of entry IDs.
	 *
	 * @return string
	 */
	public function get_download_filename( $form, $entry_ids ) {
		// Try to overwrite the filename with the title of the form.
		$form_title = sanitize_title( $form['title'] );
		if ( empty( $form_title ) ) {
			$form_title = _x( 'filename', 'default filename', 'bulk-download-for-gravity-forms' );
		}

		$suffix = 1 === count( $entry_ids ) ? $entry_ids[0] : $form['id'];

		return sprintf(
			'%s-%d',
			$form_title,
			$suffix
		);
	}

	/**
	 * Get uploaded files.
	 *
	 * @param array $upload_fields Array of all uploaded_fields.
	 * @param array $entry_ids Array of entry IDs.
	 *
	 * @return array
	 */
	public function get_uploaded_files( $upload_fields, $entry_ids ) {
		$uploaded_files = [];
		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );

			// If the field is a multi file upload, add all files from the JSON object to the array of uploaded files.
			foreach ( $upload_fields as $upload_field ) {
				$field_files = json_decode( $entry[ $upload_field ] );
				if ( is_null( $field_files ) ) {
					$field_files = [ $entry[ $upload_field ] ];
				}
				if ( ! empty( $field_files ) ) {
					$uploaded_files[ $entry_id ] = $field_files;
				}
			}

			$wp_upload_dir = wp_upload_dir();
			// Replace the URL path with the file system path for all files.
			foreach ( $uploaded_files[ $entry_id ] as $key => $uploaded_file ) {
				if ( ! empty( $uploaded_file ) ) {
					$uploaded_files[ $entry_id ][ $key ] = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $uploaded_file );
				}
			}
		}

		return $uploaded_files;
	}

	/**
	 * Add files to zip.
	 *
	 * @param array      $uploaded_files Array of uploaded files.
	 * @param ZipArchive $zip The zip Object.
	 *
	 * @return ZipArchive
	 */
	public function zip_uplodead_files( $uploaded_files, $zip ) {
		foreach ( $uploaded_files as $entry_id => $entry_files ) {
			foreach ( $entry_files as $uploaded_file ) {
				if ( is_readable( $uploaded_file ) ) {
					$zip->addFile( $uploaded_file, $entry_id . '/' . basename( $uploaded_file ) );
				}
			}
		}

		return $zip;
	}
}
