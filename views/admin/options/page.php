<?php namespace DataSync;

use DataSync\Controllers\Error as Error;

/**
 * Outputs HTML for settings page
 */
function data_sync_options_page() {
	?>
	<div id="feedback"></div>
	<div class="wrap">
		<h2>Data Sync Settings</h2>
		<form method="POST" action="options.php">
			<?php
			settings_fields( 'data_sync_options' );
			do_settings_sections( 'data-sync-options' );
			submit_button();
			$error = new Error();
			?>
			<h2>Error Log</h2>
			<textarea class="error_log" style="height: 500px; width: 100%;"><?php echo esc_html( $error->get_log() ); ?></textarea>
		</form>
	</div>
	<?php
}
