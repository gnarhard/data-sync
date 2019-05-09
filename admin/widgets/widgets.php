<?php

add_action( 'wp_dashboard_setup', 'wp_data_sync_add_widgets' );
function wp_data_sync_add_widgets() {
	wp_add_dashboard_widget( 'wp_data_sync_status_dashboard', __( 'WP Data Sync - Status', 'wp_data_sync' ), 'wp_data_sync_status_widget' );
	wp_add_dashboard_widget( 'wp_data_sync_enabled_post_types_dashboard', __( 'WP Data Sync - Enabled Post Types', 'wp_data_sync' ), 'wp_data_sync_enabled_post_types_widget' );
}