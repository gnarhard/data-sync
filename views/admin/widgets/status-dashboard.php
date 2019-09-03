<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\PostTypes;

/**
 * Dashboard widget that displays status of posts
 */
function status_widget() {

	include_once 'synced-posts-table.php';
	$status                      = '';
	$connected_sites_obj         = new ConnectedSites();
	$connected_sites             = $connected_sites_obj->get_all()->data;
	$connected_site_ids          = '';
	$i                           = 0;
	$number_of_sites_connected   = count( $connected_sites );
	$enabled_post_type_site_data = PostTypes::check_enabled_post_types_on_receiver();
	?>
    <div id="data_sync_status_tabs">
        <ul>
            <li><a href="#tabs-1">Posts</a></li>
            <li><a href="#tabs-2">Connected Sites</a></li>
        </ul>
        <div id="tabs-1">
			<?php display_synced_posts_table(); ?>
        </div>
        <div id="tabs-2">
            <table>
                <thead>
                <th>ID</th>
                <th>URL</th>
                <th>DETAILS</th>
                </thead>
                <tbody>

			<?php
			foreach ( $connected_sites as $index => $site ) {
				$i ++;
				if ( $i === $number_of_sites_connected ) {
					$connected_site_ids .= $site->id;
				} else {
					$connected_site_ids .= $site->id . ',';
				}

				$list_number = $index + 1;
				?>
                <tr>
                    <td><?php echo $site->id ?></td>
                    <td><?php echo $site->url ?></td>
                    <td>
                        <a class='reveal_connected_site_details' data-id="<?php echo $site->id ?>">Reveal</a>
                    </td>
                </tr>

                <?php

				if ( $site->id === $enabled_post_type_site_data[ $index ]['site_id'] ) {
					if ( ! empty( $enabled_post_type_site_data[ $index ]['enabled_post_types'] ) ) {
						?>
                        <tr class="connected_site_details" id="connected_site-<?php echo $site->id ?>">
                            <td colspan="3">
                                <span><strong>Enabled Post Types:</strong></span>
                                <ol>
                                    <?php
                                    foreach ( $enabled_post_type_site_data[ $index ]['enabled_post_types'] as $post_type ) {
                                        ?>
                                        <li><?php echo $post_type ?></li><?php
                                    }
                                    ?>
                                </ol>
                            </td>
                        </tr>
						<?php
					} else {
						$status .= '<br><span class="none_enabled">No enabled post types for receiver site ' . $site->id . '. </span>';
						?>
                        <tr class="connected_site_details" id="connected_site-<?php echo $site->id ?>">
                            <td colspan="3">
                                <span class="none_enabled"><strong>No enabled post types.</strong></span>
                            </td>
                        </tr>
                        <?php
					}
				}
                ?>
                <?php
			}
			?>

                </tbody>
            </table>
            <div id="connected_site_link_wrap">
                <a href="<?php echo admin_url( 'options-general.php?page=data-sync-options' ); ?>">Manage connected
                    sites</a>
            </div>
            <input type="hidden" id="sites_connected_info" data-ids="<?php echo $connected_site_ids ?>"/>
        </div>
    </div>

    <div id="status_dashboard_button_wrap">
		<?php
		if ( get_option( 'show_body_responses' ) ) {
			?>
            <button class="disabled" disabled
                    title="Please disable 'Show Body Responses' option in the settings to enable data push."
                    id="bulk_data_push"
                    href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></button><?php
		} else {
			?>
            <button id="bulk_data_push"
                    href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></button><?php
		}
		?>

        <button id="template_push"><?php _e( 'Push Template', 'data_sync' ); ?></button>
    </div>
    <div id="error_log_wrap">
        <a href="<?php echo admin_url( 'options-general.php?page=data-sync-options' ); ?>">Go to error log</a>
    </div>
    <div id="status_wrap"><?php echo $status ?></div>
	<?php
}
