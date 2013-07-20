<?php
/**
 * Metabox
 *
 * @package		Easy Digital Downloads - CSV Importer
 * @subpackage	Metabox
 * @copyright	Copyright (c) 2013, Daniel J Griffiths
 * @since		1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Importer metabox
 *
 * @since		1.0.0
 * @return		void
 */
function edd_csv_importer_metabox() {

	$wp_upload_dir = wp_upload_dir();

	ob_start();
	?>
	<div class="postbox">
		<h3><span><?php _e( 'Import Products from CSV', 'edd-csv-importer' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Import products to your Easy Digital Downloads site from a .csv file.', 'edd-csv-importer' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'tools.php?page=edd-settings-export-import' ); ?>">
			<?php
				if( isset( $_GET['errno'] ) ) {
					edd_csv_error_handler( $_GET['errno'] );
				}
				if( empty( $_GET['step'] ) || $_GET['step'] == 1 ) {
					edd_csv_cleanup();

					echo '<p><input type="file" name="import_file"/></p>';
					echo '<p><label for="has_headers"><input type="checkbox" name="has_headers" checked="yes" /> ' . __( 'Does the CSV include a header row?', 'edd-csv-importer' ) . '</label></p>';
					echo '<p>';
					echo '<input type="hidden" name="edd_action" value="upload_csv" />';
					wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' );
					submit_button( __( 'Next', 'edd' ), 'secondary', 'submit', false );
					echo '</p>';
				} elseif( $_GET['step'] == 2 ) {
					$fields = get_transient( 'edd_csv_headers' );

					foreach( $fields as $id => $field ) {
						if( get_transient( 'has_headers' ) ) {
							$i = $id + 1;
							$field_label = 'column_' . $i;
							$field_id = $id;
						} else {
							$field_label = $field;
							$field_id = $field;
						}

						echo '<div style="width: 200px; display: inline-block;">' . $field_label . '</div>';
						echo '<select name="csv_fields[' . $field_id . ']" >' . edd_csv_get_fields( $field_id ) . '</select><br/>';
					}

					echo '<p><input type="hidden" name="edd_action" value="map_csv" />';
					wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' );
					submit_button( __( 'Next', 'edd' ), 'secondary', 'submit', false );
					echo '</p>';
				} elseif( $_GET['step'] == 3 ) {
					edd_csv_process_rows();
				}
			?>
			</form>
		</div>
	</div>
	<?php
}
add_action( 'edd_export_import_top', 'edd_csv_importer_metabox' );


/**
 * Process import from a CSV file
 *
 * @since		1.0.0
 * @return		void
 */
function edd_process_csv_import() {

	if( empty( $_POST['edd_import_nonce'] ) ) return;

	if( !wp_verify_nonce( $_POST['edd_import_nonce'], 'edd_import_nonce' ) ) return;

	if( !current_user_can( 'manage_shop_settings' ) ) return;

	$import_file = $_FILES['import_file']['tmp_name'];
	if( empty( $import_file ) || !edd_is_valid_csv( $_FILES['import_file']['name'] ) ) {
		wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '2' ) ) );
		exit;
	}

	ini_set( 'auto_detect_line_endings', true );

	$file = fopen( $import_file, 'r' );
	if( ( $line = fgetcsv( $file ) ) !== false ) {
		$num = count( $line );

		$start = 0;
		if( !$_POST['has_headers'] ) {
			$start = 1;
			set_transient( 'has_headers', '1' );
		}

		for( $c = $start; $c < $num; $c++ ) {
			$fields[] = $line[$c];
		}
	}
	fclose( $file );

	ini_set( 'auto_detect_line_endings', false );

	$desination = trailingslashit( WP_CONTENT_DIR ) . basename( $import_file );

	move_uploaded_file( $import_file, $desination );

	set_transient( 'edd_csv_headers', $fields );
	set_transient( 'edd_csv_file', basename( $import_file ) );

	wp_redirect( add_query_arg( 'step', '2' ) ); exit;

}
add_action( 'edd_upload_csv', 'edd_process_csv_import' );

