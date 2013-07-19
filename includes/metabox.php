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
				<p>
					<input type="file" name="import_file"/>
				</p>
				<p>
					<input type="hidden" name="edd_action" value="upload_csv" />
					<?php wp_nonce_field( 'edd_import_nonce', 'edd_import_nonce' ); ?>
				</p>
			</form>
		</div>
	</div>
	<?php
}
add_action( 'edd_export_import_bottom', 'edd_csv_importer_metabox' );
