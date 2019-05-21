<?php namespace DataSync;

add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\add_dashboard_widgets' );

/**
 * Add both dashboard widgets to WordPress Dashboard
 */
function add_dashboard_widgets() {
	wp_add_dashboard_widget( 'wp_data_sync_status_dashboard', __( 'WP Data Sync - Status', 'wp_data_sync' ), __NAMESPACE__ . '\status_widget' );
	wp_add_dashboard_widget( 'wp_data_sync_enabled_post_types_dashboard', __( 'WP Data Sync - Push-Enabled Post Types', 'wp_data_sync' ), __NAMESPACE__ . '\enabled_post_types_widget' );
}
