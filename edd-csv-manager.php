<?php
/*
Plugin Name:     Easy Digital Downloads - CSV Manager
Plugin URI:      https://easydigitaldownloads.com/extensions/edd-csv-manager/
Description:     Allows store owners to import products from or export products to a CSV file
Version:         1.1.4
Author:          Daniel J Griffiths and Pippin Williamson
Author URI:      https://easydigitaldownloads.com
@package         EDD CSV Manager
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_CSV_Manager' ) ) {

    class EDD_CSV_Manager {

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
                self::$instance = new EDD_CSV_Manager();
            }

            return self::$instance;
		}


		/**
		 * Class constructor
		 *
		 * @since		1.0.7
		 * @access		public
		 * @return		void
		 */
		public function __construct() {
            $this->setup_constants();
            $this->includes();
            $this->load_textdomain();
            $this->product_exporter();
            $this->product_importer();
            $this->payment_history_importer();
		}


        /**
         * Setup plugin constants
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function setup_constants() {
            // Plugin folder path
            if( !defined( 'EDD_CSV_MANAGER_DIR' ) )
                define( 'EDD_CSV_MANAGER_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' );

            // Plugin folder URL
            if( !defined( 'EDD_CSV_MANAGER_URL' ) )
                define( 'EDD_CSV_MANAGER_URL', plugin_dir_url( EDD_CSV_MANAGER_DIR ) . basename( dirname( __FILE__ ) ) . '/' );

            // Plugin root file
            if( !defined( 'EDD_CSV_MANAGER_FILE' ) )
                define( 'EDD_CSV_MANAGER_FILE', __FILE__ );
        }


        /**
         * Include required files
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function includes() {
            require_once EDD_CSV_MANAGER_DIR . 'includes/functions.php';
            require_once EDD_CSV_MANAGER_DIR . 'includes/class.product-exporter.php';
            require_once EDD_CSV_MANAGER_DIR . 'includes/class.product-importer.php';
            require_once EDD_CSV_MANAGER_DIR . 'includes/class.payment-history-importer.php';
        }


        /**
         * Load plugin language files
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for languages directory
            $lang_dir = dirname( plugin_basename( EDD_CSV_MANAGER_FILE ) ) . '/languages/';
            $lang_dir = apply_filters( 'edd_csv_manager_languages_directory', $lang_dir );

            // WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-csv-manager' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-csv-manager', $locale );

            // Setup paths to current locale file
            $mofile_local = $lang_dir . $mofile;
            $mofile_global = WP_LANG_DIR . '/edd-csv-manager/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-csv-manager folder
                load_textdomain( 'edd-csv-manager', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-csv-manager/languages/ folder
                load_textdomain( 'edd-csv-manager', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-csv-manager', false, $lang_dir );
            }
        }


        /**
         * Setup the product exporter
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        public function product_exporter() {
            $exporter = EDD_CSV_PRODUCT_EXPORTER();
        }


        /**
         * Setup the product importer
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        public function product_importer() {
            $importer = EDD_CSV_PRODUCT_IMPORTER();
        }

        /**
         * Setup the payment history importer
         *
         * @since       1.1.0
         * @access      private
         * @return      void
         */
        public function payment_history_importer() {
            $importer = EDD_CSV_PAYMENT_HISTORY_IMPORTER();
        }
    }
}


function edd_csv_manager_load() {
	// Make sure EDD is active
	if( !class_exists( 'Easy_Digital_Downloads' ) ) return;

	$edd_csv_manager = new EDD_CSV_Manager();
}
add_action( 'plugins_loaded', 'edd_csv_manager_load' );