<?php namespace DataSync;

use DataSync\Controllers\Options;
use DataSync\Controllers\SyncedPosts;
use DataSync\Controllers\Posts;

/**
 * Dashboard widget that displays status of posts that haven't been synced
 */
function status_widget() {
  // TODO: add update failed section
  ?>
	<table id="wp_data_sync_status">
		<thead>
		</thead>
		<tr>
			<th><?php _e( 'ID', 'data_sync' ); ?></th>
			<th><?php _e( 'Post', 'data_sync' ); ?></th>
			<th><?php _e( 'Type', 'data_sync' ); ?></th>
			<th><?php _e( 'Created', 'data_sync' ); ?></th>
			<th><?php _e( 'Synced', 'data_sync' ); ?></th>
		</tr>
		<?php

		$receiver_options = (object) Options::receiver()->get_data();
		$posts = Posts::get( $receiver_options->enabled_post_types );
		foreach ( $posts as $post ) {
//			$filtered_post = SyncedPosts::filter( $post, $receiver_site_id );
//			if ( false !== $filtered_post ) {
//
//			}
			$post = $post[0];
			?>
			<tr>
				<td><?php echo $post->ID ?></td>
				<td><?php echo $post->post_title ?></td>
				<td><?php echo $post->post_type ?></td>
				<td><?php echo $post->post_date ?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<button id="bulk_data_push"><?php _e( 'Push All', 'data_sync' ); ?></button>
	<button id="recent_data_push"><?php _e( 'Push Unsynced', 'data_sync' ); ?></button>
	<button id="template_push"><?php _e( 'Push Template', 'data_sync' ); ?></button>
	<?php
}
