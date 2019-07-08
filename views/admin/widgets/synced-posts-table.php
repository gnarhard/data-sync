<?php

namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\Options;
use DataSync\Controllers\Posts;
use DataSync\Models\SyncedPost;

function display_synced_posts_table() {


	$connected_sites_obj       = new ConnectedSites();
	$connected_sites           = $connected_sites_obj->get_all()->data;
	$number_of_sites_connected = count( $connected_sites );
	$connected_site_ids        = '';
	$i                         = 0;

	foreach ( $connected_sites as $site ) {
		$i ++;
		if ( $i === $number_of_sites_connected ) {
			$connected_site_ids .= $site->id;
		} else {
			$connected_site_ids .= $site->id . ',';
		}
	}


	$source_options = Options::source()->get_data();
	$posts          = Posts::get( array_keys( $source_options->push_enabled_post_types ) );
	$sorted_posts   = clone( (object) array_reverse( (array) $posts ) );
	$array_of_posts = (array) $sorted_posts;

	?>
	<span id="sites_connected_info" data-ids="<?php echo $connected_site_ids?>">Sites connected: <?php echo $number_of_sites_connected ?></span>
	<table id="wp_data_sync_status">
		<thead>
		<tr>
			<th><?php _e( 'ID', 'data_sync' ); ?></th>
			<th><?php _e( 'Title', 'data_sync' ); ?></th>
			<th><?php _e( 'Type', 'data_sync' ); ?></th>
			<th><?php _e( 'Synced', 'data_sync' ); ?></th>
			<th><?php _e( 'Status', 'data_sync' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php

		if ( count( $array_of_posts ) ) {
			foreach ( $sorted_posts as $post ) {

				if ( ! empty( $post ) ) {
					$post   = $post[0];
					$result = SyncedPost::get_where( [ 'source_post_id' => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ) ] );

					if ( count( $result ) ) {
						$synced_post = (object) $result[0];
						$time        = strtotime( $synced_post->date_modified );
						$synced      = date( 'g:i a - F j, Y', $time );
					} else {
						$synced = 'Unsynced';
					}

					if ( count( $result ) === $number_of_sites_connected ) {
						$post_status = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
					} elseif ( 0 === count( $result ) ) {
						$post_status = '<i class="dashicons dashicons-warning" title="Not synced. Sync now or check error log if problem persists."></i>';
					} else {
						$post_status = '<i class="dashicons dashicons-info" title="Partially synced. Some posts may have failed to sync with a connected site because the post type isn\'t enabled on the receiver or there was an error."></i>';
					}

					?>
					<tr data-id="<?php echo $post->ID ?>" id="synced_post-<?php echo $post->ID ?>">
						<td><?php echo $post->ID ?></td>
						<td><?php echo $post->post_title ?></td>
						<td><?php echo ucfirst( $post->post_type ); ?></td>
						<td class="wp_data_synced_post_status_synced_time"><?php echo $synced ?></td>
						<td class="wp_data_synced_post_status_icons"><?php echo $post_status; ?></td>
					</tr>
					<?php
				}
			}
		} else {
			echo '<tr>No posts to sync</tr>';
		}

		?>
		</tbody>
	</table>
	<?php
}