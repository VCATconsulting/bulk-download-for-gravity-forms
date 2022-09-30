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
		add_action( 'admin_init', [ $this, 'handle_single_entry_download' ], 1 );
		add_action( 'gform_entry_list_action', [ $this, 'handle_bulk_action_download' ], 10, 3 );
	}

	/**
	 * Set a higher memory_limit using our own context with `wp_raise_memory_limit`.
	 *
	 * @return string
	 */
	public function set_memory_limit() {
		return '512M';
	}

	/**
	 * Handle single entry file download.
	 */
	public function handle_single_entry_download() {
		if ( 'gf_bulk_download' !== rgget( 'action' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return;
		}

		$form_id   = (int) rgget( 'gf_form_id' );
		$entry_ids = [ (int) rgget( 'gf_entry_id' ) ];

		$this->bulk_download( $form_id, $entry_ids );
	}

	/**
	 * Handle bulk action file download from multiple entries.
	 *
	 * @param string $action  Action being performed.
	 * @param array  $entries The entry IDs the action is being applied to.
	 * @param int    $form_id The current form ID.
	 */
	public function handle_bulk_action_download( $action, $entries, $form_id ) {
		if ( 'gf_bulk_download' !== $action ) {
			return;
		}

		$form_id   = (int) $form_id;
		$entry_ids = array_map( 'intval', $entries );

		$this->bulk_download( $form_id, $entry_ids );
	}

	/**
	 * Bulk download all files of an entry.
	 *
	 * @param int   $form_id   The current form ID.
	 * @param array $entry_ids Array of entry IDs.
	 */
	public function bulk_download( $form_id, $entry_ids ) {
		if ( empty( $form_id ) ) {
			wp_die( esc_html( __( 'The form ID to perform a bulk download for is missing.', 'bulk-download-for-gravity-forms' ) ) );
		}

		if ( empty( $entry_ids ) ) {
			wp_die( esc_html( __( 'The entry IDs to perform a bulk download for are missing.', 'bulk-download-for-gravity-forms' ) ) );
		}

		// Check permissions.
		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			wp_die( esc_html( __( 'You don\'t have the permission to bulk download files for this entry', 'bulk-download-for-gravity-forms' ) ) );
		}

		// Increase some PHP limits.
		add_filter( 'bdfgf_memory_limit', [ $this, 'set_memory_limit' ], 1 );
		wp_raise_memory_limit( 'bdfgf' );
		set_time_limit( apply_filters( 'bdfgf_max_execution_time', 120 ) );

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

			$this->zip_uploaded_files( $uploaded_files, $zip, $form );

			$zip->close();

			// Send ZIP file.
			ob_clean();
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

	/**
	 * Create download filename.
	 *
	 * @param object $form      The form object.
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

		$filename = sprintf(
			'%s-%d',
			$form_title,
			$suffix
		);

		// Check if the form has a custom filename definded in the settings.
		if ( isset( $form['bulkDownloadSettings']['customArchivename'] ) && true === $form['bulkDownloadSettings']['customArchivename'] ) {
			// Get the first entry.
			$first_entry = isset( $entry_ids[0] ) ? GFAPI::get_entry( $entry_ids[0] ) : null;
			// Replace all merge tags.
			$new_archive_name = GFCommon::replace_variables( $form['bulkDownloadSettings']['downloadArchivename'], $form, $first_entry );

			// The new archive name could be parsed and does not contain any merge tags, overwrite the filename.
			if ( ! empty( $new_archive_name ) && false === strpos( $new_archive_name, '{' ) ) {
				$filename = $new_archive_name;
			}
		}

		/**
		 * Filters the file name of the zip archive (without extension).
		 *
		 * @param string $filename  The current zip archive file name.
		 * @param array  $form      The GF form array.
		 * @param array  $entry_ids The entry IDs of all files being added to the archive.
		 *
		 * @return string
		 */
		return gf_apply_filters( [ 'bdfgf_download_filename', $form['id'] ], $filename, $form, $entry_ids );
	}

	/**
	 * Get uploaded files.
	 *
	 * @param array $upload_fields Array of all uploaded_fields.
	 * @param array $entry_ids     Array of entry IDs.
	 *
	 * @return array
	 */
	public function get_uploaded_files( $upload_fields, $entry_ids ) {
		$uploaded_files = [];
		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );

			$uploaded_files[ $entry_id ] = [];
			foreach ( $upload_fields as $upload_field ) {
				// If the field is a multi file upload, add all files from the JSON object to the array of uploaded files.
				$field_files = json_decode( $entry[ $upload_field ] );
				if ( is_null( $field_files ) ) {
					$field_files = [ $entry[ $upload_field ] ];
				}

				if ( ! empty( $field_files ) ) {
					$uploaded_files[ $entry_id ] = array_merge( $uploaded_files[ $entry_id ], $field_files );
				}
			}

			$wp_upload_dir = wp_upload_dir();
			// Replace the URL path with the file system path for all files.
			if ( ! empty( $uploaded_files[ $entry_id ] ) ) {
				foreach ( $uploaded_files[ $entry_id ] as $key => $uploaded_file ) {
					if ( ! empty( $uploaded_file ) ) {
						$uploaded_files[ $entry_id ][ $key ] = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $uploaded_file );
					}
				}
			} else {
				unset( $uploaded_files[ $entry_id ] );
			}
		}

		return $uploaded_files;
	}

	/**
	 * Add files to zip.
	 *
	 * @param array      $uploaded_files Array of uploaded files.
	 * @param ZipArchive $zip            The zip Object.
	 * @param array      $form           The form Object.
	 *
	 * @return ZipArchive
	 */
	public function zip_uploaded_files( $uploaded_files, $zip, $form ) {
		foreach ( $uploaded_files as $entry_id => $entry_files ) {
			foreach ( $entry_files as $uploaded_file ) {
				if ( is_readable( $uploaded_file ) ) {
					// Define a default entry file name using the entry ID as the folder name.
					$entry_filename = $entry_id . '/' . basename( $uploaded_file );

					// Check if the form has a custom filename definded in the settings.
					if ( isset( $form['bulkDownloadSettings']['customFoldername'] ) && true === $form['bulkDownloadSettings']['customFoldername'] ) {
						// Get the entry.
						$entry = GFAPI::get_entry( $entry_id );
						// Replace all merge tags.
						$new_folder_name = GFCommon::replace_variables( $form['bulkDownloadSettings']['downloadFoldername'], $form, $entry );

						// The new archive name could be parsed and does not contain any merge tags, overwrite the filename.
						if ( ! empty( $new_folder_name ) && false === strpos( $new_folder_name, '{' ) ) {
							$entry_filename = $new_folder_name . '/' . basename( $uploaded_file );
						}
					}

					/**
					 * Filters the file name of the uploaded file used in the zip archive.
					 *
					 * @param string $entry_filename The current entry file name.
					 * @param int    $entry_id       The ID of the GF entry.
					 * @param string $uploaded_file  The file path to the uploaded file.
					 *
					 * @return string
					 */
					$entry_filename = gf_apply_filters( [ 'bdfgf_entry_filename', $form['id'] ], $entry_filename, $entry_id, $uploaded_file );
					$zip->addFile( $uploaded_file, $entry_filename );
				}
			}
		}

		return $zip;
	}
}
