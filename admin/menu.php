<?php

add_action( 'admin_menu', 'wp_data_sync_plugin_menu' );
function wp_data_sync_plugin_menu() {
	add_options_page(
		'WP Data Sync',
		'WP Data Sync',
		'manage_options',
		'wp-data-sync-settings',
		'wp_data_sync_settings'
	);
}