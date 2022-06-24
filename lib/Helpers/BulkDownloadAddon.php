<?php

class BulkDownloadAddon extends GFAddOn {
	/**
	 * Contains an instance of this class, if available.
	 *
	 * @var object $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the WP-CLI add-on.
	 *
	 * @var string $_version Contains the version, defined from cli.php
	 */
	protected $_version = BDFGF_VERSION;
	/**
	 * Defines the minimum Gravity Forms version required.
	 * @var string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.4';
	/**
	 * Defines the plugin slug.
	 *
	 * @var string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'bulk-download-for-gravity-forms';
	/**
	 * Defines the main plugin file.
	 *
	 * @var string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'bulk-download-for-gravity-forms/bulk-download-for-gravity-forms.php';
	/**
	 * Defines the full path to this class file.
	 *
	 * @var string $_full_path The full path.
	 */
	protected $_full_path = BDFGF_FILE;
	/**
	 * Defines the URL where this add-on can be found.
	 *
	 * @var string
	 */
	protected $_url = 'https://github.com/VCATconsulting/bulk-download-for-gravity-forms';
	/**
	 * Defines the title of this add-on.
	 *
	 * @var string $_title The title of the add-on.
	 */
	protected $_title = 'Bulk Download for Gravity Forms';
	/**
	 * Defines the short title of the add-on.
	 *
	 * @var string $_short_title The short title.
	 */
	protected $_short_title = 'BulkDownload';

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __clone() {
	} /* do nothing */

}



