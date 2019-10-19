<?php namespace DataSync;

use DataSync\Controllers\Logs;
use DataSync\Controllers\TemplateSync;

/**
 * Outputs HTML for settings page
 */
function data_sync_options_page() {

	?>
    <div class="wrap">
        <h2>DATA SYNC</h2>
        <div id="data_sync_tabs" class="hidden">
			<?php if ( '1' === get_option( 'source_site' ) ) { ?>
                <ul>
                    <li><a href="#syndicated_posts">Posts</a></li>
                    <li><a href="#templates">Templates</a></li>
                    <li><a href="#connected_sites">Connected Sites</a></li>
                    <li><a href="#enabled_post_types">Enabled Post Types</a></li>
                    <li><a href="#debug_log">Log</a></li>
                    <li><a href="#settings">Settings</a></li>
                </ul>
			<?php } else { ?>
                <h3>Settings</h3>
			<?php } ?>
			<?php if ( '1' === get_option( 'source_site' ) ) { ?>
                <div id="syndicated_posts">
                    <div id="syndicated_posts_wrap">
                        <div id="status_dashboard_button_wrap">
                            <button id="refresh_syndicated_posts"
                                    class="button button-secondary"><?php _e( 'Refresh', 'data_sync' ); ?></button>
                            <button id="bulk_data_push" class="button button-primary"><?php _e( 'Sync All', 'data_sync' ); ?></button>
                        </div>
                        <table id="wp_data_sync_status">
                            <thead>
                            <tr>
                                <th><?php _e( 'ID', 'data_sync' ); ?></th>
                                <th><?php _e( 'TITLE', 'data_sync' ); ?></th>
                                <th><?php _e( 'TYPE', 'data_sync' ); ?></th>
                                <th><?php _e( 'STATUS', 'data_sync' ); ?></th>
                                <th><?php _e( 'DETAILS', 'data_sync' ); ?></th>
                            </tr>
                            </thead>
                            <tbody id="syndicated_posts_data">
                            <tr class="loading_spinner">
                                <td colspan="5">
                                    <i class="dashicons dashicons-update"></i> Loading. . .</tr>
                                </td>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="templates">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                    <div id="templates_wrap"></div>
                </div>
                <div id="connected_sites">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                    <div id="connected_sites_wrap"></div>
                </div>
                <div id="enabled_post_types">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                    <div id="enabled_post_types_wrap"></div>
                </div>
                <div id="debug_log">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
					<?php
					if ( get_option( 'source_site' ) ) {
						?>
						<?php
						if ( '1' === get_option( 'debug' ) ) {
							?>
                            <div id="log_buttons_wrap">
                                <button id="delete_error_log" class="button button-warning">Purge log</button>
                                <button class="button button-secondary" id="refresh_error_log">Refresh log</button>
                            </div>
                            <div id="error_log">
								<?php include_once 'log.php'; ?>
								<?php echo display_log(); ?>
                            </div>
							<?php
						} else {
							?><span>Enable debugging on the settings page. Debugging decreases settings page performance.</span><?php
						}

					}
					?>
                </div>

			<?php } ?>
            <div id="settings" class="hidden">
                <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                <form method="POST" action="options.php">
					<?php
					settings_fields( 'data_sync_options' );
					do_settings_sections( 'data-sync-options' );
					submit_button();
					?>
                </form>
            </div>
        </div>

    </div>
	<?php
}
