<?php

add_action( 'wp_dashboard_setup', 'wp_data_sync_add_widgets' );
function wp_data_sync_add_widgets() {
	wp_add_dashboard_widget( 'wp_data_sync_dashboard', __( 'WP Data Sync Status', 'wp_data_sync' ), 'wp_data_sync_handler' );
}

function wp_data_sync_handler() {
	_e( 'Behold, the Gordonium Empire.', 'wp_data_sync' );
}