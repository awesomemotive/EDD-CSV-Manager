<?php
/**
 * CSV Product Importer
 *
 * @since       1.0.0
 * @package     EDD CSV Manager
 * @subpackage  Importer
 * @copyright   Copyright (c) 2013, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_CSV_Importer' ) ) {

    class EDD_CSV_Importer {

        private static $instance;

        private $page;

        /**
         * Get active instance
         *
         * @since       1.0.0
         * @access      public
         * @static
         * @return      object self::$instance
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_CSV_Importer();
                self::$instance->includes();
                self::$instance->init();
            }

            return self::$instance;
        }


        /**
         * Run action and filter hooks
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function init() {

            if( version_compare( EDD_VERSION, '1.8', '<' ) ) {
                $this->page = 'tools.php?page=edd-settings-export-import';
            } else {
                $this->page = 'edit.php?post_type=download&page=edd-tools';
            }

            // Add metabox
            add_action( 'edd_export_import_top', array( $this, 'add_metabox' ) );
            add_action( 'edd_tools_before', array( $this, 'add_metabox' ) );

            // Handle uploading of a CSV
            add_action( 'edd_upload_csv', array( $this, 'upload' ) );

            // Handle mapping CSV fields to EDD fields
            add_action( 'edd_map_csv', array( $this, 'map' ) );
        }


        /**
         * Include required files
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function includes() {
            require_once EDD_CSV_MANAGER_DIR . 'includes/libraries/parsecsv.lib.php';
        }


        /**
         * Add metabox
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function add_metabox() {

            $wp_upload_dir = wp_upload_dir();

            ob_start();

            echo '<div class="postbox">';
            echo '<h3><span>' . __( 'Import Products from CSV', 'edd-csv-manager' ) . '</span></h3>';
            echo '<div class="inside">';
            echo '<p>' . __( 'Import products to your Easy Digital Downloads site from a .csv file.', 'edd-csv-manager' ) . '</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . admin_url( $this->page ) . '">';

            if( isset( $_GET['errno'] ) )
                edd_csv_error_handler( $_GET['errno'] );

            if( empty( $_GET['step'] ) || $_GET['step'] == 1 ) {
                // Cleanup data to provent accidental carryover
                $this->cleanup();

                echo '<p><input type="file" name="import_file" /></p>';
                echo '<p><label for="has_headers"><input type="checkbox" id="has_headers" name="has_headers" checked="yes" /> ' . __( 'Does the CSV include a header row?', 'edd-csv-manager' ) . '</label></p>';
                echo '<p>';
                echo '<input type="hidden" name="edd_action" value="upload_csv" />';
                wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' );
                submit_button( __( 'Next', 'edd-csv-manager' ), 'secondary', 'submit', false );
                echo '</p>';
            } elseif( $_GET['step'] == 2 ) {
                $fields = get_transient( 'edd_csv_headers' );

                // Display CSV fields for mapping
                echo '<div style="font-width: bold; border-bottom: 1px solid #000000; width: 350px; margin-bottom: 10px;">';
                echo '<span style="width: 200px; display: inline-block;">' . __( 'CSV Headers', 'edd-csv-manager' ) . '</span>';
                echo '<span style="display: inline-block;">' . __( 'EDD Fields', 'edd-csv-manager' ) . '</span>';
                echo '</div>';

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
                    echo '<select name="csv_fields[' . $field_id . ']" >' . $this->get_fields( $field_label ) . '</select><br />';
                }

                if( class_exists( 'EDD_Amazon_S3' ) ) {
                    echo '<p><label for="edd_import_s3">';
                        echo '<input type="checkbox" value="1" name="edd_import_s3" id="edd_import_s3"/>&nbsp;';
                        echo __( 'Are your download files stored on Amazon S3?', 'edd-csv-manager' );
                    echo '</label></p>';
                }

                echo '<p><input type="hidden" name="edd_action" value="map_csv" />';
                wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' );
                submit_button( __( 'Import', 'edd-csv-manager' ), 'secondary', 'submit', false );
                echo '</p>';
            }

            echo '</form>';
            echo '</div>';
            echo '</div>';
        }


        /**
         * Cleanup unneeded transients
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function cleanup() {
            if( get_transient( 'edd_file_errors' ) )    delete_transient( 'edd_file_errors' );
            if( get_transient( 'edd_image_errors' ) )   delete_transient( 'edd_image_errors' );
            if( get_transient( 'edd_csv_headers' ) )    delete_transient( 'edd_csv_headers' );
            if( get_transient( 'edd_csv_file' ) )       delete_transient( 'edd_csv_file' );
            if( get_transient( 'edd_csv_map' ) )        delete_transient( 'edd_csv_map' );
            if( get_transient( 'has_headers' ) )        delete_transient( 'has_headers' );
        }


        /**
         * Get dropdown list of available fields
         *
         * @since       1.0.0
         * @access      public
         * @param       string $parent the name of a particular select element
         * @return      string
         */
        public function get_fields( $parent ) {
            $fields = array(
                'post_author'               => __( 'Author ID', 'edd-csv-manager' ),
                '_edd_button_behavior'      => __( 'Button Behavior', 'edd-csv-manager' ),
                'categories'                => __( 'Categories', 'edd-csv-manager' ),
                'post_date'                 => __( 'Date Created', 'edd-csv-manager' ),
                'post_content'              => __( 'Description', 'edd-csv-manager' ),
                '_edd_files'                => __( 'Download Files', 'edd-csv-manager' ),
                '_edd_download_limit'       => __( 'Download Limit', 'edd-csv-manager' ),
                'post_excerpt'              => __( 'Excerpt', 'edd-csv-manager' ),
                '_edd_hide_purchase_link'   => __( 'Hide Purchase Link', 'edd-csv-manager' ),
                '_edd_images'               => __( 'Image Files', 'edd-csv-manager' ),
                'post_name'                 => __( 'Post Name', 'edd-csv-manager' ),
                '_edd_price'                => __( 'Price', 'edd-csv-manager' ),
                'post_title'                => __( 'Product Name', 'edd-csv-manager' ),
                'post_status'               => __( 'Status', 'edd-csv-manager' ),
				'tags'                      => __( 'Tags', 'edd-csv-manager' ),
				'edd_sku'                   => __( 'SKU', 'edd-csv-manager' )
            );

            $fields = apply_filters( 'edd_csv_fields', $fields );
            asort( $fields );

            $return = '<option value="">' . __( 'Unmapped', 'edd-csv-manager' ) . '</option>';

            foreach( $fields as $field_name => $field_title ) {
                $return .= '<option value="' . $field_name . '"' . $this->map_preset( $parent, $field_name ) . '>' . $field_title . '</option>';
            }

            return $return;
        }


        /**
         * Handles presetting mapping on submit when errors exist
         *
         * @since       1.0.0
         * @access      private
         * @param       string $parent the parent element we are checking
         * @param       string $field_name the value to check against
         * @return      string $selected
         */
        private function map_preset( $parent, $field_name ) {
            // Get mapped fields
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
         * Process import from a CSV file
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function upload() {
            if( empty( $_POST['edd_import_nonce'] ) ) return;

            if( !wp_verify_nonce( $_POST['edd_import_nonce'], 'edd_import_nonce' ) ) return;

            if( !current_user_can( 'manage_shop_settings' ) ) return;

            $csv = new parseCSV();

            $import_file = $_FILES['import_file']['tmp_name'];

            // Make sure we have a valid CSV
            if( empty( $import_file ) || !$this->is_valid_csv( $_FILES['import_file']['name'] ) ) {
                wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '2' ), $this->page ) );
                exit;
            }

            // Detect delimiter
            $csv->auto( $import_file );

            // Duplicate the temp file so it doesn't disappear on us
            $destination = trailingslashit( WP_CONTENT_DIR ) . basename( $import_file );
            move_uploaded_file( $import_file, $destination );

            if( isset( $_POST['has_headers'] ) ) {
                set_transient( 'has_headers', '1' );
                set_transient( 'edd_csv_headers', $csv->titles );
            }
            set_transient( 'edd_csv_file', basename( $import_file ) );

            wp_redirect( add_query_arg( 'step', '2', $this->page ) ); exit;
        }


        /**
         * Ensure the uploaded file is a valid CSV
         *
         * @since       1.0.0
         * @access      private
         * @param       string $file the filename of a specified upload
         * @return      bool
         */
        private function is_valid_csv( $file ) {
            // Array of allowed extensions
            $allowed = array( 'csv' );

            // Determine the extension for the uploaded file
            $ext = pathinfo( $file, PATHINFO_EXTENSION );

            // Check if $ext is allowed
            if( in_array( $ext, $allowed ) )
                return true;

            return false;
        }


        /**
         * Handle mapping of CSV fields to EDD fields
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function map() {
            if( empty( $_POST['edd_import_nonce'] ) ) return;

            if( !wp_verify_nonce( $_POST['edd_import_nonce'], 'edd_import_nonce' ) ) return;

            if( !current_user_can( 'manage_shop_settings' ) ) return;

            // Invert the array... array_flip ftw!
            $fields = array_flip( $_POST['csv_fields'] );

            if( $this->map_has_duplicates( $_POST['csv_fields'] ) ) {
                wp_redirect( add_query_arg( array( 'step' => '2', 'errno' => '1' ), $this->page ) );
                exit;
            }

            set_transient( 'csv_fields', serialize( $fields ) );
            $this->process_import();
        }


        /**
         * Check a given map for duplicates
         *
         * @since       1.0.0
         * @access      private
         * @param       array $fields an array of mapped fields
         * @return      bool
         */
        function map_has_duplicates( $fields ) {
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
         * Import the mapped data to EDD
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function process_import() {

            $defaults = array(
                'post_name'                 => '',
                'post_date'                 => '',
                'post_author'               => '',
                'post_title'                => '',
                'post_content'              => '',
                'post_excerpt'              => '',
                'post_status'               => 'draft',
                'post_type'                 => 'download',
                'categories'                => '',
                'tags'                      => '',
                '_edd_price'                => '',
                '_edd_files'                => '',
                '_edd_download_limit'       => '',
                '_edd_button_behavior'      => '',
                '_edd_hide_purchase_link'   => '',
				'_edd_images'               => '',
				'edd_sku'                   => ''
            );

            $defaults = apply_filters( 'edd_csv_default_fields', $defaults );

            $csv_fields = maybe_unserialize( get_transient( 'csv_fields' ) );
            $csv_fields = wp_parse_args( $csv_fields, $defaults );
            $headers = get_transient( 'edd_csv_headers' );

            $filename = get_transient( 'edd_csv_file' );
            $import_file = trailingslashit( WP_CONTENT_DIR ) . $filename;

            $csv = new parseCSV();

            // Detect delimiter
            $csv->auto( $import_file );

            // Map headers to post fields
            $post_name_key      = array_search( $csv_fields['post_name'], $headers );
            $post_author_key    = array_search( $csv_fields['post_author'], $headers );
            $post_title_key     = array_search( $csv_fields['post_title'], $headers );
            $post_content_key   = array_search( $csv_fields['post_content'], $headers );
            $post_excerpt_key   = array_search( $csv_fields['post_excerpt'], $headers );
            $post_status_key    = array_search( $csv_fields['post_status'], $headers );
            $post_date_key      = array_search( $csv_fields['post_date'], $headers );

            // Meta fields
            $price_key          = array_search( $csv_fields['_edd_price'], $headers );
            $dl_limit_key       = array_search( $csv_fields['_edd_download_limit'], $headers );
            $button_behavior    = array_search( $csv_fields['_edd_button_behavior'], $headers );
            $hide_link          = array_search( $csv_fields['_edd_hide_purchase_link'], $headers );
			$edd_sku            = array_search( $csv_fields['edd_sku'], $headers );

            // Categories
            $categories_key     = array_search( $csv_fields['categories'], $headers );

            // Tags
            $tags_key           = array_search( $csv_fields['tags'], $headers );

            // Files
            $files_key          = array_search( $csv_fields['_edd_files'], $headers);

            foreach( $csv->data as $key => $row ) {
                $new_row = array();
                $i = 0;
                foreach( $row as $column ) {
                    $new_row[ $i ] = $column;
                    $i++;
                }

                // Get the column keys
                $post_data = array(
                    'post_name'     => $new_row[ $post_name_key ],
                    'post_author'   => $new_row[ $post_author_key ],
                    'post_title'    => $new_row[ $post_title_key ],
                    'post_content'  => $new_row[ $post_content_key ],
                    'post_excerpt'  => $new_row[ $post_excerpt_key ],
                    'post_status'   => $new_row[ $post_status_key ],
                    'post_date'     => date( 'Y-m-d H:i:s', strtotime( $new_row[ $post_date_key ] ) ),
                    'post_type'     => 'download'
                );

                $file_errors = array();

                // Set files
                if( $files_key && !empty( $new_row[ $files_key ] ) ) {

                    $files = array_map( 'trim', explode( '|', $new_row[ $files_key ] ) );
                    $final_files = array();
                    $file_path = '';

                    // Make sure files exist
                    foreach( $files as $file ) {
                        $file_details = parse_url( $file );

                        if(
                            (
                                ! $file_details ||
                                ! isset( $file_details['scheme'] ) ||
                                (
                                    'http' != $file_details['scheme'] &&
                                    'https' != $file_details['scheme'] &&
                                    strpos( $file, site_url() ) !== false
                                )
                            ) && ! isset( $_POST['edd_import_s3'] )
                        ) {
                            // Set preferred path for file hosting
                            $search_base_path = trailingslashit( WP_CONTENT_DIR );
                            $preferred_path = $search_base_path . 'uploads/edd/' . $file;

                            if( file_exists( $preferred_path ) ) {
                                // Check /wp-content/uploads/edd/$file
                                $file_path = $preferred_path;
                            } elseif( file_exists( $search_base_path . $file ) ) {
                                // Check /wp-content/$file
                                if( rename( $search_base_path . $file, $preferred_path ) ) {
                                    $file_path = $preferred_path;
                                } else {
                                    $file_path = $search_base_path . $file;
                                }
                            } elseif( file_exists( $search_base_path . 'uploads/' . $file ) ) {
                                // Check /wp-content/uploads/$file
                                if( rename( $search_base_path . 'uploads/' . $file, $preferred_path ) ) {
                                    $file_path = $preferred_path;
                                } else {
                                    $file_path = $search_base_path . 'uploads/' . $file;
                                }
                            } else {
                                // Error
                                $file_errors[] = array(
                                    'row'   => $i + 1,
                                    'file'  => $file
                                );
                            }

                        } else {
                             $file_path = $file;
                        }

                        // Store file in array for later use
                        $final_files[] = array(
                            'name'  => basename( $file_path ),
                            'file'  => str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $file_path )
                        );
                    }
                }


                // Handle file errors
                if( !empty( $file_errors ) ) {
                    $i++;
                    continue;
                }


                // Setup featured image
                $image_key      = array_search( $csv_fields['_edd_images'], $headers );
                $image          = false;
                $final_images   = array();

                if( $image_key && !empty( $new_row[ $image_key ] ) ) {

                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';

                    $image          = true;
                    $image_file     = $new_row[ $image_key ];
                    $image_details  = parse_url( $image_file );

                    if( !$image_details || !isset( $image_details['scheme'] ) || ( 'http' != $image_details['scheme'] && 'https' != $image_details['scheme'] && strpos( $image_file, site_url() ) !== false ) ) {
                        // Set preferred path for file hosting
                        $search_base_path = trailingslashit( WP_CONTENT_DIR );
                        $preferred_path = $search_base_path . 'uploads/edd/' . $image_file;

                        if( file_exists( $preferred_path ) ) {
                            // Check /wp-content/uploads/edd/$file
                            $file_path = $preferred_path;
                        } elseif( file_exists( $search_base_path . $image_file ) ) {
                            // Check /wp-content/$file
                            if( rename( $search_base_path . $image_file, $preferred_path ) ) {
                                $file_path = $preferred_path;
                            } else {
                                $file_path = $search_base_path . $image_file;
                            }
                        } elseif( file_exists( $search_base_path . 'uploads/' . $image_file ) ) {
                            // Check /wp-content/uploads/$file
                            if( rename( $search_base_path . 'uploads/' . $image_file, $preferred_path ) ) {
                                $file_path = $preferred_path;
                            } else {
                                $file_path = $search_base_path . 'uploads/' . $image_file;
                            }
                        } else {
                            // Error
                            $image = false;
                            $file_errors[] = array(
                                'row'   => $i + 1,
                                'file'  => $image_key
                            );
                        }
                    } else {
                        $file_path = $image_file;
                    }

                    // Store image in array for later use
                    $final_images[] = array(
                        'name'  => basename( $file_path ),
                        'path'  => $file_path,
                        'url'   => str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $file_path )
                    );
                }


                $post_id = wp_insert_post( $post_data );

                // Verify post created successfully
                if( $post_id ) {

                    // Set meta fields
                    if( $price_key && !empty( $new_row[ $price_key ] ) )
                        update_post_meta( $post_id, 'edd_price', $new_row[ $price_key ] );

                    if( $dl_limit_key && !empty( $new_row[ $dl_limit_key ] ) )
                        update_post_meta( $post_id, '_edd_download_limit', $new_row[ $dl_limit_key ] );

                    if( $button_behavior && !empty( $new_row[ $button_behavior ] ) )
                        update_post_meta( $post_id, '_edd_button_behavior', $new_row[ $button_behavior] );

                    if( $hide_link && !empty( $new_row[ $hide_link ] ) )
                        update_post_meta( $post_id, '_edd_hide_purchase_link', $new_row[ $hide_link ] );

					if( $edd_sku && !empty( $new_row[ $edd_sku ] ) )
						update_post_meta( $post_id, 'edd_sku', $new_row[ $edd_sku ] );

                    if( !empty( $final_files ) )
                        update_post_meta( $post_id, 'edd_download_files', $final_files );


                    // Attach image
                    if( $image ) {
                        $filetype = wp_check_filetype( $final_images[0]['name'], null );

                        $attachment = array(
                            'guid'              => $final_images[0]['url'],
                            'post_mime_type'    => $filetype['type'],
                            'post_title'        => preg_replace( '/\.[^.]+$/', '', $final_images[0]['name'] ),
                            'post_content'      => '',
                            'post_status'       => 'inherit',
                            'post_parent'       => $post_id
                        );

                        $upload_dir = wp_upload_dir();

                        if( stristr( $final_images[0]['path'], $upload_dir['basedir'] ) ) {
                            $attachment_url = str_replace( $upload_dir['basedir'], '', $final_images[0]['path'] );

                            $attachment_id = wp_insert_attachment( $attachment, $attachment_url, $post_id );

                            if( !is_wp_error( $attachment_id ) && $attachment_id ) {
                                update_post_meta( $post_id, '_thumbnail_id', $attachment_id );

                                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $final_images[0]['path'] );
                                wp_update_attachment_metadata( $attachment_id, $attachment_data );
                            } else {
                                $image_errors = serialize( $final_images[0]['path'] );
                                set_transient( 'edd_image_errors', $image_errors );

                                wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '4' ), $this->page ) );
                                exit;
                            }
                        } else {
                            $image_errors = serialize( $final_images[0]['path'] );
                            set_transient( 'edd_image_perms_errors', $image_errors );

                            wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '5' ), $this->page ) );
                            exit;
                        }
                    }


                    // Set tags
                    if( $tags_key && !empty( $new_row[ $tags_key ] ) ) {

                        $tags = array_map( 'trim', explode( '|', $new_row[ $tags_key ] ) );

                        // Create tags if they don't exist
                        foreach( $tags as $tag ) {
                            if( !term_exists( $tag, 'download_tag' ) )
                                wp_insert_term( $tag, 'download_tag' );
                        }

                        wp_set_object_terms( $post_id, $tags, 'download_tag' );
                    }


                    // Set categories
                    if( $categories_key && !empty( $new_row[ $categories_key ] ) ) {

                        $categories = array_map( 'trim', explode( '|', $new_row[ $categories_key ] ) );

                        // Create categories if they don't exist
                        foreach( $categories as $category ) {
                            if( !term_exists( $category, 'download_category' ) )
                                wp_insert_term( $category, 'download_category' );
                        }

                        wp_set_object_terms( $post_id, $categories, 'download_category' );
                    }
                }
            }


            if( !empty( $file_errors ) ) {
                $file_errors = serialize( $file_errors );
                set_transient( 'edd_file_errors', $file_errors );

                wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '3' ), $this->page ) );
                exit;
            }

            wp_redirect( add_query_arg( array( 'step' => '1', 'errno' => '0' ), $this->page ) );
            exit;
        }
    }
}


function EDD_CSVI() {
    return EDD_CSV_Importer::instance();
}
