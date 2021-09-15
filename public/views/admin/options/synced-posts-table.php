<?php

namespace DataSync;

use DataSync\Controllers\Options;
use DataSync\Controllers\Posts;
use DataSync\Controllers\PostTypes;
use DataSync\Controllers\SyncedPosts;
use DataSync\Models\ConnectedSite;
use DataSync\Models\SyncedPost;

function display_post( $post, $connected_sites, $receiver_data ) {

	ob_start();

	$syndication_info = Posts::get_syndication_info_of_post( $post, $connected_sites, $receiver_data );
	$post_type_obj    = get_post_type_object( $post->post_type );
	?>
    <tr class="top_level_post_detail" data-id="<?php echo $post->ID ?>"
        id="synced_post-<?php echo $post->ID ?>">
        <td><?php echo esc_html( $post->ID ); ?></td>
        <td>
            <a class="<?php echo $syndication_info->trash_class ?>"
               href="/wp-admin/post.php?post=<?php echo $post->ID; ?>&action=edit"
               target="_blank"><?php echo esc_html( $post->post_title ); ?></a>
        </td>

        <td><?php echo esc_html( $post_type_obj->label ); ?></td>
        <td class="wp_data_synced_post_status_icons"><?php echo $syndication_info->icon; ?></td>
        <td class="expand_post_details noselect" data-id="<?php echo $post->ID ?>">+</td>
    </tr>
    <tr class="post_details" id="post-<?php echo $post->ID ?>">
        <td class="post_detail_wrap" colspan="5">
            <div class="source_details">
                <h4>Source Info</h4>
				<?php echo $syndication_info->source_message ?>
            </div>
            <div class="detail_wrap"><?php echo display_post_syndication_details_per_site( $syndication_info,
					$connected_sites, $post, $receiver_data ); ?></div>
        </td>
    </tr>
	<?php
	ob_get_contents();
}