function edd_csv_process_rows() {

	$defaults = array(
		'post_name'    => '',
		'post_author'  => '',
		'post_title'   => '',
		'post_content' => '',
		'post_excerpt' => '',
		'post_status'  => '',
		'post_type'    => 'download'
	);

	$csv_fields = maybe_unserialize( get_transient( 'csv_fields' ) );

	$csv_fields = wp_parse_args( $csv_fields, $defaults );

	$headers    = get_transient( 'edd_csv_headers' );

	echo '<pre>'; print_r( $csv_fields ); echo '</pre>';
	exit;
	ini_set( 'auto_detect_line_endings', true );

	$filename = get_transient( 'edd_csv_file' );
	$file     = trailingslashit( WP_CONTENT_DIR ) . $filename;
	if ( ( $handle = fopen( $file, 'r' ) ) !== FALSE ) {
		$i = 0;
		while ( ( $row = fgetcsv( $handle, 1000 ) ) !== FALSE ) {

			// Get the column keys
			if( $i < 1 ) {
				$post_name_key    = array_search( $csv_fields['post_name'],    $row );
				$post_author_key  = array_search( $csv_fields['post_author'],  $row );
				$post_title_key   = array_search( $csv_fields['post_title'],   $row );
				$post_content_key = array_search( $csv_fields['post_content'], $row );
				$post_excerpt_key = array_search( $csv_fields['post_excerpt'], $row );
				$post_status_key  = array_search( $csv_fields['post_status'],  $row );
				$post_date_key    = array_search( $csv_fields['post_date'],    $row );
			}
 			if( ! $headers && $i <= 1 ) {

 				$i++;
 				continue;

 			}

 			if( ( $headers && $i > 0 | || ! $headers ) {

				$post_data = array(
					'post_name'    => $row[ $post_name_key ],
					'post_author'  => $row[ $post_author_key ],
					'post_title'   => $row[ $post_title_key ],
					'post_content' => $row[ $post_content_key ],
					'post_excerpt' => $row[ $post_name_key ],
					'post_status'  => $row[ $post_status_key ],
					'post_date'    => date( 'Y-m-d H:i:s', strtotime( $row[ $post_date_key ] ) ),
					'post_type'    => 'download'
				);

				$post_id = wp_insert_post( $post_data );

				// Make sure it was created
				if( $post_id ) {
					//update_post_meta( $post_id, '_edd_download_files', $files );
				}

			}

			$i++;
		}
		fclose( $handle );
		exit;
	}

	ini_set( 'auto_detect_line_endings', false );

}


/**
 * Map CSV file
 *
 * @since		1.0.0
 * @return		void
 */
function edd_map_csv_import() {

	if( empty( $_POST['edd_import_nonce'] ) ) return;

	if( !wp_verify_nonce( $_POST['edd_import_nonce'], 'edd_import_nonce' ) ) return;

	if( !current_user_can( 'manage_shop_settings' ) ) return;

	$fields = array_flip( $_POST['csv_fields'] );


	if( edd_csv_map_has_duplicates( $_POST['csv_fields'] ) ) {
		wp_redirect( add_query_arg( array( 'step' => '2', 'errno' => '1' ) ) );
		exit;
	}

	set_transient( 'csv_fields', serialize( $fields ) );

	wp_redirect( add_query_arg( 'step', '3' ) ); exit;
}
add_action( 'edd_map_csv', 'edd_map_csv_import' );


/**
 * Get dropdown list of available fields
 *
 * @since		1.0.0
 * @param		string $parent The name of the select element
 * @return		string
 */
function edd_csv_get_fields( $parent ) {
	$fields = array(
		''							=> 'Unmapped',
		'post_author'				=> 'Author ID',
		'_edd_button_behavior'		=> 'Button Behavior',
		'_edd_categories'			=> 'Categories',
		'post_date'					=> 'Date Created',
		'post_content'				=> 'Description',
		'_edd_files'				=> 'Download Files',
		'_edd_download_limit'		=> 'Download Limit',
		'post_title'				=> 'Download Name',
		'post_excerpt'				=> 'Excerpt',
		'_edd_hide_purchase_link'	=> 'Hide Purchase Link',
		'_edd_images'				=> 'Image Files',
		'post_name'					=> 'Post Name',
		'edd_price'					=> 'Price',
		'post_status'				=> 'Status',
		'_edd_tags'					=> 'Tags'
	);

	$return = '';

	$fields = apply_filters( 'edd_csv_fields', $fields );

	foreach( $fields as $field_name => $field_title ) {
		$return .= '<option value="' . $field_name . '"' . edd_csv_map_preset( $parent, $field_name ) . '>' . $field_title . '</option>';
	}

	return $return;
}


/**
 * Check if the specified file is a valid CSV
 *
 * @since		1.0.0
 * @param		$file
 * @return		bool
 */
function edd_is_valid_csv( $file ) {
	$allowed = array( 'csv', 'ods', 'xls', 'xlsx' );

	$ext = pathinfo( $file, PATHINFO_EXTENSION );

	echo $ext;
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
function edd_csv_map_preset( $parent, $field_name ) {
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
 * @param		int $errno
 * @return		void
 */
function edd_csv_error_handler( $errno ) {

	switch( $errno ) {
		case '1':
			$error = __( 'You cannot assign multiple columns to the same db field!', 'edd-csv-importer' );
			break;
		case '2':
			$error = __( 'You must specify a valid CSV file to import!', 'edd-csv-importer' );
			break;
	}

	echo '<div class="error"><p>' . $error . '</p></div>';
}


/**
 * Cleanup unneeded transients
 *
 * @since		1.0.0
 * @return		void
 */
function edd_csv_cleanup() {
	if( get_transient( 'edd_csv_headers' ) )
		delete_transient( 'edd_csv_headers' );

	if( get_transient( 'edd_csv_file' ) )
		delete_transient( 'edd_csv_file' );

	if( get_transient( 'edd_csv_map' ) )
		delete_transient( 'edd_csv_map' );

	if( get_transient( 'csv_fields' ) )
		delete_transient( 'csv_fields' );

	if( get_transient( 'has_headers' ) )
		delete_transient( 'has_headers' );
}