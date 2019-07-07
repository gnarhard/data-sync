<?php namespace DataSync;

/**
 * Dashboard widget that displays status of posts
 */
function status_widget() {

	include_once 'synced-posts-table.php';
	display_synced_posts_table();
	?>
	<div id="status_dashboard_button_wrap">
		<a id="bulk_data_push" href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></a>
		<button id="template_push"><?php _e( 'Push Template', 'data_sync' ); ?></button>
	</div>
	<div id="error_log_wrap">
		<a href="<?php echo admin_url( 'options-general.php?page=data-sync-options' ); ?>">Go to error log</a>
	</div>
	<?php
}
