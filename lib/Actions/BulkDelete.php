<?php
/**
 * Class to delete all files of an entry.
 *
 * @package BDFGF\Actions
 */

namespace BDFGF\Actions;

use BDFGF\Helpers\FormFields;
use GFAPI;
use GFCommon;
use GFFormsModel;

/**
 * Class BulkDelete
 */
class BulkDelete {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_action( 'admin_post_bdfgf_bulk_delete', [ $this, 'handle_single_entry_delete' ], 1 );
		add_action( 'gform_entry_list_action', [ $this, 'handle_bulk_action_delete' ], 10, 3 );
		add_filter( 'gform_entry_field_value', [ $this, 'filter_entry_fileupload_display' ], 10, 4 );
		add_filter( 'gform_entries_field_value', [ $this, 'mark_deleted_files_in_entry_list' ], 10, 4 );
	}

	/**
	 * Handle single entry file deletion.
	 */
	public function handle_single_entry_delete() {
		$form_id  = (int) rgget( 'gf_form_id' );
		$entry_id = absint( rgget( 'gf_entry_id' ) );

		if ( ! $entry_id || ! $form_id ) {
			wp_die( esc_html__( 'Missing required parameters.', 'bulk-download-for-gravity-forms' ) );
		}

		/*
		 * Check admin referer for security.
		 */
		check_admin_referer( 'bdfgf_bulk_delete_entry_' . $entry_id );

		/*
		 * Permission check.
		 */
		if ( ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete entry files.', 'bulk-download-for-gravity-forms' ) );
		}

		$this->bulk_delete( $form_id, [ $entry_id ] );

		/*
		* Redirect back to the entry list after deletion.
		*/
		wp_safe_redirect( admin_url( 'admin.php?page=gf_entries&id=' . $form_id ) );
		exit;
	}

	/**
	 * Handle bulk action file deletion from multiple entries.
	 *
	 * @param string $action Action being performed.
	 * @param array  $entries The entry IDs the action is being applied to.
	 * @param int    $form_id The current form ID.
	 */
	public function handle_bulk_action_delete( $action, $entries, $form_id ) {
		if ( 'bdfgf_bulk_delete' !== $action ) {
			return;
		}

		$form_id   = (int) $form_id;
		$entry_ids = array_map( 'intval', $entries );

		$this->bulk_delete( $form_id, $entry_ids );
	}

	/**
	 * Bulk delete all files of an entry.
	 *
	 * @param int   $form_id   The current form ID.
	 * @param array $entry_ids Array of entry IDs.
	 *
	 * @throws \Exception If deletion fails.
	 */
	public function bulk_delete( $form_id, $entry_ids ) {
		if ( empty( $form_id ) ) {
			wp_die( esc_html( __( 'The form ID required for bulk deletion is missing.', 'bulk-download-for-gravity-forms' ) ) );
		}

		if ( empty( $entry_ids ) ) {
			wp_die( esc_html( __( 'The entry IDs required for bulk deletion are missing.', 'bulk-download-for-gravity-forms' ) ) );
		}

		/*
		 * Check permissions and if user is car delete entries.
		 */
		$delete_permitted = GFCommon::current_user_can_any( 'gravityforms_delete_entries' );

		/**
		 * Filters the delete permission.
		 *
		 * @param bool        $download_permitted  True if user is logged in and has gravityforms_view_entries permission.
		 * @param int         $form_id The GF form id.
		 * @param array<int>  $entry_ids The entry IDs of which all files will be added to the archive.
		 *
		 * @return bool
		 */
		$delete_permitted = gf_apply_filters( [ 'bdfgf_delete_permission', $form_id ], $delete_permitted, $form_id, $entry_ids );

		/*
		 * Check if userer has no permission.
		 */
		if ( ! $delete_permitted ) {
			wp_die( esc_html( __( 'You do not have permission to bulk delete files for these entries.', 'bulk-download-for-gravity-forms' ) ) );
		}

		/*
		 * Get the form object.
		 */
		$form = GFAPI::get_form( $form_id );

		if ( empty( $form ) ) {
			wp_die( esc_html__( 'Form not found.', 'bulk-download-for-gravity-forms' ) );
		}

		/*
		 * Get the upload fields.
		 */
		$upload_fields = FormFields::get_form_upload_fields( $form_id );

		/*
		 * Get upload files.
		 */
		$uploaded_files = $this->get_uploaded_files_to_delete( $upload_fields, $entry_ids );

		if ( 0 === count( $uploaded_files ) ) {
			wp_die( esc_html__( 'No files found.', 'bulk-download-for-gravity-forms' ) );
		}

		try {
			if ( isset( $form['bulkDownloadSettings']['customDeleteEntryFiles'] ) && true === $form['bulkDownloadSettings']['customDeleteEntryFiles'] ) {
				/*
				 * Loop through all uploaded files and delete them.
				 */
				foreach ( $uploaded_files as $entry_id => $entry_files ) {
					$deleted_urls = (array) gform_get_meta( $entry_id, 'bdfgf_deleted_file_urls' );

					foreach ( $entry_files as $file ) {
						/*
						 * Delete only when the file exists and is readable.
						 * This prevents errors in case the file was already deleted manually or by another process,
						 * and ensures we only mark files as deleted that were actually deleted by us.
						 */
						if ( is_string( $file['path'] ) ) {
							if ( is_readable( $file['path'] ) ) {
								$deleted = wp_delete_file( $file['path'] );

								if ( $deleted && ! empty( $file['url'] ) ) {
									$deleted_urls[] = $file['url'];
								}
							}
						}
					}

					/*
					 * Add a note to the entry about the bulk deletion of files, including the number of deleted files
					 * and the user who performed the deletion, to keep a record of the deletion in the entry details.
					 */
					$user = wp_get_current_user();
					$note = sprintf(
						// translators: %s: The number of deleted files.
						esc_html( _n( 'Bulk deleted %s file.', 'Bulk deleted %s files.', count( $entry_files ), 'bulk-download-for-gravity-forms' ) ),
						count( $entry_files )
					);
					GFAPI::add_note( $entry_id, $user->ID, $user->display_name, $note, $note_type = 'user' );

					$deleted_urls = array_values( array_unique( array_filter( $deleted_urls ) ) );

					/*
					 * Only set flags if we actually deleted something, to avoid false positives (e.g. if files were already deleted manually or by another process).
					 */
					if ( ! empty( $deleted_urls ) ) {
						/*
						 * Set flag to indicate that files were deleted for this entry.
						 * This is used to mark the file URLs as deleted in the entry details view.
						 * We set this flag regardless of whether we actually deleted files or not,
						 * to also cover cases where files were already deleted manually or by another process,
						 * so that we can mark them as deleted in the UI instead of showing broken links.
						 * The list of deleted URLs will be used to only mark those specific URLs as deleted,
						 * in case there are multiple file upload fields and only some of them had deletable files.
						 */
						gform_update_meta( $entry_id, 'bdfgf_files_deleted', true );

						/*
						 * List of deleted file URLs for this entry.
						 * This is used to only mark the deleted files as deleted in the entry details view,
						 * in case there are multiple file upload fields and only some of them had deletable files.
						 * We set this list regardless of whether we actually deleted files or not, to also cover cases where files were already deleted manually or by another process,
						 * so that we can mark them as deleted in the UI instead of showing broken links.
						 */
						gform_update_meta( $entry_id, 'bdfgf_deleted_file_urls', $deleted_urls );
					}
				}
			}
		} catch ( \Exception $e ) {
			// translators: %s: The error message.
			wp_die( esc_html( sprintf( __( 'There was an error while deleting the files: %s', 'bulk-download-for-gravity-forms' ), $e->getMessage() ) ) );
		}
	}

	/**
	 * Get uploaded files.
	 *
	 * @param array $upload_fields Array of all uploaded_fields.
	 * @param array $entry_ids     Array of entry IDs.
	 *
	 * @return array
	 */
	public function get_uploaded_files_to_delete( $upload_fields, $entry_ids ) {
		$uploaded_files = [];

		/*
		 * The current upload directory.
		 */
		$wp_upload_dir = wp_upload_dir();

		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );

			if ( is_wp_error( $entry ) ) {
				continue;
			}

			$uploaded_files[ $entry_id ] = [];

			foreach ( $upload_fields as $upload_field_id ) {
				$field_value = rgar( $entry, (string) $upload_field_id );
				if ( empty( $field_value ) ) {
					continue;
				}

				/*
				 * If the field is a multi file upload, add all files from the JSON object to the array of uploaded files.
				 */
				$field_files = json_decode( $field_value, true );
				if ( ! is_array( $field_files ) ) {
					$field_files = [ $field_value ];
				}

				foreach ( $field_files as $file_url ) {
					if ( empty( $file_url ) || ! is_string( $file_url ) ) {
						continue;
					}

					$file_path = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $file_url );

					$uploaded_files[ $entry_id ][] = [
						'field_id' => (int) $upload_field_id,
						'url'      => $file_url,
						'path'     => $file_path,
					];
				}
			}

			if ( empty( $uploaded_files[ $entry_id ] ) ) {
				unset( $uploaded_files[ $entry_id ] );
			}
		}

		return $uploaded_files;
	}

	/**
	 * Filter the file upload field display in the entry details to mark deleted files.
	 *
	 * @param string $value The field value to be displayed.
	 * @param object $field The field object.
	 * @param array  $entry The entry data.
	 * @param array  $form  The form data.
	 *
	 * @return string The modified field value for display.
	 */
	public function filter_entry_fileupload_display( $value, $field, $entry, $form ) {
		/*
		 * Check if the field is a file upload field.
		 */
		if ( ! is_object( $field ) || 'fileupload' !== $field->type ) {
			return $value;
		}

		$entry_id = (int) rgar( $entry, 'id' );
		if ( ! $entry_id ) {
			return $value;
		}

		/*
		 * Check if any files were deleted for this entry.
		 */
		$flag = gform_get_meta( $entry_id, 'bdfgf_files_deleted' );
		if ( empty( $flag ) ) {
			return $value;
		}

		/*
		 * Get the custom label for deleted files from the form settings, or use the default label if not set.
		 */
		$deleted_label = trim( (string) ( $form['bulkDownloadSettings']['customDeleteEntryFilesMarkerText'] ?? '' ) );
		$deleted_label = '' !== $deleted_label
			? esc_html( $deleted_label )
			: esc_html__( '(Deleted via bulk delete)', 'bulk-download-for-gravity-forms' );

		/*
		 * Mark  only the deleted file URLs.
		 */
		$deleted_urls = gform_get_meta( $entry_id, 'bdfgf_deleted_file_urls' );
		$deleted_urls = maybe_unserialize( $deleted_urls );
		$deleted_urls = is_array( $deleted_urls ) ? $deleted_urls : [];

		/*
		 * We normalize the deleted URLs to paths to be more robust when matching them against the hrefs in the entry details,
		 */
		$deleted_paths = array_values(
			array_filter(
				array_map(
					function ( $u ) {
						$path = wp_parse_url( (string) $u, PHP_URL_PATH );
						return $path ? rtrim( $path, '/' ) : '';
					},
					$deleted_urls
				)
			)
		);

		/*
		 * We check if the value is a string and contains an anchor tag before applying the regex replacement,
		 */
		if ( ! is_string( $value ) || strpos( $value, '<a ' ) === false ) {
			return $value;
		}

		return preg_replace_callback(
			'~(<a\s+[^>]*href=[\'"])([^\'"]+)([\'"][^>]*>)([^<]*)(</a>)~i',
			function ( $match_param ) use ( $deleted_paths, $deleted_label ) {
				$text = $match_param[4];

				/*
				 * If we have a list of deleted paths, we only mark the links as deleted if their href path matches one of the deleted paths.
				 */
				if ( ! empty( $deleted_paths ) ) {
					/*
					 * Gravity Forms can render download proxy links (index.php?gf-download=...).
					 * In that case, matching by href path won't work (it's usually /index.php), so we match by basename.
					 */
					$deleted_basenames = array_map( 'wp_basename', $deleted_paths );
					$current_basename  = wp_basename( html_entity_decode( (string) $text ) );

					if ( ! in_array( $current_basename, $deleted_basenames, true ) ) {
						return $match_param[0];
					}
				}

				/*
				 * We check if the deleted label is already present in the link text before appending it,
				 * to avoid appending it multiple times if the filter is applied multiple times for some reason.
				 * Also we remove the link from the text to so the file is not clickable anymore and create a 404 when clicked,
				 * to prevent confusion for the user about whether the file is actually deleted or not.
				 */
				if ( strpos( $text, ' ' . $deleted_label ) === false ) {
					$text .= ' ' . $deleted_label;
				}

				return '<span class="bdfgf-deleted-file">' . esc_html( $text ) . '</span>';
			},
			$value
		);
	}

	/**
	 * Mark deleted files in entry list.
	 *
	 * @param string $value The field value.
	 * @param int    $form_id The form ID.
	 * @param int    $field_id The field ID.
	 * @param array  $entry The entry data.
	 *
	 * @return string
	 */
	public function mark_deleted_files_in_entry_list( $value, $form_id, $field_id, $entry ) {

		$form  = GFAPI::get_form( (int) $form_id );
		$field = GFFormsModel::get_field( $form, (int) $field_id );

		if ( ! $field || 'fileupload' !== $field->type ) {
			return $value;
		}

		$entry_id = (int) rgar( $entry, 'id' );
		if ( ! $entry_id ) {
			return $value;
		}

		/*
		 * We check if the entry has been marked as having deleted files by looking for the 'bdfgf_files_deleted' meta key.
		 */
		if ( empty( gform_get_meta( $entry_id, 'bdfgf_files_deleted' ) ) ) {
			return $value;
		}

		/*
		 * Field value can be either a single URL string or a JSON-encoded array of URLs, depending on whether the field is a single file upload or a multi-file upload.
		 */
		$raw = (string) rgar( $entry, (string) $field_id );
		if ( '' === $raw ) {
			return $value;
		}

		$field_urls = json_decode( $raw, true );
		$field_urls = is_array( $field_urls ) ? $field_urls : [ $raw ];
		$field_urls = array_values( array_filter( $field_urls, 'is_string' ) );

		if ( empty( $field_urls ) ) {
			return $value;
		}

		/*
		 * Count deleted URLs.
		 */
		$total_count = count( $field_urls );

		/*
		 * We store the deleted file URLs in entry meta with the key 'bdfgf_deleted_file_urls' as a serialized array.
		 * This allows us to keep track of which specific files were deleted from the entry, even if the entry itself still exists.
		 * When we want to check if a file in a file upload field has been deleted, we can compare the URLs in the field with the URLs stored in this meta.
		 * If there's a match, we know that the file was deleted and can mark it accordingly in the entry list.
		 */
		$deleted_urls = gform_get_meta( $entry_id, 'bdfgf_deleted_file_urls' );
		$deleted_urls = maybe_unserialize( $deleted_urls );
		$deleted_urls = is_array( $deleted_urls ) ? $deleted_urls : [];
		$deleted_urls = array_values( array_filter( $deleted_urls, 'is_string' ) );

		if ( empty( $deleted_urls ) ) {
			return $value;
		}

		$deleted_in_field = array_intersect( $field_urls, $deleted_urls );
		$deleted_count    = count( $deleted_in_field );

		if ( $deleted_count <= 0 ) {
			return $value;
		}

		$existing_count = max( 0, $total_count - $deleted_count );

		$deleted_label = trim( (string) ( $form['bulkDownloadSettings']['customDeleteEntryFilesMarkerText'] ?? '' ) );
		$deleted_label = '' !== $deleted_label
			? $deleted_label
			: __( 'Deleted via bulk delete', 'bulk-download-for-gravity-forms' );

		/*
		 * We show the number of available files and the number of deleted files in brackets, to give a clear overview of how many files were deleted and how many are still available.
		 * If all files were deleted, it will show "0 available (X Deleted via bulk delete)", if some files are still available, it will show "Y available (X Deleted via bulk delete)".
		 * This way the user can immediately see the status of the files in the entry list without having to open the entry details.
		 * We use the singular and plural forms of "available" and "Deleted via bulk delete" based on the counts, to ensure proper grammar in the displayed text.
		 * If a custom label for deleted files is set in the form settings, we use that label instead of the default "Deleted via bulk delete".
		 * We also make sure to escape the text properly for safe output in HTML.
		 */
		$files_text = sprintf(
			// translators: %d: The number of available files.
			_n( '%d available', '%d available', $existing_count, 'bulk-download-for-gravity-forms' ),
			$existing_count
		);

		$deleted_text = sprintf(
			// translators: 1: The number of deleted files, 2: The label for deleted files (e.g. "Deleted via bulk delete").
			_n( '%1$d %2$s', '%1$d %2$s', $deleted_count, 'bulk-download-for-gravity-forms' ),
			$deleted_count,
			$deleted_label
		);

		$new_text = $files_text . ' (' . $deleted_text . ')';

		/*
		 * Replace Links with the deleted label in the entry list if they are deleted, to give a clear indication of which files were deleted directly in the entry list.
		 */
		if ( is_string( $value ) && strpos( $value, '<a ' ) !== false ) {
			$value2 = preg_replace_callback(
				'~(<a\s+[^>]*>)(.*?)(</a>)~is',
				function () use ( $deleted_label ) {
					return '<span class="bdfgf-deleted-file">' . esc_html( $deleted_label ) . '</span>';
				},
				(string) $value,
				1,
				$replaced
			);

			if ( ! empty( $replaced ) ) {
				return $value2;
			}
		}

		return ' <span class="bdfgf-deleted-file">' . esc_html( $new_text ) . '</span>';
	}
}