function display_post_syndication_details_per_site( $syndication_info, $connected_sites, $post, $receiver_data ) {
	?>
    <div class="connected_site_info">
    <h4>Connected Site Info</h4>
	<?php

	foreach ( $connected_sites as $index => $site ) {
		$result = SyncedPost::get_where( array(
			'source_post_id'   => (int) filter_var( $post->ID, FILTER_SANITIZE_NUMBER_INT ),
			'receiver_site_id' => (int) filter_var( $site->id, FILTER_SANITIZE_NUMBER_INT ),
		) );

		$connected_site_sync_date   = strtotime( $site->sync_start );
		$source_modified_date       = strtotime( $post->post_modified_gmt );
		$connected_site_synced_post = ( ! empty( $result[0] ) ) ? $result[0] : false; ?>

        <strong>Site ID: <?php echo $site->id ?> &middot; <?php echo $site->url ?></strong>
        <div class="details">
			<?php

			$post_meta      = get_post_meta( $post->ID );
			$excluded_sites = ( '' == get_post_meta( $post->ID, '_excluded_sites',
					true ) ) ? [ 0 => 0 ] : get_post_meta( $post->ID, '_excluded_sites', true );
			$canonical_site = ( '' == get_post_meta( $post->ID, '_canonical_site',
					true ) ) ? 0 : get_post_meta( $post->ID, '_canonical_site', true );

			if ( in_array( (int) $site->id, $excluded_sites ) ) {
				?><span class="none_enabled"><strong>Receiver excluded in post.</strong></span><?php

				if ( (int) $site->id === (int) $canonical_site ) {
					?><span class="none_enabled"><strong>This post's canonical URL is pointing to a receiver that is excluded.</strong></span><?php
				}
			}

			foreach ( $receiver_data as $receiver ) {
				if ( (int) $site->id === $receiver->site_id ) {
					$enabled_post_types = $receiver->enabled_post_types;
					break;
				}
			}

			if ( empty( $enabled_post_types ) ) {

				?><span class="none_enabled"><strong>No enabled post types on this site.</strong></span><?php

				if ( (int) $site->id === (int) $canonical_site ) {
					?><span class="none_enabled"><strong>This post's canonical URL is pointing to this receiver that doesn't have any post types enabled. No syndication will happen and SEO errors will occur. Please enable post types on this receiver or change the canonical site of this post.</strong></span><?php
				}

			}

			if ( ! empty( $connected_site_synced_post ) ) {

				$local_timestamp = date( 'g:i:s a n/d/Y',
					strtotime( $connected_site_synced_post->date_modified ) + ( (int) get_option( 'gmt_offset' ) * 3600 ) );

				echo '<span>Last syndication: ' . $local_timestamp . '</span>';

				// NEED TO GET PER-SITE DATA. CAN'T RELY ON DATA FROM $syndication_info BECAUSE THAT IS POST-SPECIFIC, NOT SITE AND POST SPECIFIC.
				$synced_post_modified_time = strtotime( $connected_site_synced_post->date_modified );
				$source_post_modified_time = strtotime( $post->post_modified_gmt );
				$receiver_post             = Posts::find_receiver_post( $receiver_data,
					$connected_site_synced_post->receiver_site_id, $connected_site_synced_post->receiver_post_id );
				if ( null === $receiver_post ) {
					if ( $source_post_modified_time > $synced_post_modified_time ) {
						$sync_status = 'diverged';
					}
				} else {
					$receiver_modified_time = strtotime( $receiver_post->post_modified_gmt );

					if ( $receiver_modified_time > $synced_post_modified_time ) {
						$sync_status = 'diverged';
					} elseif ( $source_post_modified_time > $synced_post_modified_time ) {
						$sync_status = 'diverged';
					} elseif ( $synced_post_modified_time >= $receiver_modified_time ) {
						$sync_status = 'synced';
					}
				}

				if ( 'diverged' === $sync_status ) {
					$site_status_icon = '<span>Status: <i class="dashicons dashicons-editor-unlink"></i></span>';

					if ( ( $syndication_info->source_version_edited ) && ( ( $syndication_info->receiver_version_edited[0] ) && ( (int) $connected_site_synced_post->receiver_site_id === (int) $syndication_info->receiver_version_edited[1] ) ) ) {
						echo '<span class="warning">Source AND receiver updated since last sync.</span>';
					} elseif ( ( $syndication_info->receiver_version_edited[0] ) && ( (int) $connected_site_synced_post->receiver_site_id === (int) $syndication_info->receiver_version_edited[1] ) ) {
						echo '<span class="warning">Receiver post was updated after the last sync.</span>';
					}

					echo '<br>';
					echo '<button class="button danger_button overwrite_single_receiver" id="overwrite_single_receiver_' . $post->ID . '_' . $connected_site_synced_post->receiver_site_id . '" data-receiver-site-id="' . $connected_site_synced_post->receiver_site_id . '" data-source-post-id="' . $connected_site_synced_post->source_post_id . '">Overwrite this receiver</button>';
				} else {
					// SYNCED.
					$site_status_icon = '<span>Status: <i class="dashicons dashicons-yes" title="Synced on this connected site."></i></span>';
				}
			} elseif ( in_array( (int) $site->id, $excluded_sites ) ) {
				// NOT SYNCED ON PURPOSE BECAUSE OF EXCLUDED SITE.
				echo '<span>Last syndication: Never.';
				$site_status_icon = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
			} elseif ( $source_modified_date < $connected_site_sync_date ) {
				// NOT SYNCED ON PURPOSE BECAUSE OF SYNC START DATE.
				echo '<span>Last syndication: Never.';
				echo '<span class="warning">This post hasn\'t been modified after this site\'s sync start date.</span>';
				$site_status_icon = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
			} else {
				// NOT SYNCED.
				echo '<span>Last syndication: Never.';
				$site_status_icon = '<span>Status: <i class="dashicons dashicons-warning warning" title="Not synced."></i></span>';
				$site_status_icon .= '<button class="button danger_button overwrite_single_receiver" id="overwrite_single_receiver_' . $post->ID . '_' . $site->id . '" data-receiver-site-id="' . $site->id . '" data-source-post-id="' . $post->ID . '">Overwrite this receiver</button>';
			}


			echo $site_status_icon;

			?>
        </div>
        </div>
		<?php
	}
}
