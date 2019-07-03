<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\Options;
use DataSync\Controllers\SyncedPosts;
use DataSync\Controllers\Posts;
use DataSync\Models\SyncedPost;

/**
 * Dashboard widget that displays status of posts that haven't been synced
 */
function status_widget() {

	?>
	<table id="wp_data_sync_status">
		<thead>
		</thead>
		<tr>
			<th><?php _e( 'ID', 'data_sync' ); ?></th>
			<th><?php _e( 'Title', 'data_sync' ); ?></th>
			<th><?php _e( 'Type', 'data_sync' ); ?></th>
			<th><?php _e( 'Created', 'data_sync' ); ?></th>
			<th><?php _e( 'Synced', 'data_sync' ); ?></th>
		</tr>
		<?php
		$connected_sites_obj       = new ConnectedSites();
		$connected_sites           = $connected_sites_obj->get_all()->data;
		$number_of_sites_connected = count( $connected_sites );
		$source_options            = Options::source()->get_data();
		$posts                     = Posts::get( array_keys( $source_options->push_enabled_post_types ) );
		$sorted_posts              = clone( (object) array_reverse( (array) $posts) );

		foreach ( $sorted_posts as $post ) {

			if ( ! empty( $post ) ) {
				$post = $post[0];

				$time = strtotime( $post->post_date );

				$result = SyncedPost::get_where( [ 'source_post_id' => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ) ] );

				if ( count( $result ) === $number_of_sites_connected ) {
					$post_status = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
				} else if ( count( $result ) === 0 ) {
					$post_status = '<i class="dashicons dashicons-info" title="Partially synced. Some posts may have failed to sync with a connected site because the post type isn\'t enabled on the receiver or there was an error."></i>';
				} else {
					$post_status = '<i class="dashicons dashicons-warning" title="Not synced."></i>';
				}
				?>
				<tr>
					<td><?php echo $post->ID ?></td>
					<td><?php echo $post->post_title ?></td>
					<td><?php echo ucfirst( $post->post_type ); ?></td>
					<td><?php echo date( 'g:i a - F j, Y', $time ); ?></td>
					<td><?php echo $post_status; ?></td>
				</tr>
				<?php
			}

		}
		?>
	</table>
	<div id="status_dashboard_button_wrap">
		<a id="bulk_data_push" href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></a>
		<button id="template_push"><?php _e( 'Push Template', 'data_sync' ); ?></button>
	</div>
	<div id="error_log_wrap">
		<a href="<?php echo admin_url( 'options-general.php?page=data-sync-options' ); ?>">Go to error log</a>
	</div>
	<?php
}
