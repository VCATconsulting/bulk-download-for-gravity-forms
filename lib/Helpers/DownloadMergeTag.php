<?php
/**
 * Class to add row action link to GF entries list table.
 *
 * @package BDFGF\Helpers
 */

namespace BDFGF\Helpers;

/**
 * Class DownloadMergeTag
 */
class DownloadMergeTag {

	/**
	 * Initialize the class
	 */
	public function init() {
		add_filter( 'gform_custom_merge_tags', [ $this, 'bulk_downloader_merge_tags' ], 10, 4 );
		add_filter( 'gform_replace_merge_tags', [ $this, 'replace_bulk_download_link' ], 10, 7 );
	}

	/**
	 * Create the merge tag.
	 *
	 * @param string $merge_tags The merge tag.
	 * @param int    $form_id The form ID.
	 * @param array  $fields The fields array.
	 * @param int    $element_id The element ID.
	 *
	 * @return string
	 */
	public function bulk_downloader_merge_tags( $merge_tags, $form_id, $fields, $element_id ) {
		$merge_tags[] = [
			'label' => esc_html__( 'Bulk Download Link', 'bulk-download-for-gravity-forms' ),
			'tag'   => '{bulk_download_link}',
		];

		return $merge_tags;
	}

	/**
	 * Create the merge tag.
	 *
	 * @param string $text The text in which merge tags are being processed.
	 * @param array  $form The Form object if available or false.
	 * @param array  $entry The Entry object if available or false.
	 * @param bool   $url_encode Indicates if the urlencode function should be applied.
	 * @param bool   $esc_html Indicates if the esc_html function should be applied.
	 * @param bool   $nl2br Indicates if the nl2br function should be applied.
	 * @param string $format The format requested for the location the merge is being used. Possible values: html, text or url.
	 *
	 * @return string
	 */
	public function replace_bulk_download_link( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		if ( ! FormFields::has_uploaded_files( $entry ) ) {
			return $text;
		}
		preg_match_all( '/\{bulk_download_link(:(.*?))?\}/', $text, $matches, PREG_SET_ORDER );

		if ( is_array( $matches ) ) {
			$default_link_text = esc_html__( 'Download all files for this entry', 'bulk-download-for-gravity-forms' );
			foreach ( $matches as $match ) {
				$options_string = isset( $match[2] ) ? $match[2] : '';
				$options        = shortcode_parse_atts( $options_string );

				$link_text = isset( $options['link_text'] ) ? $options['link_text'] : $default_link_text;

				$text = str_replace(
					$match[0],
					$this->render_bulk_download_link( $entry, $link_text ),
					$text
				);
			}
		}

		return $text;
	}

	/**
	 * Create download link.
	 *
	 * @param array  $entry The entry data.
	 * @param string $link_text The text of the download link.
	 *
	 * @retun string;
	 */
	public function render_bulk_download_link( $entry, $link_text ) {
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						[
							'page'        => 'gf_entries',
							'action'      => 'gf_bulk_download',
							'gf_entry_id' => esc_attr( $entry['id'] ),
							'gf_form_id'  => esc_attr( $entry['form_id'] ),
						],
						admin_url( 'admin.php' )
					),
					'gf_bulk_download'
				)
			),
			esc_html( $link_text )
		);
	}
}
