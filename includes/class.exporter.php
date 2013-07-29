<?php
/**
 * CSV Product Exporter
 *
 * @since       1.0.0
 * @package     EDD CSV Manager
 * @subpackage  Exporter
 * @copyright   Copyright (c) 2013, Daniel J Griffiths
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_CSV_Exporter' ) ) {

    class EDD_CSV_Exporter {

        private static $instance;

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
                self::$instance = new EDD_CSV_Exporter();
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
            // Add metabox
            add_action( 'edd_export_import_top', array( $this, 'add_metabox' ) );

            // Process export
            add_action( 'edd_export_csv', array( $this, 'export' ) );
        }


        /**
         * Add metabox
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function add_metabox() {
            echo '<div class="postbox">';
            echo '<h3><span>' . __( 'Export Products to CSV', 'edd-csv-manager' ) . '</span></h3>';
            echo '<div class="inside">';
            echo '<p>' . __( 'Export products from your Easy Digital Downloads site to a .csv file.', 'edd-csv-manager' ) . '</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . admin_url( 'tools.php?page=edd-settings-export-import' ) . '">';

            echo '<p>';
            echo '<input type="hidden" name="edd_action" value="export_csv" />';
            submit_button( __( 'Export', 'edd-csv-manager' ), 'secondary', 'submit', false );
            echo '</p>';

            echo '</form>';
            echo '</div>';
            echo '</div>';
        }


        /**
         * Export products to a CSV file
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function export() {
            if( !current_user_can( 'manage_options' ) )
                wp_die( __( 'You do not have permission to export data.', 'edd-csv-manager' ), __( 'Error', 'edd-csv-manager' ) );

            $this->set_download_headers();

            // Set CSV header row data
            $headers = array(
                'post_id',
                'post_name',
                'post_date',
                'post_author',
                'post_title',
                'post_content',
                'post_excerpt',
                'post_status',
                'categories',
                'tags',
                '_edd_price',
                '_edd_files',
                '_edd_download_limit',
                '_edd_button_behavior',
                '_edd_hide_purchase_link',
                '_edd_images'
            );

            $headers = apply_filters( 'edd_csv_export_headers', $headers );

            $data[] = $headers;

            // Get our list of downloads!
            $downloads = get_posts( array( 'post_type' => 'download', 'posts_per_page' => -1 ) );

            foreach( $downloads as $download ) {
                $post_id        = $download->ID;
                $post_name      = $download->post_name;
                $post_date      = $download->post_date;
                $post_author    = $download->post_author;
                $post_title     = $download->post_title;
                $post_content   = $download->post_content;
                $post_excerpt   = $download->post_excerpt;
                $post_status    = $download->post_status;

                $categories_array = wp_get_object_terms( $download->ID, 'download_category', array( 'fields' => 'names' ) );
                $categories = '';

                if( count( $categories_array ) == 1 ) {
                    $categories = $categories_array[0];
                } elseif( count( $categories_array ) > 1 ) {
                    $categories = implode( '|', $categories_array );
                }

                $tags_array = wp_get_object_terms( $download->ID, 'download_tag', array( 'fields' => 'names' ) );
                $tags = '';

                if( count( $tags_array ) == 1 ) {
                    $tags = $tags_array[0];
                } elseif( count( $tags_array ) > 1 ) {
                    $tags = implode( '|', $tags_array );
                }

                $price      = get_post_meta( $download->ID, 'edd_price', true );

                $files_array = get_post_meta( $download->ID, 'edd_download_files' );
                $files = '';
                $download_array = array();

                if( count( $files_array[0] ) == 1 ) {
                    $files = basename( $files_array[0][0]['file'] );
                    $download_array[] = $files_array[0][0]['file'];
                } elseif( count( $files_array[0] ) > 1 ) {
                    foreach( $files_array[0] as $file ) {
                        $file_array[] = basename( $file['file'] );
                        $download_array[] = $file['file'];
                    }
                    $files = implode( '|', $file_array );
                }

                $download_limit     = get_post_meta( $download->ID, '_edd_download_limit', true );
                $button_behavior    = get_post_meta( $download->ID, '_edd_button_behavior', true );
                $hide_purchase_link = get_post_meta( $download->ID, '_edd_hide_purchase_link', true );

                $image_id           = get_post_thumbnail_id( $download->ID );
                $image_details      = wp_get_attachment_image_src( $image_id );

                $row = array(
                    $post_id,
                    $post_name,
                    $post_date,
                    $post_author,
                    $post_title,
                    $post_content,
                    $post_excerpt,
                    $post_status,
                    $categories,
                    $tags,
                    $price,
                    $files,
                    $download_limit,
                    $button_behavior,
                    $hide_purchase_link,
                    $image_details[0]
                );
                $row = apply_filters( 'edd_csv_export_row', $row );

                $data[] = $row;
            }

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
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function set_download_headers() {
            ignore_user_abort( true );

            if( !edd_is_func_disabled( 'set_time_limit' ) && !ini_get( 'safe_mode' ) ) set_time_limit( 0 );

            nocache_headers();

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=edd-export-products-' . date( 'm-d-y' ) . '.csv' );
            header( 'Expires: 0' );
        }
    }
}


function EDD_CSVE() {
    return EDD_CSV_Exporter::instance();
}
