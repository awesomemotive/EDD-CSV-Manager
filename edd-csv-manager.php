<?php
/*
 * Plugin Name:		Easy Digital Downloads - CSV Manager
 * Plugin URI:		http://easydigitaldownloads.com
 * Description:		Allows store owners to import products from or export products to a CSV file
 * Version:			1.0.0
 * Author:			Daniel J Griffiths
 * Author URI:		http://ghost1227.com
 *
 * @package			EDD CSV Manager
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_CSV_Manager' ) ) {

	class EDD_CSV_Manager {

		private static $instance;

		/**
		 * Get active instance
		 *
		 * @since		1.0.0
		 * @access		public
		 * @static
		 * @return		object self::$instance
		 */
		public static function instance() {
			if( !self::$instance ) {
				self::$instance = new EDD_CSV_Manager();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->exporter();
				self::$instance->importer();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @since		1.0.0
		 * @access		private
		 * @return		void
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
		 * @since		1.0.0
		 * @access		private
		 * @return		void
		 */
		private function includes() {
			require_once EDD_CSV_MANAGER_DIR . 'includes/functions.php';
			require_once EDD_CSV_MANAGER_DIR . 'includes/class.exporter.php';
			require_once EDD_CSV_MANAGER_DIR . 'includes/class.importer.php';
		}


		/**
		 * Load plugin language files
		 *
		 * @since		1.0.0
		 * @access		public
		 * @return		void
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
		 * Setup the exporter
		 *
		 * @since		1.0.0
		 * @access		private
		 * @return		void
		 */
		public function exporter() {
			$exporter = EDD_CSVE();
		}


		/**
		 * Setup the importer
		 *
		 * @since		1.0.0
		 * @access		private
		 * @return		void
		 */
		public function importer() {
			$importer = EDD_CSVI();
		}

	}
}


function EDD_CSV() {
	return EDD_CSV_Manager::instance();
}

// Off we go!
EDD_CSV();
