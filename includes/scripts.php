<?php
/**
 * Scripts
 *
 * @package		Easy Digital Downloads - CSV Importer
 * @subpackage	Scripts
 * @copyright	Copyright (c) 2013, Daniel J Griffiths
 * @since		1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Setup upload directories
 *
 * @since		1.0.0
 * @return		void
 */
function edd_csv_importer_setup_upload_dir() {
	$wp_upload_dir = wp_upload_dir();
	$upload_path = $wp_upload_dir['basedir'] . '/edd-csv-importer-uploads';

	// Create our upload directory if it doesn't exist
	if( !is_dir( $upload_path ) )
		wp_mkdir_p( $upload_path );

	// Add an htaccess file to prevent access to our upload directory
	$rules = 'Options -Indexes';
	if( !file_exists( $upload_path . '/.htaccess' ) )
		@file_put_contents( $upload_path . '/.htaccess', $rules );

	// Add a blank index.php file
	if( !file_exists( $upload_path . '/index.php' ) )
		@file_put_contents( $upload_path . '/index.php', '<?php' . PHP_EOL );
}
add_action( 'admin_init', 'edd_csv_importer_setup_upload_dir' );
