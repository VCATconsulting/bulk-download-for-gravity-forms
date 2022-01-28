=== Bulk Download for Gravity Forms ===

Contributors: VCATconsulting, Kau-Boy, shogathu, nida78
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 2.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Bulk download all files from a [Gravity Forms](https://www.gravityforms.com/ "visit Gravity Forms website") entry in one go.

== Description ==

This plugin is an add-on to the [Gravity Forms](https://www.gravityforms.com/ "visit Gravity Forms website") form builder plugin. It offers the opportunity to download all files from a single Gravity Forms entry with one click.
Therefore, it adds a download link to the list view, and an extra download button to the single view of a Gravity Form entry. All files that are uploaded to the entry are collected and downloadable in a single ZIP file.

== Installation ==

1. Install and configure Gravity Forms plugin,
2. Find this Bulk Download plugin in the "Add Plugins" page within your WordPress installation or Upload the Bulk Download plugin to your blog,
3. Activate it,
4. Find the Bulk Download link in list and single view!

== Screenshots ==

1. Find the Bulk Download link by hovering a Gravity Forms entry in the list view
2. There is also a Bulk Action to download all files from multiple entries
3. An extra button is added by the plugin at the right sidebar in the detail view of an entry
4. A download link can be added to notifications using a merge tag

== Changelog ==

= 2.1.0 =

* Adding support for the "Select all X entries" link for the bulk action.
* Fixing an issue where zip file was missing some uploaded files.

= 2.0.0 =

* Add a bulk action to allow bulk downloads for all files from multiple entries.

= 1.2.0 =

* Add custom Gravity Forms merge tag {bulk_download_link} to display a download link in notification mail.
* Also add a "link_text" attribute to the Gravity Form merge tag {bulk_download_link:link_text="your link text"} to change the default link text.

= 1.1.0 =

* Prevent issues when files with empty paths are added to the ZIP file
* Use the sanitized form title for the download file name

= 1.0.1 =

* Remove function to load translation files from the plugin directory

= 1.0.0 =

* First stable version
