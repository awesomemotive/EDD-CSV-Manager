=== Easy Digital Downloads - CSV Manager ===
Contributors: section214, mordauk, chriscct7
Donate link:
Tags: easy digital downloads, edd, csv, importer, exporter
Requires at least: 3.6
Tested up to: 4.4
Stable tag: 1.2.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow EDD site owners to import products from or export products to a CSV file.

== Description ==

Easy Digital Downloads CSV Manager is a simple, free extension for EDD which allows site owners to bulk import products from or export products to a CSV.

This extension also allows you to import past purchase records into Easy Digital Downloads.

Requires [Easy Digital Downloads](http://wordpress.org/extend/plugins/easy-digital-downloads/) v1.7 or later.

== Installation ==

1. Upload `edd-csv-manager` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the `Tools->Import/Export EDD` page.
4. Follow the onscreen instructions!

== Frequently asked questions ==

1. Where do I upload the downloadable files to import?

Upload them via FTP to `wp-content/uploads/edd/`

The name of the files must match the filename you have specified in the CSV exactly.

2. Can I import files from Amazon S3 or other external server?

If you are using the [Amazon S3 extension](https://easydigitaldownloads.com/extensions/amazon-s3/):

1. Upload the files to your Amazon S3 bucket. It must be the same bucket as you have specified in your Amazon S3 settings in Downloads > Settings > Misc.
2. Enter the file path in your CSV column like this: `folder-name/file.mp3`. Do not include the bucket name in the path, only the name of any folders and the file itself.

If you are NOT using the Amazon S3 extension:

1. Upload the files to your Amazon S3 bucket.
2. Set all files to be publicly accessible.
3. Place the complete file URLs into the CSV.


== Screenshots ==


== Changelog ==

= Version 1.2.8 =
* Fixed incorrect file name/path when featured images are not located in the current month's upload folder

= Version 1.2.7 =
* Fixed incorrect file name/path when file is moved from wp-content to wp-content/uploads/edd/

= Version 1.2.6 =
* Fixed an issue with import files that include uploads/{year}/{month} in the file names

= Version 1.2.5 =
* Fixed bug with currency code not importing properly

= Version 1.2.4 =
* Fixed bug with non-USD currency imports

= Version 1.2.3 =
* Fixed bug with non-USD currency imports

= Version 1.2.1 =
* Fixed featured image import
* Better error messages

= Version 1.2.0 =
* Added payment history exporter
* Added sample CSV files

= Version 1.1.10 =
* Added product notes support

= Version 1.1.9 =
* Added check for email address as username on import

= Version 1.1.8 =
* Fixed issue with double slashes in featured images

= Version 1.1.7 =
* Fixed missing item details for purchase import

= Version 1.1.6 =
* Rewrote featured image import system... should work properly now!

= Version 1.1.5 =
* Fixed a bug that caused purchase history import to fail if the products column was the first column in the CSV

= Version 1.1.4 =
* Fix bug with created users not passing ID/email properly
* Add better handling for importing to multisite installs
* Add support for custom file names
* Allow filtering export filenames

= Version 1.1.3 =
* Fix typo in version compare

= Version 1.1.2 =
* Fix resending receipts option

= Version 1.1.1 =
* Fix bug with metabox hooks

= Version 1.1.0 =
* Add support for payment importing
* Minor tweaks to codebase

= Version 1.0.8 =
* Add support for importing/exporting product SKUs

= Version 1.0.7 =
* Fixed an incorrect URL for the product import action
* Fixed undefined constant warning

= Version 1.0.6 =
* Add support for EDD 1.8 Tools menu and maintain backwards compatibility
* Add support for importing files with the Amazon S3 extension

= Version 1.0.5 =
* Revert 1.0.4 - EDD 1.8 isn't out yet!

= Version 1.0.4 =
* Moved to new EDD Tools menu

= Version 1.0.3 =
* Fixed another bug with importing download files

= Version 1.0.2 =
* Minor fix to import/export handler

= Version 1.0.1 =
* Fixed import/export bug