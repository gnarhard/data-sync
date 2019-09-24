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
<!--    <div id="data_sync_status_tabs">-->
		<?php
		$status = display_synced_posts_table();

		foreach ( $connected_sites as $index => $site ) {
			$i ++;
			if ( $i === $number_of_sites_connected ) {
				$connected_site_ids .= $site->id;
			} else {
				$connected_site_ids .= $site->id . ',';
			}
		}
		?>

        <input type="hidden" id="sites_connected_info" data-ids="<?php echo $connected_site_ids ?>"/>
<!--    </div>-->

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
            <button id="bulk_data_push" class="button button-primary" href="/wp-json/data-sync/v1/source_data/push"><?php _e( 'Sync', 'data_sync' ); ?></button><?php
		}
		?>

        <button id="template_push" class="button button-primary"><?php _e( 'Push Template', 'data_sync' ); ?></button>
    </div>
    <div id="status_wrap"></div>
	<?php
}
