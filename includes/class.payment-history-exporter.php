<?php
/**
 * CSV Payment History Exporter
 *
 * @since       1.2.0
 * @package     CSVManager\PaymentHistoryExporter
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


if( ! class_exists( 'EDD_CSV_Payment_History_Exporter' ) ) {

    class EDD_CSV_Payment_History_Exporter {

        private static $instance;

        private $page;

        /**
         * Get active instance
         *
         * @since       1.2.0
         * @access      public
         * @return      object self::$instance
         */
        public static function instance() {
            if( ! self::$instance ) {
                self::$instance = new EDD_CSV_Payment_History_Exporter();
                self::$instance->init();
            }

            return self::$instance;
        }


        /**
         * Run action and filter hooks
         *
         * @since       1.2.0
         * @access      private
         * @return      void
         */
        private function init() {
            if( version_compare( EDD_VERSION, '1.9.5', '<' ) ) {
                $this->page = 'tools.php?page=edd-product-export-import';
                add_action( 'edd_tools_before', array( $this, 'add_metabox' ) );
            } elseif( version_compare( EDD_VERSION, '1.9.4', '>' ) && version_compare( EDD_VERSION, '2.0', '<' ) ) {
                $this->page = 'edit.php?post_type=download&page=edd-tools';
                add_action( 'edd_tools_before', array( $this, 'add_metabox' ) );
            } else {
                $this->page = 'edit.php?post_type=download&page=edd-tools';
                add_action( 'edd_tools_import_export_after', array( $this, 'add_metabox' ) );
            }

            // Process export
            add_action( 'edd_export_payment_history_csv', array( $this, 'export' ) );
        }


        /**
         * Add metabox
         *
         * @since       1.2.0
         * @access      public
         * @return      void
         */
        public function add_metabox() {
            echo '<div class="postbox" id="edd-payment-history-export">';
            echo '<h3><span>' . __( 'Export Payment History to CSV', 'edd-csv-manager' ) . '</span></h3>';
            echo '<div class="inside">';
            echo '<p>' . __( 'Export the payment history from your Easy Digital Downloads site to a .csv file.', 'edd-csv-manager' ) . '</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . admin_url( $this->page ) . '">';
            echo '<p>';
            echo '<input type="hidden" name="edd_action" value="export_payment_history_csv" />';
            submit_button( __( 'Export', 'edd-csv-manager' ), 'secondary', 'submit', false );
            echo '</p>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }


        /**
         * Export payment history to a CSV file
         *
         * @since       1.2.0
         * @access      public
         * @return      void
         */
        public function export() {
            if( ! current_user_can( 'manage_options' ) )
                wp_die( __( 'You do not have permission to export data.', 'edd-csv-manager' ), __( 'Error', 'edd-csv-manager' ) );

            // Set CSV header row data
            $headers = array(
                'status',
                'post_date',
                'user_email',
                'first_name',
                'last_name',
                'currency',
                'price',
                'tax',
                'discount',
                'downloads'
            );

            $headers = apply_filters( 'edd_csv_export_payment_history_headers', $headers );

            $data[] = $headers;

            // Get all the things
            $payments = edd_get_payments();
            foreach( $payments as $payment ) {
                $status  = edd_get_payment_status( $payment, true );
                $payment = edd_get_payment_meta( $payment->ID );
                
                $post_date  = $payment['date'];
                $user_email = $payment['email'];
                $first_name = $payment['user_info']['first_name'];
                $last_name  = $payment['user_info']['last_name'];
                $currency   = $payment['currency'];
                $price      = html_entity_decode( edd_currency_filter( edd_format_amount( $payment['cart_details'][0]['price'] ) ) );
                $tax        = html_entity_decode( edd_currency_filter( edd_format_amount( $payment['cart_details'][0]['tax'] ) ) );
                $discount   = $payment['user_info']['discount'];
                
                $downloads = '';

                if( count( $payment['downloads'] ) == 1 ) {
                    $download = edd_get_download( $payment['downloads'][0]['id'] );
                    $downloads = $download->post_title;
                } elseif( count( $payment['downloads'] ) > 1 ) {
                    $downloads = array();

                    foreach( $payment['downloads'] as $download ) {
                        $download = edd_get_download( $download['id'] );
                        $downloads[] = $download->post_title;
                    }

                    $downloads = implode( '|', $downloads );
                }

                $row = array(
                    $status,
                    $post_date,
                    $user_email,
                    $first_name,
                    $last_name,
                    $currency,
                    $price,
                    $tax,
                    $discount,
                    $downloads
                );
                $row = apply_filters( 'edd_csv_export_payment_history_row', $row );

                $data[] = $row;
            }

            $this->set_csv_download_headers();

            // Output data to CSV
            $csv = fopen( 'php://output', 'w' );

            foreach( $data as $fields ) {
                fputcsv( $csv, $fields );
            }

            fclose( $csv );

            // Exit needed to prevent 'junk' in CSV output
            exit;
        }


        /**
         * Set headers for CSV export
         *
         * @since       1.2.0
         * @access      public
         * @return      void
         */
        public function set_csv_download_headers() {
            ignore_user_abort( true );

            if( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) set_time_limit( 0 );

            nocache_headers();

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . apply_filters( 'edd_payment_history_export_filename', 'edd-export-payment-history-' . date( 'm-d-y' ) ) . '.csv' );
            header( 'Expires: 0' );
        }
    }
}


function EDD_CSV_PAYMENT_HISTORY_EXPORTER() {
    return EDD_CSV_Payment_History_Exporter::instance();
}
