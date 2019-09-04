<?php

namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\Options;
use DataSync\Controllers\Posts;
use DataSync\Controllers\PostTypes;
use DataSync\Models\SyncedPost;
use DataSync\Controllers\Logs;

function display_synced_posts_table() {

	$connected_sites_obj           = new ConnectedSites();
	$connected_sites               = $connected_sites_obj->get_all()->data;
	$number_of_sites_connected     = count( $connected_sites );
	$source_options                = Options::source()->get_data();
	$post_types                    = array_keys( $source_options->push_enabled_post_types );
	$posts                         = Posts::get_wp_posts( $post_types, true );
	$enabled_post_type_site_data   = PostTypes::check_enabled_post_types_on_receiver();
	$status                        = '';
	$no_enabled_post_types_on_site = false;
	?>
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
        <tbody>
		<?php

		if ( count( $posts ) ) {

			foreach ( $posts as $post ) {

				$post_status                     = '';
				$post_meta                       = get_post_meta( $post->ID );
				$excluded_sites                  = unserialize( $post_meta['_excluded_sites'][0] );
				$result                          = SyncedPost::get_where( [ 'source_post_id' => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ) ] );
				$number_of_synced_posts_returned = count( $result );
				$trash_class                     = "";
				$source_version_edited           = false;

				if ( $number_of_synced_posts_returned ) {
					foreach ( $result as $synced_post ) {
						if ( true === (bool) $synced_post->diverged ) {
							$post_status = '<i class="dashicons dashicons-editor-unlink" title="A receiver post was updated after the last sync. Click to overwrite with source post." data-receiver-site-id="' . $synced_post->receiver_site_id . '" data-source-post-id="' . $synced_post->source_post_id . '"></i>';
						}
					}

					$synced_post               = (object) $result[0];
					$synced_post_modified_time = strtotime( $synced_post->date_modified );
					$source_post_modified_time = strtotime( $post->post_modified );

					if ( $source_post_modified_time > $synced_post_modified_time ) {
						$source_version_edited = true;
						$synced                = '<span class="warning">Source updated since last sync.</span>';
						$synced                .= '<button class="button danger_button push_post_now" data-receiver-site-id="' . $synced_post->receiver_site_id . '" data-source-post-id="' . $synced_post->source_post_id . '">Overwrite all receivers</button></span>';
						$post_status           = '<i class="dashicons dashicons-warning warning" title="Not synced. Sync now or check error log if problem persists."></i>';
					} else {
						$synced = date( 'g:i:s A n/d/Y', $synced_post_modified_time );
					}

				} else {
					$synced = 'Unsynced';
				}

				if ( 'trash' === $post->post_status ) {
					$post_status = '<i class="dashicons dashicons-trash" title="Trashed at source but still live on receivers. To delete on receivers, delete permanently at source."></i>';
					$trash_class = "trashed";
				}

				if ( '' === $post_status ) {
					if ( count( $result ) === $number_of_sites_connected ) {
						$post_status = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
					} elseif ( 0 === count( $result ) ) {
						$post_status = '<i class="dashicons dashicons-warning warning" title="Not synced. Sync now or check error log if problem persists."></i>';
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
                    <td>
                        <a class="<?php echo $trash_class ?>"
                           href="/wp-admin/post.php?post=<?php echo $post->ID; ?>&action=edit"
                           target="_blank"><?php echo esc_html( $post->post_title ); ?></a>
                    </td>
                    <td><?php echo esc_html( ucfirst( $post->post_type ) ); ?></td>
                    <td class="wp_data_synced_post_status_icons"><?php echo $post_status; ?></td>
                    <td class="expand_post_details" data-id="<?php echo $post->ID ?>">+</td>
                </tr>
                <tr class="post_details" id="post-<?php echo $post->ID ?>">
                    <td class="post_detail_wrap" colspan="5">
                        <div class="source_details">
                            <h4>Source Info</h4>
							<?php echo $synced ?>
                        </div>
                        <div class="connected_site_info">
                            <h4>Connected Site Info</h4>
							<?php
							foreach ( $connected_sites as $index => $site ) {
								?>
                                <div class="detail_wrap">
                                    <strong>Site ID: <?php echo $site->id ?></strong>
                                    <span><?php echo $site->url ?></span>
                                    <div class="details">
										<?php
										$result                     = SyncedPost::get_where(
											array(
												'source_post_id'   => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ),
												'receiver_site_id' => (int) filter_var( $site->id, FILTER_SANITIZE_NUMBER_INT ),
											)
										);
										$connected_site_synced_post = $result[0];
										if ( ! empty( $connected_site_synced_post ) ) {
											echo '<span>Last syndication: ' . date( 'g:i:s A n/d/Y', strtotime( $connected_site_synced_post->date_modified ) ) . '</span>';
											if ( 0 !== (int) $connected_site_synced_post->diverged ) {
												if ( $source_version_edited ) {
													echo '<span class="warning">Source AND receiver updated since last sync.</span>';
													echo '<br>';
													echo '<button class="button danger_button push_post_now" data-receiver-site-id="' . $synced_post->receiver_site_id . '" data-source-post-id="' . $synced_post->source_post_id . '" data-connected-site-id="' . $site->id . '">Overwrite receiver</a>';
												} else {
													echo '<span class="warning">Receiver post was updated after the last sync.</span>';
													echo '<br>';
													echo '<button class="button danger_button push_post_now" data-receiver-site-id="' . $synced_post->receiver_site_id . '" data-source-post-id="' . $synced_post->source_post_id . '" data-connected-site-id="' . $site->id . '">Overwrite receiver</a>';
												}
											}
										}

										// CONNECTED SITES INFO
										$site_info = $enabled_post_type_site_data[ $index ];
										if ( $site->id === $site_info['site_id'] ) {
											if ( ! empty( $site_info['enabled_post_types'] ) ) {
												?>
                                                <span><strong>Enabled Post Types:</strong></span>
                                                <ol>
													<?php
													foreach ( $site_info['enabled_post_types'] as $post_type ) {
														?>
                                                        <li><?php echo $post_type ?></li><?php
													}
													?>
                                                </ol>
												<?php
											} else {
												$no_enabled_post_types_on_site = true;
												?>
                                                <span class="none_enabled"><strong>No enabled post types on this site. Syndication will fail.</strong></span>
												<?php
											}
										}

										?>
                                    </div>
                                </div>
								<?php

							}
							?>
                        </div>
                    </td>
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

	if ( $no_enabled_post_types_on_site ) {
		$status .= '<br><span class="none_enabled">No enabled post types for some receiver sites. View post details for more information.</span>';
	}

	return $status;
}