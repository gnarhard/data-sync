<?php

namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\Options;
use DataSync\Controllers\Posts;
use DataSync\Controllers\PostTypes;
use DataSync\Controllers\SyncedPosts;
use DataSync\Models\SyncedPost;

function display_synced_posts_table() {

	$source_options = Options::source()->get_data();

	$connected_sites_obj = new ConnectedSites();
	$connected_sites     = $connected_sites_obj->get_all()->data;

	$post_types = array_keys( $source_options->push_enabled_post_types );
	$posts      = Posts::get_wp_posts( $post_types, true );

	$enabled_post_type_site_data   = PostTypes::check_enabled_post_types_on_receiver();
	$status                        = '';
	$no_enabled_post_types_on_site = false;

	$synced_posts   = SyncedPost::get_all();
	$receiver_posts = array();

	foreach ( $connected_sites as $site ) {
		$receiver_posts_response = wp_remote_get( $site->url . '/wp-json/wp/v2/posts' );
		$receiver_posts[]        = array(
			'site_id' => $site->id,
			'posts'   => json_decode( wp_remote_retrieve_body( $receiver_posts_response ) ),
		);
	}

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

				$syndication_info = Posts::get_syndication_info( $post, $connected_sites, $receiver_posts );

				?>
                <tr data-id="<?php echo $post->ID ?>" id="synced_post-<?php echo $post->ID ?>">
                    <td><?php echo esc_html( $post->ID ); ?></td>
                    <td>
                        <a class="<?php echo $syndication_info->trash_class ?>"
                           href="/wp-admin/post.php?post=<?php echo $post->ID; ?>&action=edit"
                           target="_blank"><?php echo esc_html( $post->post_title ); ?></a>
                    </td>
                    <td><?php echo esc_html( ucfirst( $post->post_type ) ); ?></td>
                    <td class="wp_data_synced_post_status_icons"><?php echo $syndication_info->status; ?></td>
                    <td class="expand_post_details" data-id="<?php echo $post->ID ?>">+</td>
                </tr>
                <tr class="post_details" id="post-<?php echo $post->ID ?>">
                    <td class="post_detail_wrap" colspan="5">
                        <div class="source_details">
                            <h4>Source Info</h4>
							<?php echo $syndication_info->synced ?>
                        </div>
                        <div class="detail_wrap"><?php echo display_post_syndication_details( $syndication_info, $enabled_post_type_site_data, $connected_sites, $post ); ?></div>
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
}


function display_post_syndication_details( $syndication_info, $enabled_post_type_site_data, $connected_sites, $post ) {
	?>
    <div class="connected_site_info">
    <h4>Connected Site Info</h4>
	<?php
	foreach (
		$connected_sites

		as $index => $site
	) {

		$result = SyncedPost::get_where(
			array(
				'source_post_id'   => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ),
				'receiver_site_id' => (int) filter_var( $site->id, FILTER_SANITIZE_NUMBER_INT ),
			)
		);

		$connected_site_synced_post = ( ! empty( $result[0] ) ) ? $result[0] : false;

		?>

        <strong>Site ID: <?php echo $site->id ?> &middot; <?php echo $site->url ?></strong>
        <div class="details">
			<?php

			// CONNECTED SITES INFO
			$site_info = $enabled_post_type_site_data[ $index ];
			if ( $site->id === $site_info['site_id'] ) {

				$no_enabled_post_types_on_site = true;
				$post_meta                     = get_post_meta( $post->ID );
				$excluded_sites                = unserialize( $post_meta['_excluded_sites'][0] );

				if ( in_array( (int) $site->id, $excluded_sites ) ) {
					?><span class="none_enabled"><strong>This post is excluding this receiver.</strong></span><?php

					if ( (int) $site->id === (int) $post_meta['_canonical_site'][0] ) {
						?><span class="none_enabled"><strong>This post's canonical URL is pointing to a receiver that is excluded.</strong></span><?php
					}
				}

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

					?>
                    <span class="none_enabled"><strong>No enabled post types on this site.</strong></span><?php


					if ( (int) $site->id === (int) $post_meta['_canonical_site'][0] ) {
						?><span class="none_enabled"><strong>This post's canonical URL is pointing to this receiver that doesn't have any post types enabled. No syndication will happen and SEO errors will occur. Please enable post types on this receiver or change the canonical site of this post.</strong></span><?php
					}

				}
			}

			if ( ! empty( $connected_site_synced_post ) ) {

				echo '<span>Last syndication: ' . date( 'g:i:s A n/d/Y', strtotime( $connected_site_synced_post->date_modified ) ) . '</span>';

				if ( 0 !== (int) $connected_site_synced_post->diverged ) {

					$site_status = '<span>Status: <i class="dashicons dashicons-editor-unlink"></i></span>';

					if ( ( $syndication_info->source_version_edited ) && ( ( $syndication_info->receiver_version_edited[0] ) && ( (int) $connected_site_synced_post->receiver_site_id === (int) $syndication_info->receiver_version_edited[1] ) ) ) {
						echo '<span class="warning">Source AND receiver updated since last sync.</span>';
					} elseif ( ( $syndication_info->receiver_version_edited[0] ) && ( (int) $connected_site_synced_post->receiver_site_id === (int) $syndication_info->receiver_version_edited[1] ) ) {
						echo '<span class="warning">Receiver post was updated after the last sync.</span>';
					} elseif ( $syndication_info->source_version_edited ) {
						echo '<span class="warning">Source updated since last sync.</span>';
					}

					echo '<br>';
					echo '<button class="button danger_button overwrite_single_receiver" data-receiver-site-id="' . $syndication_info->synced_post->receiver_site_id . '" data-source-post-id="' . $syndication_info->synced_post->source_post_id . '">Overwrite this receiver</a>';

				} else {
					// SYNCED.
					$site_status = '<span>Status: <i class="dashicons dashicons-yes" title="Synced on this connected site."></i></span>';
				}


			} else if ( in_array( (int) $site->id, $excluded_sites ) ) {
				// NOT SYNCED ON PURPOSE BECAUSE OF EXCLUDED SITE.
				$site_status = '<span>Status: <i class="dashicons dashicons-yes" title="Synced on this connected site."></i></span>';
			} else {
				// NOT SYNCED.
				$site_status = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
				$site_status .= '<button class="button danger_button overwrite_single_receiver" data-receiver-site-id="' . $site->id . '" data-source-post-id="' . $post->ID . '">Overwrite this receiver</a>';
			}


			echo $site_status;

			?>
        </div>
        </div>
		<?php

	}
}