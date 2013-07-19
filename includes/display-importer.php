<?php
/**
 * Importer page
 *
 * @package		Easy Digital Downloads - CSV Importer
 * @subpackage	Importer Page
 * @copyright	Copyright (c) 2013, Daniel J Griffiths
 * @license		http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since		1.0.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Importer page
 *
 * @since		1.0.0
 * @return		void
 */
function edd_csv_importer_page() {

	$wp_upload_dir = wp_upload_dir();
	$max_filesize = ini_get( 'upload_max_filesize' );
	$max_filesize_k = return_bytes( $max_filesize );

	ob_start();
	?>
	<div class="wrap">

		<div id="icon-tools" class="icon32"><br/></div>

		<h2><?php _e( 'Easy Digital Downloads - CSV Importer', 'edd-csv-importer' ); ?></h2>

		<div id="edd-csv-importer-sidebar">
			<h3><?php _e( 'Welcome to the EDD CSV Importer!', 'edd-csv-importer' ); ?></h3>
			<hr />
			<h4><?php _e( 'Getting Started', 'edd-csv-importer' ); ?></h4>
			<p><?php _e( 'Welcome! If this is your first time running the CSV Importer, please take a moment to read through this tutorial and familiarize yourself with the system.', 'edd-csv-importer' ); ?></p>
			<p><?php _e( 'Upon activation of this plugin, a new directory should have been created at the following location:' ); ?></p>
			<p class="edd-csv-importer-inset"><?php echo $wp_upload_dir['basedir'] . '/edd-csv-importer-uploads'; ?></p>
		</div>

		<div id="edd-csv-importer-content">
			<p><?php _e( 'Upload your Easy Digital Downloads products and we\'ll import them into this site.', 'edd-csv-importer' ); ?></p>

			<form method="post" enctype="multipart/form-data" onsubmit="return checkSize(<?php echo $max_filesize_k; ?>)">

				<p><?php _e( 'Choose a CSV (.csv, .xls, .xlsx) file to upload, then click Upload file and import.', 'edd-csv-importer' ); ?></p>

				<p>
					<label for="upload">
						<?php echo sprintf( __( 'Choose a file from your computer: (Maximum size: %1$s)', 'edd-csv-importer' ), $max_filesize ); ?>
					</label>
					<input type="file" id="upload" name="upload" />

					<input type="hidden" name="edd-action" value="upload_csv" />
				</p>

				<p><input type="submit" class="button-secondary action" value="<?php _e( 'Upload File', 'edd-csv-importer' ); ?>" /></p>
		
			</form>

		</div>
	</div>

	<?php
	echo ob_get_clean();
}


