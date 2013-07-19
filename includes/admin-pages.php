<?php
/**
 * Admin pages
 *
 * @package		Easy Digital Downloads - CSV Importer
 * @subpackage	Admin Pages
 * @copyright	Copyright (c) 2013, Daniel J Griffiths
 * @license		http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Creates the admin submenu page(s) for the importer
 *
 * @since		1.0.0
 * @global		$edd_csv_importer_page
 * @return		void
 */
function edd_csv_importer_add_options_link() {
	global $edd_csv_importer_page;

	$edd_csv_importer_page = add_management_page( __( 'Easy Digital Downloads CSV Importer', 'edd-csv-importer' ), __( 'EDD CSV Importer', 'edd-csv-importer' ), 'manage_shop_settings', 'edd-csv-importer', 'edd_csv_importer_page' );

	add_action( 'admin_print_styles-' . $edd_csv_importer_page, 'edd_csv_importer_enqueue_styles' );
}
add_action( 'admin_menu', 'edd_csv_importer_add_options_link', 10 );
