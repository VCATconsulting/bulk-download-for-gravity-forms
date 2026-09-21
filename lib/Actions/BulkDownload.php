<?php
/**
 * Class to download all files of an entry in a ZIP file.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Actions;

use BDFGF\Helpers\FormFields;
use GFAPI;
use GFCommon;
use WordPress\AiClient\Common\Exception\RuntimeException;
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
		add_filter( 'nonce_life', [ $this, 'filter_download_nonce_life' ], 10, 2 );
	}

	/**
	 * Filter the nonce lifetime for single-entry download links.
	 *
	 * @param int        $lifespan Lifespan of nonces in seconds.
	 * @param string|int $action   The nonce action.
	 *
	 * @return int
	 */
	public function filter_download_nonce_life( $lifespan, $action ) {
		$download_action_prefix = 'bdfgf_bulk_download_entry_';

		if ( 0 !== strpos( (string) $action, $download_action_prefix ) ) {
			return $lifespan;
		}

		/**
		 * Filters the lifetime of single-entry download nonces.
		 *
		 * @param int        $lifespan Lifespan in seconds.
		 * @param string|int $action   The nonce action containing the entry ID.
		 */
		return max( 1, absint( apply_filters( 'bdfgf_download_nonce_life', $lifespan, $action ) ) );
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

		$form_id      = (int) rgget( 'gf_form_id' );
		$entry_id     = absint( rgget( 'gf_entry_id' ) );
		$is_mail_link = 1 === absint( rgget( 'bdfgf_mail_link' ) );

		/*
		 * Check if the download link is valid, if it is a mail link. Otherwise check the nonce for the bulk download action.
		 */
		if ( $is_mail_link ) {
			if ( ! $this->is_valid_mail_download_token( $form_id, $entry_id ) ) {
				wp_die(
					esc_html__( 'The download link is invalid or has expired.', 'bulk-download-for-gravity-forms' ),
					'',
					[ 'response' => 403 ]
				);
			}
		} else {
			check_admin_referer( 'bdfgf_bulk_download_entry_' . $entry_id );
		}

		/*
		 * Pass an empty array if entry id is not valid, to trigger the appropiate error message in bulk_download().
		 */
		$entry_ids = [];
		if ( 0 !== $entry_id ) {
			$entry_ids[] = $entry_id;
		}

		$this->bulk_download( $form_id, $entry_ids );
	}

	/**
	 * Handle bulk action file download from multiple entries.
	 *
	 * @param string $action Action being performed.
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
	 *
	 * @throws \Exception If ZIP creation fails.
	 */
	public function bulk_download( $form_id, $entry_ids ) {
		if ( empty( $form_id ) ) {
			wp_die( esc_html( __( 'The form ID required for bulk download is missing.', 'bulk-download-for-gravity-forms' ) ) );
		}

		if ( empty( $entry_ids ) ) {
			wp_die( esc_html( __( 'The entry IDs required for bulk download are missing.', 'bulk-download-for-gravity-forms' ) ) );
		}

		/*
		 * Check permissions and if user is logged in.
		 */
		$download_permitted = GFCommon::current_user_can_any( 'gravityforms_view_entries' );

		/**
		 * Filters the download permission.
		 *
		 * @param bool        $download_permitted  True if user is logged in and has gravityforms_view_entries permission.
		 * @param int         $form_id The GF form id.
		 * @param array<int>  $entry_ids           The entry IDs of which all files will be added to the archive.
		 *
		 * @return bool
		 */
		$download_permitted = gf_apply_filters( [ 'bdfgf_download_permission', $form_id ], $download_permitted, $form_id, $entry_ids );

		/*
		 * Check if userer has no permission.
		 */
		if ( ! $download_permitted ) {
			wp_die( esc_html( __( 'You do not have permission to bulk download files for these entries.', 'bulk-download-for-gravity-forms' ) ) );
		}

		/*
		 * Add filter to Increase some PHP limits.
		 */
		add_filter( 'bdfgf_memory_limit', [ $this, 'set_memory_limit' ], 1 );
		wp_raise_memory_limit( 'bdfgf' );

		/**
		 * Filters the max execution time.
		 *
		 * @param int $max_execution_time Max. execution time in seconds. Default: 120.
		 *
		 * @return int
		 */
		$max_execution_time = apply_filters( 'bdfgf_max_execution_time', 120 );
		set_time_limit( $max_execution_time );

		/*
		 * Get the form object.
		 */
		$form = GFAPI::get_form( $form_id );

		if ( empty( $form ) ) {
			wp_die( esc_html__( 'Form not found.', 'bulk-download-for-gravity-forms' ) );
		}

		/*
		 * Create a nice filename for the download.
		 */
		$download_filename = $this->get_download_filename( $form, $entry_ids );

		/*
		 * Get the upload fields.
		 */
		$upload_fields = FormFields::get_form_upload_fields( $form_id );

		/*
		 * Get upload files.
		 */
		$uploaded_files = $this->get_uploaded_files( $upload_fields, $entry_ids, $form );

		if ( 0 === count( $uploaded_files ) ) {
			wp_die( esc_html__( 'No files found for the selected entries.', 'bulk-download-for-gravity-forms' ) );
		}

		$zip_filename = '';
		$error        = null;

		try {
			/*
			 * Create the temporary ZIP file. The finally block removes it on normal completion or exceptions.
			 */
			$zip_filename = wp_tempnam( $download_filename . '.zip' );
			if ( ! $zip_filename ) {
				throw new \Exception( __( 'Could not create a temporary ZIP file.', 'bulk-download-for-gravity-forms' ) );
			}

			/*
			 * Create the ZipArchive.
			 */
			$zip         = new ZipArchive();
			$open_result = $zip->open( $zip_filename, ZipArchive::OVERWRITE );

			/*
			 * Check if the zip archive could be created.
			 */
			if ( true !== $open_result ) {
				throw new \Exception(
					sprintf(
					// translators: %s: The error code.
						esc_html__( 'Failed to create ZIP archive. Error code: %s', 'bulk-download-for-gravity-forms' ),
						esc_html( $open_result )
					)
				);
			}

			$this->zip_uploaded_files( $uploaded_files, $zip, $form );

			/**
			 * Do extra action.
			 *
			 * @param ZipArchive $zip The zip archive.
			 * @param array      $uploaded_files All uploaded files .
			 * @param array      $form The current upload directory's path and URL.
			 */
			gf_do_action( [ 'bdfgf_after_uploaded_files', $form['id'] ], $zip, $uploaded_files, $form );

			/*
			 * Files are written when the archive is finalized; open() succeeding is not enough.
			 */
			if ( ! $zip->close() ) {
				throw new \Exception( __( 'Could not finalize the ZIP archive.', 'bulk-download-for-gravity-forms' ) );
			}

			clearstatcache( true, $zip_filename );
			$zip_size = is_file( $zip_filename ) && is_readable( $zip_filename ) ? filesize( $zip_filename ) : false;
			if ( false === $zip_size || 0 === $zip_size ) {
				throw new \Exception( __( 'The ZIP archive is unavailable or empty.', 'bulk-download-for-gravity-forms' ) );
			}

			/*
			 * Send ZIP file.
			 */
			if ( ob_get_level() ) {
				ob_clean();
			}

			if ( headers_sent() ) {
				throw new \Exception( __( 'Headers already sent.', 'bulk-download-for-gravity-forms' ) );
			}

			nocache_headers();
			header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $download_filename . '.zip"' );
			header( 'Content-Length: ' . $zip_size );
			flush();
			readfile( $zip_filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			flush();
		} catch ( \Exception $e ) {
			$error = $e;
		} finally {
			if ( $zip_filename && file_exists( $zip_filename ) ) {
				unlink( $zip_filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			}
		}

		if ( $error ) {
			// translators: %s: The error message.
			wp_die( esc_html( sprintf( __( 'There was an error creating the ZIP file: %s', 'bulk-download-for-gravity-forms' ), $error->getMessage() ) ) );
		}

		die();
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
		/*
		 * Try to overwrite the filename with the title of the form.
		 */
		$form_title = sanitize_title( $form['title'] );
		if ( empty( $form_title ) ) {
			$form_title = _x( 'archive', 'default filename', 'bulk-download-for-gravity-forms' );
		}

		$suffix = 1 === count( $entry_ids ) ? $entry_ids[0] : $form['id'];

		$filename = sprintf(
			'%s-%d',
			$form_title,
			$suffix
		);

		/*
		 * Check if the form has a custom filename definded in the settings.
		 */
		if ( isset( $form['bulkDownloadSettings']['customArchivename'] ) && true === $form['bulkDownloadSettings']['customArchivename'] ) {
			/*
			 * Get the first entry.
			 */

			$first_entry = null;
			foreach ( $entry_ids as $entry_id ) {
				$candidate = GFAPI::get_entry( $entry_id );
				if ( ! is_wp_error( $candidate ) && (int) rgar( $candidate, 'form_id' ) === (int) $form['id'] ) {
					$first_entry = $candidate;
					break;
				}
			}

			if ( null === $first_entry ) {
				wp_die( esc_html__( 'No valid entries found for this form.', 'bulk-download-for-gravity-forms' ), '', [ 'response' => 404 ] );
			}

			/*
			 * Replace all merge tags.
			 */
			$new_archive_name = GFCommon::replace_variables( $form['bulkDownloadSettings']['downloadArchivename'], $form, $first_entry );

			/*
			 * The new archive name could be parsed and does not contain any merge tags, overwrite the filename.
			 */
			if ( ! empty( $new_archive_name ) && false === strpos( $new_archive_name, '{' ) ) {
				$new_archive_name = $this->sanitize_zip_archive_filename( $new_archive_name, '' );

				if ( '' !== $new_archive_name ) {
					$filename = $new_archive_name;
				}
			}
		}

		/**
		 * Filters the file name of the zip archive (without extension).
		 *
		 * @param string $filename The current zip archive file name.
		 * @param array  $form The GF form array.
		 * @param array  $entry_ids The entry IDs of all files being added to the archive.
		 *
		 * @return string
		 */
		$filtered_filename = gf_apply_filters( [ 'bdfgf_download_filename', $form['id'] ], $filename, $form, $entry_ids );

		return $this->sanitize_zip_archive_filename( $filtered_filename, $filename );
	}

	/**
	 * Get uploaded files.
	 *
	 * @param array $upload_fields Array of all uploaded_fields.
	 * @param array $entry_ids     Array of entry IDs.
	 * @param array $form          The GF form array.
	 *
	 * @return array
	 */
	public function get_uploaded_files( $upload_fields, $entry_ids, $form ) {
		$uploaded_files = [];

		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );

			if ( is_wp_error( $entry ) || (int) rgar( $entry, 'form_id' ) !== (int) $form['id'] ) {
				continue;
			}

			$deleted_urls = gform_get_meta( (int) $entry_id, 'bdfgf_deleted_file_urls' );
			$deleted_urls = maybe_unserialize( $deleted_urls );
			$deleted_urls = is_array( $deleted_urls ) ? $deleted_urls : [];
			$deleted_urls = array_values( array_filter( $deleted_urls, 'is_string' ) );
			$deleted_set  = array_fill_keys( $deleted_urls, true );

			$uploaded_files[ $entry_id ] = [];
			foreach ( (array) $upload_fields as $upload_field_id ) {
				$raw = (string) rgar( $entry, (string) $upload_field_id );
				if ( '' === $raw ) {
					continue;
				}

				$decoded = json_decode( $raw, true );
				$urls    = is_array( $decoded ) ? $decoded : [ $raw ];

				foreach ( (array) $urls as $url ) {
					if ( ! is_string( $url ) || '' === trim( $url ) ) {
						continue;
					}

					// ✅ Wenn für dieses Entry als gelöscht markiert: NICHT ins ZIP
					if ( isset( $deleted_set[ $url ] ) ) {
						continue;
					}

					$path = FormFields::get_upload_path_from_url( $url );

					if ( $path && is_readable( $path ) ) {
						$uploaded_files[ (int) $entry_id ][] = $path;
					}
				}
			}

			/**
			 * Filter to add extra files into a single entry.
			 *
			 * @param array $uploaded_files All uploaded files.
			 * @param int   $entry_id The entry ID .
			 * @param array $form The form array.
			 *
			 * @return array
			 */
			$uploaded_files = gf_apply_filters( [ 'bdfgf_single_entry_uploaded_files', $form['id'] ], $uploaded_files, $entry_id, $form );

			if ( empty( $uploaded_files[ (int) $entry_id ] ) ) {
				unset( $uploaded_files[ (int) $entry_id ] );
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
	 *
	 * @throws \RuntimeException If Runtime fails.
	 */
	public function zip_uploaded_files( $uploaded_files, $zip, $form ) {
		foreach ( $uploaded_files as $entry_id => $entry_files ) {
			foreach ( $entry_files as $uploaded_file ) {
				if ( is_readable( $uploaded_file ) ) {
					/*
					 * Define a default entry file name using the entry ID as the folder name.
					 */
					$file_basename  = sanitize_file_name( wp_basename( $uploaded_file ) );
					$entry_filename = $entry_id . '/' . $file_basename;

					/*
					 * Check if the form has a custom filename definded in the settings.
					 */
					if ( isset( $form['bulkDownloadSettings']['customFoldername'] ) && true === $form['bulkDownloadSettings']['customFoldername'] ) {
						/*
						 * Get the entry.
						 */
						$entry = GFAPI::get_entry( $entry_id );

						/*
						 * Replace all merge tags.
						 */
						$new_folder_name = GFCommon::replace_variables( $form['bulkDownloadSettings']['downloadFoldername'], $form, $entry );

						/*
						 * The new archive name could be parsed and does not contain any merge tags, overwrite the filename.
						 */
						if ( ! empty( $new_folder_name ) && false === strpos( $new_folder_name, '{' ) ) {
							$new_folder_name = sanitize_file_name( wp_basename( $new_folder_name ) );

							if ( '' !== $new_folder_name ) {
								$entry_filename = $new_folder_name . '/' . $file_basename;
							}
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
					$entry_filename = $this->sanitize_zip_entry_filename( $entry_filename, $entry_id . '/' . $file_basename );
					if ( ! $zip->addFile( $uploaded_file, $entry_filename ) ) {
						throw new \RuntimeException( esc_html__( 'Could not add an upload to the ZIP archive.', 'bulk-download-for-gravity-forms' ) );
					}
				}
			}
		}

		return $zip;
	}

	/**
	 * Sanitize zip archive filename.
	 *
	 * @param string $filename File name.
	 * @param string $fallback Fallback file name.
	 *
	 * @return string
	 */
	private function sanitize_zip_archive_filename( $filename, $fallback ) {
		$filename = sanitize_file_name( wp_basename( (string) $filename ) );

		return '' !== $filename ? $filename : $fallback;
	}

	/**
	 * Sanitize file path inside zip archive.
	 *
	 * @param string $entry_filename Entry file name.
	 * @param string $fallback       Fallback entry file name.
	 *
	 * @return string
	 */
	private function sanitize_zip_entry_filename( $entry_filename, $fallback ) {
		$parts           = explode( '/', str_replace( '\\', '/', (string) $entry_filename ) );
		$sanitized_parts = [];

		foreach ( $parts as $part ) {
			$part = sanitize_file_name( wp_basename( $part ) );

			if ( '' === $part || '.' === $part || '..' === $part ) {
				continue;
			}

			$sanitized_parts[] = $part;
		}

		return ! empty( $sanitized_parts ) ? implode( '/', $sanitized_parts ) : $fallback;
	}

	/**
	 * Check if the download token is valid.
	 *
	 * @param int $form_id The form ID.
	 * @param int $entry_id The entry ID.
	 *
	 * @return bool
	 */
	private function is_valid_mail_download_token( $form_id, $entry_id ) {
		$expires   = absint( rgget( 'bdfgf_expires' ) );
		$signature = sanitize_text_field( (string) rgget( 'bdfgf_signature' ) );

		if ( 0 === $form_id || 0 === $entry_id || $expires < time() || '' === $signature ) {
			return false;
		}

		$payload  = $form_id . '|' . $entry_id . '|' . $expires;
		$expected = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );

		return hash_equals( $expected, $signature );
	}
}
