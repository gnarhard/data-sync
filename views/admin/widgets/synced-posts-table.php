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
	$source_options            = Options::source()->get_data();
	$post_types                = array_keys( $source_options->push_enabled_post_types );
	$posts                     = Posts::get_wp_posts( $post_types );
	?>
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

		if ( count( $posts ) ) {

			foreach ( $posts as $post ) {

				$post_status                     = '';
				$post_meta                       = get_post_meta( $post->ID );
				$excluded_sites                  = unserialize( $post_meta['_excluded_sites'][0] );
				$result                          = SyncedPost::get_where( [ 'source_post_id' => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ) ] );
				$number_of_synced_posts_returned = count( $result );

//						print_r($post);

				if ( $number_of_synced_posts_returned ) {
					foreach ( $result as $synced_post ) {
						if ( true === (bool) $synced_post->diverged ) {
							$post_status = '<i class="dashicons dashicons-editor-unlink" title="A receiver post was updated after the last sync. Click to overwrite with source post." data-receiver-site-id="' . $synced_post->receiver_site_id . '" data-source-post-id="' . $synced_post->source_post_id . '"></i>';
						}
					}

					$synced_post               = (object) $result[0];
					$synced_post_modified_time = strtotime( $synced_post->date_modified );
					$source_post_modified_time = strtotime( $post->post_modified );

				} else {
					$synced = 'Unsynced';
				}

				if ( '' === $post_status ) {
					if ( count( $result ) === $number_of_sites_connected ) {
						$post_status = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
					} elseif ( 0 === count( $result ) ) {
						$post_status = '<i class="dashicons dashicons-warning" title="Not synced. Sync now or check error log if problem persists."></i>';
					} else {

						$amount_of_sites_synced = $number_of_sites_connected - count( $excluded_sites );

						if ( $amount_of_sites_synced === $number_of_synced_posts_returned ) {
							$post_status = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
						} else {
							$post_status = '<i class="dashicons dashicons-info" title="Partially synced. Some posts may have failed to sync with a connected site because the post type isn\'t enabled on the receiver or there was an error."></i>';
						}
					}
				}

				if ( $source_post_modified_time > $synced_post_modified_time ) {
					$synced = 'Source updated since last sync. <a class="">Push Now (doesnt work yet but would you like it to?).</a>';
					$post_status = '<i class="dashicons dashicons-warning" title="Not synced. Sync now or check error log if problem persists."></i>';
				} else {
					$synced = date( 'g:i:s A n/d/Y', $synced_post_modified_time );
				}

				?>
                <tr data-id="<?php echo $post->ID ?>" id="synced_post-<?php echo $post->ID ?>">
                    <td><?php echo esc_html( $post->ID ); ?></td>
                    <td><a href="/wp-admin/post.php?post=<?php echo $post->ID;?>&action=edit" target="_blank"><?php echo esc_html( $post->post_title ); ?></a></td>
                    <td><?php echo esc_html( ucfirst( $post->post_type ) ); ?></td>
                    <td class="wp_data_synced_post_status_synced_time"><?php echo esc_html( $synced ); ?></td>
                    <td class="wp_data_synced_post_status_icons"><?php echo $post_status; ?></td>
                </tr>
				<?php

			}
		} else {
			echo '<tr>No posts to sync</tr>';
		}

		?>
        </tbody>
    </table>
	<?php
}