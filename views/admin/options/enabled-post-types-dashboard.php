<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\PostTypes;
use DataSync\Models\ConnectedSite;

/**
 * Dashboard widget that displays which post types are enabled to be synced
 */
function display_enabled_post_types() {
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

	$connected_sites             = (array) ConnectedSite::get_all();

	foreach ( $connected_sites as $index => $site ) {

		$enabled_post_type_site_data = PostTypes::check_enabled_post_types_on_receiver( $site );

		?><strong>Site ID: <?php echo $site->id ?> &middot; <?php echo $site->url ?></strong><?php

		if ( ! empty( $enabled_post_type_site_data ) ) {
			?>
            <ol>
				<?php
				foreach ( $enabled_post_type_site_data as $post_type ) {
					?>
                    <li><?php echo $post_type ?></li><?php
				}
				?>
            </ol>
			<?php
		} else {

			?>
            <span class="none_enabled">No enabled post types on this site.</span><?php

		}
	}
}