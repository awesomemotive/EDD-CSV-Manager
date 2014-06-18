<?php
/**
 * CSV Payment History Importer
 *
 * @since       1.1.0
 * @package     CSVManager\PaymentHistoryImporter
 * @copyright   Copyright (c) 2013, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_CSV_Payment_History_Importer' ) ) {

    class EDD_CSV_Payment_History_Importer {

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
                self::$instance = new EDD_CSV_Payment_History_Importer();
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

            if( version_compare( EDD_VERSION, '1.9.5', '<' ) ) {
                $this->page = 'tools.php?page=edd-settings-export-import';
	            add_action( 'edd_tools_before', array( $this, 'add_metabox' ) );
            } elseif( version_compare( EDD_VERSION, '1.9.4', '>' ) && version_compare( EDD_VERSION, '2.0', '<' ) ) {
                $this->page = 'edit.php?post_type=download&page=edd-tools';
                add_action( 'edd_tools_before', array( $this, 'add_metabox' ) );
                //add_action( 'edd_tools_import_export_after', array( $this, 'add_metabox' ) );
            } else {
                $this->page = 'edit.php?post_type=download&page=edd-tools';
				add_action( 'edd_tools_import_export_after', array( $this, 'add_metabox' ) );
            }

            // Handle uploading of a CSV
            add_action( 'edd_upload_purchase_csv', array( $this, 'upload' ) );

            // Handle mapping CSV fields to EDD fields
            add_action( 'edd_map_purchase_csv', array( $this, 'map' ) );
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

            ob_start();

            echo '<div class="postbox" id="edd-payment-import">';
            echo '<h3><span>' . __( 'Import Purchase History from CSV', 'edd-csv-manager' ) . '</span></h3>';
            echo '<div class="inside">';
            echo '<p>' . __( 'Import previous purchase history from other ecommerce systems from a .csv file.', 'edd-csv-manager' ) . '</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . admin_url( $this->page ) . '">';

            if( isset( $_GET['errno'] ) && isset( $_GET['type'] ) && $_GET['type'] == 'purchase' )
                edd_csv_error_handler( $_GET['errno'] );

            if( empty( $_GET['step'] ) || $_GET['step'] == 1 || ( isset( $_GET['type'] ) && $_GET['type'] != 'purchase' ) ) {
                if( empty( $_GET['step'] ) || $_GET['step'] == 1 ) {
                    // Cleanup data to provent accidental carryover
                    $this->cleanup();
                }

                echo '<p><input type="file" name="import_file" /></p>';
                echo '<p><label for="has_headers"><input type="checkbox" id="has_headers" name="has_headers" checked="yes" /> ' . __( 'Does the CSV include a header row?', 'edd-csv-manager' ) . '</label></p>';
                echo '<p><label for="resend_receipts"><input type="checkbox" id="resend_receipts" name="resend_receipts" checked="yes" /> ' . __( 'Should we resend purchase receipts?', 'edd-csv-manager' ) . '</label></p>';
                echo '<p>';
                echo '<input type="hidden" name="edd_action" value="upload_purchase_csv" />';
                wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' );
                submit_button( __( 'Next', 'edd-csv-manager' ), 'secondary', 'submit', false );
                echo '</p>';
            } elseif( $_GET['step'] == 2 && isset( $_GET['type'] ) && $_GET['type'] == 'purchase' ) {
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

                echo '<p><input type="hidden" name="edd_action" value="map_purchase_csv" />';
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
            if( get_transient( 'resend_receipts' ) )    delete_transient( 'resend_receipts' );
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
                'price'                     => __( 'Price', 'edd-csv-manager' ),
                'post_date'                 => __( 'Date Purchased', 'edd-csv-manager' ),
                'user_email'                => __( 'User Email', 'edd-csv-manager' ),
                'currency'                  => __( 'Currency', 'edd-csv-manager' ),
                'downloads'                 => __( 'Downloads', 'edd-csv-manager' ),
                'first_name'                => __( 'First Name', 'edd-csv-manager' ),
                'last_name'                 => __( 'Last Name', 'edd-csv-manager' ),
                'discount'                  => __( 'Discount', 'edd-csv-manager' ),
                'tax'                       => __( 'Tax', 'edd-csv-manager' )
            );

            $fields = apply_filters( 'edd_payment_history_csv_fields', $fields );
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
                wp_redirect( add_query_arg( array( 'tab' => 'import_export', 'type' => 'purchase', 'step' => '1', 'errno' => '2' ), $this->page ) );
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
            if( isset( $_POST['resend_receipts'] ) ) {
                set_transient( 'resend_receipts', '1' );
            }
            set_transient( 'edd_csv_file', basename( $import_file ) );

            wp_redirect( add_query_arg( array( 'tab' => 'import_export', 'type' => 'purchase', 'step' => '2#edd-payment-import' ), $this->page ) ); exit;
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
                wp_redirect( add_query_arg( array( 'tab' => 'import_export', 'type' => 'purchase', 'step' => '2', 'errno' => '1' ), $this->page ) );
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
                'price'                     => '',
                'post_date'                 => '',
                'user_email'                => '',
                'purchase_key'              => strtolower( md5( uniqid() ) ),
                'currency'                  => 'USD',
                'downloads'                 => '',
                'cart_details'              => '',
                'user_info'                 => array(
                    'id'                    => '',
                    'email'                 => '',
                    'first_name'            => '',
                    'last_name'             => '',
                    'discount'              => ''
                ),
                'user_id'                   => '',
                'status'                    => 'pending',
                'post_data'                 => array(),
                'gateway'                   => 'csv_import',
                'tax'                       => ''
            );

            $defaults = apply_filters( 'edd_payment_history_csv_default_fields', $defaults );

            $csv_fields = maybe_unserialize( get_transient( 'csv_fields' ) );
            $csv_fields = wp_parse_args( $csv_fields, $defaults );
            $headers = get_transient( 'edd_csv_headers' );

            $filename = get_transient( 'edd_csv_file' );
            $import_file = trailingslashit( WP_CONTENT_DIR ) . $filename;

            $csv = new parseCSV();

            // Detect delimiter
            $csv->auto( $import_file );

            // Map headers to post fields
            $price_key          = array_search( $csv_fields['price'], $headers );
            $post_date_key      = array_search( $csv_fields['post_date'], $headers );
            $user_email_key     = array_search( $csv_fields['user_email'], $headers );
            $currency_key       = array_search( $csv_fields['currency'], $headers );
            $downloads_key      = array_search( $csv_fields['downloads'], $headers );
            $tax_key            = array_search( $csv_fields['tax'], $headers );
            $first_name_key     = array_search( $csv_fields['first_name'], $headers );
            $last_name_key      = array_search( $csv_fields['last_name'], $headers );
            $discount_key       = array_search( $csv_fields['discount'], $headers );

            foreach( $csv->data as $key => $row ) {
                $new_row = array();
                $i = 0;
                foreach( $row as $column ) {
                    $new_row[ $i ] = $column;
                    $i++;
                }

                // Get user info or create new user
                $user = get_user_by( 'email', $new_row[ $user_email_key ] );

                if( !$user ) {
                    $password = wp_generate_password();
                    $user_id = wp_insert_user(
                        array(
                            'user_email'    => sanitize_email( $new_row[ $user_email_key ] ),
                            'user_login'    => sanitize_email( $new_row[ $user_email_key ] ),
                            'user_pass'     => $password,
                            'first_name'    => sanitize_text_field( $new_row[ $first_name_key ] ),
                            'last_name'     => sanitize_text_field( $new_row[ $last_name_key ] )
                        )
                    );

                    $user    = get_user_by( 'id', $user_id );
                    $email   = $user->user_email;
                } else {
                    $user_id = $user->ID;
                    $email   = $user->user_email;
                }

                $download_errors = array();

                // Setup downloads
                if( $downloads_key && !empty( $new_row[ $downloads_key ] ) ) {

                    $downloads       = array_map( 'trim', explode( '|', $new_row[ $downloads_key ] ) );
                    $final_downloads = array();
                    $products        = array();
                    $cart_details    = array();
                    $i               = 0;

                    // Make sure downloads exist
                    foreach( $downloads as $i => $download_name ) {
                        $download = get_page_by_title( $download_name, OBJECT, 'download' );
                        
                        if( !$download && is_numeric( $download_name ) ) {
                            $download = get_post( $download_name, OBJECT );
                            $post_type = get_post_type( $download );

                            if( $post_type != 'download' ) unset( $download );
                        }

                        if( $download ) {
                            $products[] = array(
                                'id'      => $download->ID,
                                'options' => array()
                            );
                        
                            $cart_details[] = array(
                                'id'       => $download->ID,
                                'price'    => '',
                                'tax'      => 0,
                                'quantity' => 1
                            );
                        } else {
                            // Error
                            $download_errors[] = array(
                                'row'      => $i,
                                'product'  => $download_name
                            );
                        }
                    }

                    $payment_data = array(
                        'price'         => $new_row[ $price_key ],
                        'post_date'     => date( 'Y-m-d H:i:s', strtotime( $new_row[ $post_date_key ] ) ),
                        'user_email'    => $new_row[ $user_email_key ],
                        'purchase_key'  => strtolower( md5( uniqid() ) ), // random key
                        'currency'      => 'USD',
                        'downloads'     => $products,
                        'cart_details'  => $cart_details,
                        'user_info'     => array(
                            'id'            => $user_id,
                            'email'         => $email,
                            'first_name'    => $new_row[ $first_name_key ],
                            'last_name'     => $new_row[ $last_name_key ],
                            'discount'      => $new_row[ $discount_key ]
                        ),
                        'user_id'       => $user_id,
                        'status'        => 'pending',
                        'post_data'     => array(),
                        'gateway'       => 'csv_import',
                        'tax'           => $new_row[ $tax_key ]
                    );

                    $payment_id = edd_insert_payment( $payment_data );
                    if( !get_transient( 'resend_receipts' ) ) {
                        remove_action( 'edd_update_payment_status', 'edd_trigger_purchase_receipt', 10 );
                        remove_action( 'edd_complete_purchase', 'edd_trigger_purchase_receipt', 999 );
                    }

                    edd_update_payment_status( $payment_id, 'publish' );
                }
            }


            if( !empty( $download_errors ) ) {
                $download_errors = serialize( $download_errors );
                set_transient( 'edd_download_errors', $download_errors );

                wp_redirect( add_query_arg( array( 'tab' => 'import_export', 'type' => 'purchase', 'step' => '1', 'errno' => '6' ), $this->page ) );
                exit;
            }

            wp_redirect( add_query_arg( array( 'tab' => 'import_export', 'type' => 'purchase', 'step' => '1', 'errno' => '0' ), $this->page ) );
            exit;
        }
    }
}


function EDD_CSV_PAYMENT_HISTORY_IMPORTER() {
    return EDD_CSV_Payment_History_Importer::instance();
}
