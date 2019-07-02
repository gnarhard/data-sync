<?php namespace DataSync;

use DataSync\Controllers\Log as Log;

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
			if ( get_option( 'source_site' ) ) {
				?>
				<h2>Error Log</h2>
				<a id="refresh_error_log">Refresh Log</a>
				<textarea id="error_log" style="height: 500px; width: 100%;"><?php echo esc_html( Log::get_log() ); ?></textarea>
				<?php
			}
			?>
		</form>
	</div>
	<?php
}
