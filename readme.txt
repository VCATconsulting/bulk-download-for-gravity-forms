=== Bulk Download for Gravity Forms ===

Contributors: VCATconsulting, Kau-Boy, shogathu, nida78, naapwe
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Bulk download all files from one or multiple Gravity Forms entries in one go.

== Description ==

This plugin is an add-on to the [Gravity Forms](https://www.gravityforms.com/ "visit Gravity Forms website") form builder plugin.
It offers the opportunity to download all files from one or multiple Gravity Forms entries with one click.

Therefore, it adds a download link to the list view, and an extra download button to the single view of a Gravity Form entry and a Bulk Action.
All uploaded files are collected and downloadable in a single ZIP file.

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
5. The form specific settings page to overwrite file and folder names

== Frequently Asked Questions ==

= Can I change the file name of the ZIP archive?

You can use the settings page from this option to overwrite the zip archive name. In this option you can also use merge tags from your form.

The plugin also offers a filter called `bdfgf_download_filename` which you can use to change the zip archive name.

You can find an example usage of this filter in [a small plugin in a GIST](https://gist.github.com/vcat-support/d0b817a4270c302d6325d76b0b67d017).

= Can I change the file or folder name of the entries in the ZIP archive?

You can use the settings page from this option to overwrite the folder name. In this option you can also use merge tags from your form.

The plugin also offers a filter called `bdfgf_entry_filename` which you can use to change the names.

You can find an example usage of this filter in [a small plugin in a GIST](https://gist.github.com/vcat-support/b1716d96e131535917b2be368a8fd935).

= When I try to bulk download the files, nothing happens. What can I do?

Issues like these usually occur when your server has too low values for the `memory_limit` or `max_execution_time`.

The plugin provides the filters `bdfgf_memory_limit` and `bdfgf_max_execution_time` to change these values.

You can find example usage of the [memory_limit](https://gist.github.com/vcat-support/f3b52c6f248e6a2b9301adfa845f206f) filter and the [max_execution_time](https://gist.github.com/vcat-support/09d72df61d084ab3250d491408c1e824) filter in the two linked GISTs.

= Can I influence the permissions to download files in bulk?

By default only logged in users with the `gravityforms_view_entries` capability are allowed to download files in bulk. You can use the `bdfgf_download_permission` filter to expand permission check.

= Can I add extra files on the server which were not uploaded to the zip archive?

The Plugin provides a filter `bdfgf_single_entry_uploaded_files` and an action `bdfgf_after_uploaded_files` to do this. You can add extra files to every single entry or to the whole zip archive beside the entries

== Changelog ==

= 3.2.0 =

* Adding an extra filter `bdfgf_single_entry_uploaded_files` to include extra files to a single or every subfolder inside the zip archive.
* Adding an extra action `bdfgf_after_uploaded_files` to add one or more files into the zip archive after the folder passthrough the merge tags.
* Update some filter to the gf_apply_filter function.
* Update to min PHP Version 7.4

= 3.1.1 =

* Correct some wording

= 3.1.0 =

* Adding 2 new setting fields for the form, which now can customize error messages.
* Fixed errors that could be caused by an incorrectly send header for the zip archive. This sometimes meant that the zip file could not be opened.
* Adding filter for download permission to allow more fine grained permission management for other plugins.
* General improvements
* Fix error message not being triggered when entry id for single entry download is invalid.
* Show error when form not found.
* Do not create an invalid zip file when no files are found.
* Skip entries which could not be retrieved.

= 3.0.0 =

* Introducing a settings page per form.
* Adding a setting to overwrite the zip archive file name.
* Adding a setting to overwrite the entry folder names in the zip archive.

= 2.5.0 =

* Use the `gf_apply_filters()` functions to allow filtering of values based on a form ID

= 2.4.1 =

* Restore the vendor folder in the build made by Github actions

= 2.4.0 =

* Replace nonce check with a capability check to allow downloads using the mail links in multiple notifications.

= 2.3.0 =

* Increase memory_limit to 512M and add filter `bdfgf_memory_limit` to allow changes to the value.
* Increase max_execution_time to 120 and add filter `bdfgf_max_execution_time` to allow changes to the value.
* Add filter `bdfgf_download_filename` to allow changes to the zip archive file name.
* Add filter `bdfgf_entry_filename` to allow changes to the entry folder names added to the zip archive.

= 2.2.0 =

* Adding a check if the ZIP extension is installed.
* Use shorter labels for download buttons.

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
