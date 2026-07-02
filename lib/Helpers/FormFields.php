<?php
/**
 * Helper class to get all upload fields for a form.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

use GFAPI;

/**
 * Class FormFields
 */
class FormFields {

	/**
	 * Array caching the already queried form definitions.
	 *
	 * @var array
	 */
	public static $forms = [];

	/**
	 * Get all upload fields for a form
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return false|array
	 */
	public static function get_form_upload_fields( $form_id ) {
		if ( array_key_exists( $form_id, self::$forms ) ) {
			return self::$forms[ $form_id ]['upload_fields'];
		}

		/*
		 * Get the form.
		 */
		$form = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return false;
		}

		self::$forms[ $form_id ]['upload_fields'] = [];

		/*
		 * Find all single or multi file upload fields.
		 */
		foreach ( $form['fields'] as $field ) {
			if ( 'fileupload' === $field['type'] ) {
				self::$forms[ $form_id ]['upload_fields'][] = $field['id'];
			}
		}

		return self::$forms[ $form_id ]['upload_fields'];
	}

	/**
	 * Check if a given form has any upload fields (single or multi).
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	public static function has_upload_fields( $form_id ) {
		return ! empty( self::get_form_upload_fields( $form_id ) );
	}

	/**
	 * Resolve an upload URL to a local path.
	 *
	 * @param string $url Upload URL.
	 *
	 * @return false|string
	 */
	public static function get_upload_path_from_url( $url ) {
		$wp_upload_dir = wp_upload_dir();
		$base_url      = wp_parse_url( $wp_upload_dir['baseurl'] );
		$url_parts     = wp_parse_url( (string) $url );

		if (
			empty( $base_url['scheme'] ) ||
			empty( $base_url['host'] ) ||
			empty( $base_url['path'] ) ||
			empty( $url_parts['scheme'] ) ||
			empty( $url_parts['host'] ) ||
			empty( $url_parts['path'] )
		) {
			return false;
		}

		if (
			strtolower( $base_url['scheme'] ) !== strtolower( $url_parts['scheme'] ) ||
			strtolower( $base_url['host'] ) !== strtolower( $url_parts['host'] )
		) {
			return false;
		}

		$base_url_path = trailingslashit( wp_normalize_path( $base_url['path'] ) );
		$url_path      = wp_normalize_path( $url_parts['path'] );

		if ( 0 !== strpos( trailingslashit( $url_path ), $base_url_path ) ) {
			return false;
		}

		$relative_path = ltrim( substr( $url_path, strlen( untrailingslashit( $base_url_path ) ) ), '/' );
		$path          = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) . $relative_path );
		$base_dir      = wp_normalize_path( trailingslashit( $wp_upload_dir['basedir'] ) );

		return 0 === strpos( $path, $base_dir ) ? $path : false;
	}

	/**
	 * Check if an entry has at least one uploaded file in one of it's upload fields.
	 *
	 * @param array|int $entry The entry array or the entry ID.
	 *
	 * @return bool
	 */
	public static function has_uploaded_files( $entry ) {
		if ( is_int( $entry ) ) {
			$entry = GFAPI::get_entry( $entry );
		}

		if ( empty( $entry['form_id'] ) ) {
			return false;
		}

		/*
		 * Get all upload fields for the form.
		 */
		$form_upload_fields = self::get_form_upload_fields( $entry['form_id'] );

		$has_fields = false;
		foreach ( $form_upload_fields as $form_upload_field ) {
			if ( ! empty( $entry[ $form_upload_field ] ) ) {
				if ( '[]' !== ( $entry[ $form_upload_field ] ) ) {
					$has_fields = true;
				}
			}
		}

		if ( true === $has_fields ) {
			return true;
		}

		return false;
	}
}
