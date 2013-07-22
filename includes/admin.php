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
							$field_label = $field;
							$field_id = $field;
						} else {
							$i = $id + 1;
							$field_label = 'column_' . $i;
							$field_id = $id;
						}

						echo '<div style="width: 200px; display: inline-block;">' . $field_label . '</div>';
						echo '<select name="csv_fields[' . $field_id . ']" >' . edd_csv_get_fields( $field_id ) . '</select><br/>';
					}

					echo '<p><input type="hidden" name="edd_action" value="map_csv" />';
					wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' );
					submit_button( __( 'Next', 'edd' ), 'secondary', 'submit', false );
					echo '</p>';
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
function edd_process_csv_upload() {

	if( empty( $_POST['edd_import_nonce'] ) ) return;

	if( !wp_verify_nonce( $_POST['edd_import_nonce'], 'edd_import_nonce' ) ) return;

	if( !current_user_can( 'manage_shop_settings' ) ) return;

	$csv = new parseCSV();

	$import_file = $_FILES['import_file']['tmp_name'];

	// Make sure we have a valid CSV
	if( empty( $import_file ) || !edd_is_valid_csv( $_FILES['import_file']['name'] ) ) {
		wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '2' ) ) );
		exit;
	}

	// Detect deliminator
	$csv->auto( $import_file );

	// Duplicate the temp file so it doesn't disappear on us
	$desination = trailingslashit( WP_CONTENT_DIR ) . basename( $import_file );
	move_uploaded_file( $import_file, $desination );

	if( isset( $_POST['has_headers'] ) ) {
		set_transient( 'has_headers', '1' );
		set_transient( 'edd_csv_headers', $csv->titles );
	}
	set_transient( 'edd_csv_file', basename( $import_file ) );

	wp_redirect( add_query_arg( 'step', '2' ) ); exit;

}
add_action( 'edd_upload_csv', 'edd_process_csv_upload' );


/**
 * Map CSV file
 *
 * @since		1.0.0
 * @return		void
 */
function edd_process_csv_mapping() {
	if( empty( $_POST['edd_import_nonce'] ) ) return;

	if( !wp_verify_nonce( $_POST['edd_import_nonce'], 'edd_import_nonce' ) ) return;

	if( !current_user_can( 'manage_shop_settings' ) ) return;

	// Invert the array... array_flip ftw!
	$fields = array_flip( $_POST['csv_fields'] );

	if( edd_csv_map_has_duplicates( $_POST['csv_fields'] ) ) {
		wp_redirect( add_query_arg( array( 'step' => '2', 'errno' => '1' ) ) );
		exit;
	}

	set_transient( 'csv_fields', serialize( $fields ) );
	edd_process_csv_import();
	//wp_redirect( add_query_arg( 'step', '3' ) ); exit;
}
add_action( 'edd_map_csv', 'edd_process_csv_mapping' );


/**
 * Import CSV content
 *
 * @since		1.0.0
 * @return		void
 */
