<?php

require_once 'fields.php';
add_sections();
add_fields();
register();

function add_sections() {
	$option_group = 'wp-data-sync-settings';

	add_settings_section( "global", "", null, $option_group );
	add_settings_section( "wp_data_sync_source_settings", "Source Settings", null, $option_group );
	add_settings_section( "wp_data_sync_receiver_settings", "Receiver Settings", null, $option_group );

}

function add_fields() {

	$option_group = 'wp-data-sync-settings';

	add_settings_field( "source_site", "Source or Receiver?", "display_source_input", $option_group, "global" );

}

//add_action( "admin_init", "display_theme_panel_fields" );

function register() {

	$option_group = 'wp-data-sync-settings';
	register_setting( $option_group, "source_site" );

}