<?php namespace DataSync;
add_action( 'admin_menu', __NAMESPACE__ . '\plugin_menu' );
function plugin_menu() {
	add_options_page(
		'WP Data Sync',
		'WP Data Sync',
		'manage_options',
		'data-sync-settings',
		__NAMESPACE__ . '\data_sync_settings'
	);
}