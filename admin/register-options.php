<?php

add_action('admin_init', 'add_wp_data_sync_options');

function add_wp_data_sync_options() {

	// SECTIONS
	add_settings_section( "wp_data_sync_global_settings", "", false, 'wp-data-sync-settings' );
	add_settings_section( "wp_data_sync_source_settings", "Source Settings", false, 'wp-data-sync-settings' );
	add_settings_section( "wp_data_sync_receiver_settings", "Receiver Settings", false, 'wp-data-sync-settings' );

	// OPTIONS
	add_settings_field( "source_site", "Source or Receiver?", "display_source_input", 'wp-data-sync-settings', "wp_data_sync_global_settings" );
	register_setting( "wp_data_sync_global_settings", "source_site" );
}