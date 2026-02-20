<?php
/**
 * AJAX validator for GF entry bulk actions (download/delete).
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFAPI;
use GFCommon;

/**
 * Class BulkActionValidator
 */
class BulkActionValidator {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'wp_ajax_bdfgf_validate_bulk_action', [ $this, 'ajax_validate_bulk_action' ] );
	}

	/**
	 * AJAX handler: validates whether selected entries contain downloadable/deletable files.
	 *
	 * Expects POST:
	 * - nonce
	 * - form_id
	 * - bulk_action (gf_bulk_download | bdfgf_bulk_delete)
	 * - entry_ids[] (array)
	 */
	public function ajax_validate_bulk_action() {
		/*
		 * Security: Check nonce for AJAX request. We do this manually here to be able to return custom error messages in case of missing/invalid nonce.
		 */
		check_ajax_referer( 'bdfgf_bulk_validate', 'nonce' );

		if ( ! isset( $_POST['nonce'] ) ) { // phpcs:ignore
			wp_send_json_error( [ 'message' => 'missing_nonce' ], 400 );
		}

		if ( ! check_ajax_referer( 'bdfgf_bulk_validate', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'bad_nonce' ], 403 );
		}

		$form_id     = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0; // phpcs:ignore
		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( (string) $_POST['bulk_action'] ) : ''; // phpcs:ignore

		/*
		 * Sanitize entry IDs: ensure it's an array of integers.
		 * We expect entry_ids[] as an array from the AJAX request, but we need to validate and sanitize it.
		 */
		$entry_ids = isset( $_POST['entry_ids'] ) && is_array( $_POST['entry_ids'] ) ? array_map( 'intval', $_POST['entry_ids'] ) : []; // phpcs:ignore

		if ( ! $form_id ) {
			wp_send_json_error( [ 'message' => 'missing_form_id' ], 400 );
		}
		if ( empty( $entry_ids ) ) {
			wp_send_json_error( [ 'message' => 'missing_entry_ids' ], 400 );
		}

		/*
		 * Note: We check permissions for viewing entries for both actions, because both require access to the entry data to validate the files.
		 * The delete action requires an additional permission check for deleting entries.
		 */
		if ( ! GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		if ( 'bdfgf_bulk_delete' === $bulk_action && ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		if ( 'gf_bulk_download' !== $bulk_action && 'bdfgf_bulk_delete' !== $bulk_action ) {
			wp_send_json_error( [ 'message' => 'invalid_action' ], 400 );
		}

		$form = GFAPI::get_form( $form_id );
		if ( empty( $form ) ) {
			wp_send_json_error( [ 'message' => 'form_not_found' ], 404 );
		}

		/*
		 * We need to get the upload field IDs for the form to check the entries for referenced files.
		 * We use the FormFields helper for this, which caches the upload field IDs per form to avoid redundant processing on subsequent requests for the same form.
		 */
		$upload_field_ids = FormFields::get_form_upload_fields( $form_id );
		if ( empty( $upload_field_ids ) ) {
			wp_send_json_success(
				[
					'action'   => $bulk_action,
					'total'    => 0,
					'readable' => 0,
					'missing'  => 0,
				]
			);
		}

		$wp_upload_dir = wp_upload_dir();

		$total_refs = 0; // referenced URLs in selected entries.
		$readable   = 0; // physically readable files (downloadable/deletable).
		$missing    = 0; // referenced but missing.

		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );
			if ( is_wp_error( $entry ) ) {
				continue;
			}

			foreach ( $upload_field_ids as $fid ) {
				$raw = (string) rgar( $entry, (string) $fid );
				if ( '' === $raw ) {
					continue;
				}

				$decoded = json_decode( $raw, true );
				$urls    = is_array( $decoded ) ? $decoded : [ $raw ];

				foreach ( (array) $urls as $u ) {
					if ( ! is_string( $u ) || '' === trim( $u ) ) {
						continue;
					}

					++$total_refs;

					$path = str_replace( $wp_upload_dir['baseurl'], $wp_upload_dir['basedir'], $u );
					if ( is_readable( $path ) ) {
						++$readable;
					} else {
						++$missing;
					}
				}
			}
		}

		/*
		 * Return the validation results: total referenced files, how many are readable (downloadable/deletable), and how many are missing.
		 * The backend can use this information to inform the user before proceeding with the bulk action.
		 */
		wp_send_json_success(
			[
				'action'   => $bulk_action,
				'total'    => $total_refs,
				'readable' => $readable,
				'missing'  => $missing,
			]
		);
	}
}
