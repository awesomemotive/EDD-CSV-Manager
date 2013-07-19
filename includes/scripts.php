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
function edd_csv_importer_setup_upload_dirs() {
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
add_action( 'admin_init', 'edd_csv_importer_setup_upload_dirs' );


/**
 * Register styles
 *
 * @since		1.0.0
 * @return		void
 */
function edd_csv_importer_register_styles() {
	wp_register_style( 'edd-csv-importer-css', EDD_CSV_IMPORTER_URL . 'assets/css/style.css' );
}
add_action( 'admin_init', 'edd_csv_importer_register_styles' );

function edd_csv_importer_enqueue_styles() {
	wp_enqueue_style( 'edd-csv-importer-css' );
}


/**
 * Convert PHP upload_max_filesize values to bytes
 *
 * @since		1.0.0
 * @param		string $size
 * @return		int $kbsize
 */
function return_bytes( $size ) {
	if( empty( $size ) ) return 0;

	$size = trim( $size );

	preg_match( '#([0-9]+)[\s]*([a-z]+)#i', $size, $matches );

	$last = '';

	if( isset( $matches[2] ) )
		$last = $matches[2];

	if( isset( $matches[1] ) )
		$size = (int)$matches[1];

	switch( strtolower( $last ) ) {
		case 'g':
		case 'gb':
			$size *= 1024;
		case 'm':
		case 'mb':
			$size *= 1024;
		case 'k':
		case 'kb':
			$size *= 1024;
	}

	return (int)$size;
}
