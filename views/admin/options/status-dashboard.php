<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;

/**
 * Dashboard widget that displays status of posts
 */
function display_syndicated_posts() {

	include_once 'synced-posts-table.php';

	$status = display_syndicated_posts_table();
	?>

    <div id="status_dashboard_button_wrap">
		<?php
		if ( get_option( 'show_body_responses' ) ) {
			?>
            <button class="disabled" disabled
                    title="Please disable 'Show Body Responses' option in the settings to enable data push."
                    id="bulk_data_push"
                    href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></button><?php
		} else {
			?>
            <button id="bulk_data_push" class="button button-primary"
                    href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></button><?php
		}
		?>

    </div>
	<?php
}
