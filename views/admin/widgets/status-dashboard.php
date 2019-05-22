<?php namespace DataSync;

/**
 * Dashboard widget that displays status of posts that haven't been synced
 */
function status_widget() {  ?>
	<table>
		<thead>
		</thead>
		<tr>
			<th><?php _e('Post', 'wp_data_sync'); ?></th>
			<th><?php _e('Type', 'wp_data_sync'); ?></th>
			<th><?php _e('Created', 'wp_data_sync'); ?></th>
		</tr>
	</table>
	<button id="bulk_data_push"><?php _e('Bulk Push', 'wp_data_sync'); ?></button>
	<button id="recent_data_push"><?php _e('Only Push New', 'wp_data_sync'); ?></button>
	<?php
}
