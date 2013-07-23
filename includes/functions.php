<?php
/**
 * Functions
 *
 * @package		Easy Digital Downloads - CSV Importer
 * @subpackage	Functions
 * @copyright	Copyright (c) 2013, Daniel J Griffiths
 * @since		1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Check if the specified file is a valid CSV
 *
 * @since		1.0.0
 * @param		$file
 * @return		bool
 */
function edd_is_valid_csv( $file ) {
	// Array of allowed extensions
	$allowed = array( 'csv', 'ods', 'xls', 'xlsx' );

	// Determine the extension for the uploaded file
	$ext = pathinfo( $file, PATHINFO_EXTENSION );

	// Check if $ext is allowed
	if( in_array( $ext, $allowed ) )
		return true;

	return false;
}


/**
 * Check CSV map for duplicates
 *
 * @since		1.0.0
 * @return		bool
 */
function edd_csv_map_has_duplicates( $fields ) {
	$duplicates = false;

	foreach( $fields as $csv => $db ) {
		if( !empty( $db ) ) {
			if( !isset( $value_{$db} ) ) {
				$value_{$db} = true;
			} else {
				$duplicates |= true;
			}
		}
	}

	return $duplicates;
}


/**
 * Handles presetting mapping on submit when errors exist
 *
 * @since		1.0.0
 * @param		string $parent The parent element we are checking
 * @param		string $field_name The value to check against it
 * @return		string $selected
 */
function edd_csv_map_preset_on_error( $parent, $field_name ) {
	$csv_fields = get_transient( 'csv_fields' );
	$csv_fields = unserialize( $csv_fields );

	if( isset( $csv_fields[$parent] ) && $csv_fields[$parent] == $field_name ) {
		$selected = ' selected ';
	} else {
		$selected = '';
	}

	return $selected;
}


/**
 * Handle errors
 *
 * @since		1.0.0
 * @param		int $errno The specific errorcode to handle
 * @param		mixed $data Arbitrary data that may need to be handled
 * @return		void
 */
function edd_csv_error_handler( $errno ) {
	$data = '';

	switch( $errno ) {
		case '1':
			$error = __( 'You cannot assign multiple columns to the same db field!', 'edd-csv-importer' );
			break;
		case '2':
			$error = __( 'You must specify a valid CSV file to import!', 'edd-csv-importer' );
			break;
		case '3':
			$error = __( 'One or more files failed to import!', 'edd-csv-importer' );
			$file_errors = get_transient( 'edd_file_errors' );
			if( $file_errors ) {
				$file_errors = maybe_unserialize( $file_errors );

				foreach( $file_errors as $row => $file ) {
					$data .= sprintf( __( '<br />&middot; The file %s cannot be found on line %s.', 'edd-csv-importer' ), $file['file'], $file['row'] );
				}
			}
			break;

	}

	echo '<div class="error"><p>' . $error . '</p>' . $data . '</div>';
}


/**
 * Cleanup unneeded transients
 *
 * @since		1.0.0
 * @return		void
 */
function edd_csv_cleanup() {
	if( get_transient( 'edd_file_errors' ) )
		delete_transient( 'edd_file_errors' );

	if( get_transient( 'edd_csv_headers' ) )
		delete_transient( 'edd_csv_headers' );

	if( get_transient( 'edd_csv_file' ) )
		delete_transient( 'edd_csv_file' );

	if( get_transient( 'edd_csv_map' ) )
		delete_transient( 'edd_csv_map' );

	if( get_transient( 'has_headers' ) )
		delete_transient( 'has_headers' );
}
