<?php
/*
Plugin Name:	Easy Digital Downloads - CSV Importer
Plugin URI:		http://easydigitaldownloads.com
Description:	Allows store owners to import products from a CSV file
Version:		1.0.0
Author:			Daniel J Griffiths
Author URI:		http://ghost1227.com
*/


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_CSV_Importer' ) ) {

	class EDD_CSV_Importer {

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
				self::$instance = new EDD_CSV_Importer();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
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
			if( !defined( 'EDD_CSV_IMPORTER_DIR' ) )
				define( 'EDD_CSV_IMPORTER_DIR', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' );

			// Plugin folder URL
			if( !defined( 'EDD_CSV_IMPORTER_URL' ) )
				define( 'EDD_CSV_IMPORTER_URL', plugin_dir_url( EDD_CSV_IMPORTER_DIR ) . basename( dirname( __FILE__ ) ) . '/' );

			// Plugin root file
			if( !defined( 'EDD_CSV_IMPORTER_FILE' ) )
				define( 'EDD_CSV_IMPORTER_FILE', __FILE__ );
		}


		/**
		 * Include required files
		 *
		 * @since		1.0.0
		 * @access		private
		 * @return		void
		 */
		private function includes() {
			require_once EDD_CSV_IMPORTER_DIR . 'includes/scripts.php';
			require_once EDD_CSV_IMPORTER_DIR . 'includes/admin-pages.php';
			require_once EDD_CSV_IMPORTER_DIR . 'includes/display-importer.php';
		}


		/**
		 * Load the plugin language files
		 *
		 * @since		1.0.0
		 * @access		public
		 * @return		void
		 */
		public function load_textdomain() {
			// Set filter for languages directory
			$lang_dir = dirname( plugin_basename( EDD_CSV_IMPORTER_FILE ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_csv_importer_languages_directory', $lang_dir );

			// WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-csv-importer' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-csv-importer', $locale );

			// Setup paths to current locale file
			$mofile_local = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-csv-importer/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-csv-importer folder
				load_textdomain( 'edd-csv-importer', $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-csv-importer/languages/ folder
				load_textdomain( 'edd-csv-importer', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-csv-importer', false, $lang_dir );
			}
		}
	}
}

function EDD_CSV() {
	return EDD_CSV_Importer::instance();
}

// Off we go!
EDD_CSV();