function edd_process_csv_import() {

	$defaults = array(
		'post_name'		=> '',
		'post_author'	=> '',
		'post_title'	=> '',
		'post_content'	=> '',
		'post_excerpt'	=> '',
		'post_status'	=> '',
		'post_type'		=> 'download',
		'categories'	=> '',
		'tags'			=> ''
	);

	$csv_fields  = maybe_unserialize( get_transient( 'csv_fields' ) );
	$csv_fields  = wp_parse_args( $csv_fields, $defaults );
	$headers     = get_transient( 'edd_csv_headers' );
	$file_errors = array();

	//echo '<pre>'; print_r( $csv_fields ); echo '</pre>';

	ini_set( 'auto_detect_line_endings', true );

	$filename = get_transient( 'edd_csv_file' );
	$file = trailingslashit( WP_CONTENT_DIR ) . $filename;
	if( ( $handle = fopen( $file, 'r' ) ) !== FALSE ) {
		$i = 0;
		while( ( $row = fgetcsv( $handle, 1000 ) ) !== FALSE ) {

			// Get the column keys
			if( $i < 1 ) {
				// Default fields
				$post_name_key		= array_search( $csv_fields['post_name'], $row );
				$post_author_key	= array_search( $csv_fields['post_author'], $row );
				$post_title_key		= array_search( $csv_fields['post_title'], $row );
				$post_content_key	= array_search( $csv_fields['post_content'], $row );
				$post_excerpt_key	= array_search( $csv_fields['post_excerpt'], $row );
				$post_status_key	= array_search( $csv_fields['post_status'], $row );
				$post_date_key		= array_search( $csv_fields['post_date'], $row );

				// Meta fields
				$price_key			= array_search( $csv_fields['_edd_price'], $row );
				$dl_limit_key		= array_search( $csv_fields['_edd_download_limit'], $row );
				$button_behavior	= array_search( $csv_fields['_edd_button_behavior'], $row );
				$hide_link			= array_search( $csv_fields['_edd_hide_purchase_link'], $row );

				// Categories
				$categories_key		= array_search( $csv_fields['categories'], $row );

				// Tags
				$tags_key			= array_search( $csv_fields['tags'], $row );

				// Files
				$files_key			= array_search( $csv_fields['_edd_files'], $row );

				// Featured image
				$image_key			= array_search( $csv_fields['_thumbnail_id'], $row );
			}

 			if( ! $headers && $i <= 1 ) {
 				$i++;
 				continue;
 			}

 			if( ( $headers && $i > 0 ) || ! $headers ) {
				$post_data = array(
					'post_name'		=> $row[ $post_name_key ],
					'post_author'	=> $row[ $post_author_key ],
					'post_title'	=> $row[ $post_title_key ],
					'post_content'	=> $row[ $post_content_key ],
					'post_excerpt'	=> $row[ $post_name_key ],
					'post_status'	=> $row[ $post_status_key ],
					'post_date'		=> date( 'Y-m-d H:i:s', strtotime( $row[ $post_date_key ] ) ),
					'post_type'		=> 'download'
				);

				// Set files
				if( $files_key && !empty( $row[ $files_key ] ) ) {

					$files = array_map( 'trim', explode( '|', $row[ $files_key ] ) );
					$final_files = array();

					// Make sure files exist
					foreach( $files as $file ) {
						$file_details = parse_url( $file );
						if( ! $file_details || ! isset( $file_details['scheme'] ) || 'http' != $file_details['scheme'] || 'https' != $file_details['scheme'] ) {
							// Set preferred path for file hosting
							$search_base_path = trailingslashit( WP_CONTENT_DIR );
							$preferred_path = $search_base_path . 'uploads/edd/' . $file;

							if( file_exists( $preferred_path ) ) {
								// Check /wp-content/uploads/edd/$file
								$file_path = $preferred_path;
							} elseif( file_exists( $search_base_path . $file ) ) {
								// Check /wp-content/$file
								if( move_uploaded_file( $search_base_path . $file, $preferred_path ) ) {
									$file_path = $preferred_path;
								} else {
									$file_path = $search_base_path . $file;
								}
							} elseif( file_exists( $search_base_path . 'uploads/' . $file ) ) {
								// Check /wp-content/uploads/$file
								if( move_uploaded_file( $search_base_pat . 'uploads/' . $file, $preferred_path ) ) {
									$file_path = $preferred_path;
								} else {
									$file_path = $search_base_path . 'uploads/' . $file;
								}
							} else {
								// Error
								$file_errors[] = array(
									'row'  => $i + 1,
									'file' => $file
								);
							}
						}

						// Store file in array for use later
						$final_files[] = array(
							'name' => basename( $file_path ),
							'file' => str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $file_path )
						);
					}
				}

				if( ! empty( $file_errors ) ) {
					$i++;
					continue;
				}

				$image = false;
				// Set featured images
				if( $image_key && !empty( $row[ $image_key ] ) ) {

					require_once ABSPATH . 'wp-adminincludes/image.php';
					require_once ABSPATH . 'wp-adminincludes/file.php';
					require_once ABSPATH . 'wp-adminincludes/media.php';

					$image = true;
					$image_details = parse_url( $image_key );
					if( ! $image_details || ! isset( $image_details['scheme'] ) || 'http' != $image_details['scheme'] || 'https' != $image_details['scheme'] ) {
						// Set preferred path for file hosting
						$search_base_path = trailingslashit( WP_CONTENT_DIR );
						$preferred_path = $search_base_path . 'uploads/edd/' . $image_key;

						if( file_exists( $preferred_path ) ) {
							// Check /wp-content/uploads/edd/$file
							$file_path = $preferred_path;
						} elseif( file_exists( $search_base_path . $image_key ) ) {
							// Check /wp-content/$file
							if( move_uploaded_file( $search_base_path . $image_key, $preferred_path ) ) {
								$file_path = $preferred_path;
							} else {
								$file_path = $search_base_path . $image_key;
							}
						} elseif( file_exists( $search_base_path . 'uploads/' . $image_key ) ) {
							// Check /wp-content/uploads/$file
							if( move_uploaded_file( $search_base_pat . 'uploads/' . $image_key, $preferred_path ) ) {
								$file_path = $preferred_path;
							} else {
								$file_path = $search_base_path . 'uploads/' . $image_key;
							}
						} else {
							// Error
							$image = false;
							$file_errors[] = array(
								'row'  => $i + 1,
								'file' => $image_key
							);
						}
					}

					// Store file in array for use later
					$final_files[] = array(
						'name' => basename( $file_path ),
						'file' => str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $file_path )
					);
				}

				// Check /wp-content/uploads/edd/$file
				$post_id = wp_insert_post( $post_data );

				// Make sure it was created
				if( $post_id ) {

					// Set meta fields
					if( $price_key && !empty( $row[ $price_key ] ) )
						update_post_meta( $post_id, 'edd_price', $row[ $price_key ] );

					if( $dl_limit_key && !empty( $row[ $dl_limit_key ] ) )
						update_post_meta( $post_id, '_edd_download_limit', $row[ $dl_limit_key ] );

					if( $button_behavior && !empty( $row[ $button_behavior ] ) )
						update_post_meta( $post_id, '_edd_button_behavior', $row[ $button_behavior ] );

					if( $hide_link && !empty( $row[ $hide_link ] ) )
						update_post_meta( $post_id, '_edd_hide_purchase_link', $row[ $hide_link ] );

					// Set download files
					if( ! empty( $final_files ) )
						update_post_meta( $post_id, 'edd_download_files', $final_files );

					// Attach image
					if( $image )
						$attachment_id = wp_handle_upload( $file_path, $post_id );

					// Set tags
					if( $tags_key && !empty( $row[ $tags_key ] ) ) {

						$tags = array_map( 'trim', explode( '|', $row[ $tags_key ] ) );

						// Create tags if they don't exist
						foreach( $tags as $tag ) {
							if( !term_exists( $tag, 'download_tag' ) ) {
								wp_insert_term( $tag, 'download_tag' );
							}
						}

						wp_set_object_terms( $post_id, $tags, 'download_tag' );
					}

					// Set categories
					if( $categories_key && !empty( $row[ $categories_key ] ) ) {

						$categories = array_map( 'trim', explode( '|', $row[ $categories_key ] ) );

						// Create categories if they don't exist
						foreach( $categories as $category ) {
							if( !term_exists( $category, 'download_category' ) ) {
								wp_insert_term( $category, 'download_category' );
							}
						}

						wp_set_object_terms( $post_id, $categories, 'download_category' );
					}
				}
			}
			$i++;
		}
		fclose( $handle );

		if( ! empty( $file_errors ) ) {
			$file_errors = serialize( $file_errors );
			set_transient( 'edd_file_errors', $file_errors );

			wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '3' ) ) );
			exit;
		}

		exit;
	}

	ini_set( 'auto_detect_line_endings', false );

}


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
		'post_date'					=> 'Date Created',
		'post_content'				=> 'Description',
		'post_title'				=> 'Download Name',
		'post_excerpt'				=> 'Excerpt',
		'post_name'					=> 'Post Name',
		'post_status'				=> 'Status',
		'categories'			    => 'Categories',
		'tags'					    => 'Tags',
		'_edd_files'				=> 'Download Files',
		'_edd_download_limit'		=> 'Download Limit',
		'_edd_hide_purchase_link'	=> 'Hide Purchase Link',
		'_edd_images'				=> 'Image Files',
		'_edd_price'				=> 'Price'
	);

	$return = '';

	$fields = apply_filters( 'edd_csv_fields', $fields );

	foreach( $fields as $field_name => $field_title ) {
		$return .= '<option value="' . $field_name . '"' . edd_csv_map_preset_on_error( $parent, $field_name ) . '>' . $field_title . '</option>';
	}

	return $return;
}
