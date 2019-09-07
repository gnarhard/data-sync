<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\PostTypes;

/**
 * Dashboard widget that displays which post types are enabled to be synced
 */
function enabled_post_types_widget() {
    ?>
    <h3>Source</h3>
    <span><em>Push-enabled post types:</em></span>
	<?php display_push_enabled_post_types(); ?>
	<button id="save_push_enabled_post_types">Save</button>
    <div class="connected_site_enabled_post_types">
        <h3>Connected Sites</h3>
        <?php display_connected_sites_enabled_post_types(); ?>
    </div>
	<?php
}


function display_connected_sites_enabled_post_types() {


	$connected_sites_obj       = new ConnectedSites();
	$connected_sites           = $connected_sites_obj->get_all()->data;
	$enabled_post_type_site_data   = PostTypes::check_enabled_post_types_on_receiver();

	foreach ( $connected_sites as $index => $site ) {

		$site_info = $enabled_post_type_site_data[ $index ];
		$no_enabled_post_types_on_site = true;

		?><strong>Site ID: <?php echo $site->id ?> &middot; <?php echo $site->url ?></strong><?php

		if ( ! empty( $site_info['enabled_post_types'] ) ) {
			?>
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

		}
    }
}