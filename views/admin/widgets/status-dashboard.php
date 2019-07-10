<?php namespace DataSync;

use DataSync\Controllers\ConnectedSites;

/**
 * Dashboard widget that displays status of posts
 */
function status_widget() {

	include_once 'synced-posts-table.php';
	$connected_sites_obj       = new ConnectedSites();
	$connected_sites           = $connected_sites_obj->get_all()->data;
	$connected_site_ids        = '';
	$i                         = 0;
	$number_of_sites_connected = count( $connected_sites );
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
			<ol>
				<?php
				foreach ( $connected_sites as $site ) {
					$i ++;
					if ( $i === $number_of_sites_connected ) {
						$connected_site_ids .= $site->id;
					} else {
						$connected_site_ids .= $site->id . ',';
					}

					?>
					<li><?php echo $site->url ?></li>
					<?php
				}
				?>
			</ol>
			<div id="connected_site_link_wrap">
				<a href="<?php echo admin_url( 'options-general.php?page=data-sync-options' ); ?>">Manage connected sites</a>
			</div>
			<input type="hidden" id="sites_connected_info" data-ids="<?php echo $connected_site_ids ?>"/>
		</div>
	</div>

	<div id="status_dashboard_button_wrap">
		<a id="bulk_data_push" href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></a>
		<button id="template_push"><?php _e( 'Push Template', 'data_sync' ); ?></button>
	</div>
	<div id="error_log_wrap">
		<a href="<?php echo admin_url( 'options-general.php?page=data-sync-options' ); ?>">Go to error log</a>
	</div>
	<?php
}
