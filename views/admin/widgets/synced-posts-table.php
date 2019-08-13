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
	$posts                     = Posts::get( array_keys( $source_options->push_enabled_post_types ) );
//	print_r($posts);
	$sorted_posts   = clone( (object) array_reverse( (array) $posts ) );
	$array_of_posts = (array) $sorted_posts;
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

		if ( count( $array_of_posts ) ) {
			foreach ( $sorted_posts as $post_types ) {

				if ( ! empty( $post_types ) ) {

					foreach ( $post_types as $post ) {

						$post_status                     = '';
						$post_meta                       = get_post_meta( $post->ID );
						$excluded_sites                  = unserialize( $post_meta['_excluded_sites'][0] );
						$result                          = SyncedPost::get_where( [ 'source_post_id' => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ) ] );
						$number_of_synced_posts_returned = count( $result );

						if ( $number_of_synced_posts_returned ) {
							foreach ( $result as $synced_post ) {
								if ( true === (bool) $synced_post->diverged ) {
									$post_status = '<i class="dashicons dashicons-editor-unlink" title="A receiver post was updated after the last sync. Click to overwrite with source post." data-receiver-site-id="' . $synced_post->receiver_site_id . '" data-source-post-id="' . $synced_post->source_post_id . '"></i>';
								}
							}

							$synced_post = (object) $result[0];
							$time        = strtotime( $synced_post->date_modified );
							$synced      = date( 'm/d/Y g:i:s a', $time );

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

						?>
                        <tr data-id="<?php echo $post->ID ?>" id="synced_post-<?php echo $post->ID ?>">
                            <td><?php echo esc_html( $post->ID ); ?></td>
                            <td><?php echo esc_html( $post->post_title ); ?></td>
                            <td><?php echo esc_html( ucfirst( $post->post_type ) ); ?></td>
                            <td class="wp_data_synced_post_status_synced_time"><?php echo esc_html( $synced ); ?></td>
                            <td class="wp_data_synced_post_status_icons"><?php echo $post_status; ?></td>
                        </tr>
						<?php

					}

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