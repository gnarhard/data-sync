<?php

function wp_data_sync_enqueue() {

	wp_register_style( 'wp-data-sync-admin', WP_DATA_SYNC_URL. 'dist/styles/wp-data-sync.css', false, false);
	wp_enqueue_style( 'wp-data-sync-admin' );

	wp_register_script( 'wp-data-sync-admin', WP_DATA_SYNC_URL . 'dist/js/admin-autoloader.es6.js', false, false, true);
	wp_enqueue_script( 'wp-data-sync-admin' );

}
add_action('admin_enqueue_scripts', 'wp_data_sync_enqueue');