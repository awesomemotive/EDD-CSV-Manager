<?php
/**
 * Functions
 *
 * @since		1.0.0
 * @package		EDD CSV Manager
 * @subpackage	Functions
 * @copyright	Copyright (c) 2013, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


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
	$class = 'error';

	switch( $errno ) {
		case '0':
			$class = 'updated';
			$error = __( 'Import completed successfully!', 'edd-csv-manager' );
			break;
		case '1':
			$error = __( 'You cannot assign multiple columns to the same db field!', 'edd-csv-manager' );
			break;
		case '2':
			$error = __( 'You must specify a valid CSV file to import!', 'edd-csv-manager' );
			break;
		case '3':
			$error = __( 'One or more files failed to import!', 'edd-csv-manager' );
			$file_errors = get_transient( 'edd_file_errors' );
			if( $file_errors ) {
				$file_errors = maybe_unserialize( $file_errors );

				foreach( $file_errors as $row => $file ) {
					$data .= sprintf( __( '<br />&middot; The file %s cannot be found on line %s.', 'edd-csv-manager' ), $file['file'], $file['row'] );
				}
			}
			delete_transient( 'edd_file_errors' );
			break;
		case '4':
			$error = __( 'Error adding image attachment!', 'edd-csv-manager' );
			$image_errors = get_transient( 'edd_image_errors' );
			if( $image_errors ) {
				$image_errors = maybe_unserialize( $image_errors );

				foreach( $image_errors as $file ) {
					$data .= sprintf( __( '<br />&middot; The image %s could not be attached!', 'edd-csv-manager' ), $file['file'] );
				}
			}
			delete_transient( 'edd_image_errors' );
			break;


	}

	echo '<div class="' . $class . '"><p>' . $error . '</p>' . $data . '</div>';
}
