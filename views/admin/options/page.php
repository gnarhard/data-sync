<?php namespace DataSync;

use DataSync\Controllers\Logs;

/**
 * Outputs HTML for settings page
 */
function data_sync_options_page() {

	require_once DATA_SYNC_PATH . 'views/admin/options/status-dashboard.php';
	require_once DATA_SYNC_PATH . 'views/admin/options/enabled-post-types-dashboard.php';

	?>
	<div id="feedback"></div>
	<div class="wrap">
		<h2>DATA SYNC</h2>
        <div id="data_sync_tabs">
            <ul>
                <li><a href="#syndicated_posts">Syndicated Posts</a></li>
                <li><a href="#connected_sites">Connected Sites</a></li>
                <li><a href="#enabled_post_types">Enabled Post Types</a></li>
                <li><a href="#settings">Settings</a></li>
                <li><a href="#debug_log">Log</a></li>
            </ul>
            <div id="syndicated_posts">
                <?php
                status_widget();
                ?>
            </div>
            <div id="connected_sites">
		        <?php
		        display_connected_sites();
		        ?>
            </div>
            <div id="enabled_post_types">
		        <?php
		        enabled_post_types_widget();
		        ?>
            </div>
            <div id="settings">
                <form method="POST" action="options.php">
		            <?php
		            settings_fields( 'data_sync_options' );
		            do_settings_sections( 'data-sync-options' );
		            submit_button();
		            ?>
                </form>
            </div>
            <div id="debug_log">
		        <?php
		        if ( get_option( 'source_site' ) ) {
			        if ( '1' === get_option ( 'debug' ) ) {
				        ?>
                        <h2>Log</h2>
                        <span id="refresh_error_log">Refresh log</span>
                        <div id="error_log">
					        <?php include_once 'log.php'; ?>
					        <?php echo display_log(); ?>
                        </div>
				        <?php
			        }
		        }
		        ?>
            </div>
        </div>

	</div>
	<?php
}
