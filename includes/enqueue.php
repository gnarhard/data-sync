<?php namespace DataSync;

function enqueue() {

	wp_register_style( 'data-sync-admin', DATA_SYNC_URL. 'dist/styles/data-sync.css', false, false);
	wp_enqueue_style( 'data-sync-admin' );

	wp_register_script( 'data-sync-admin', DATA_SYNC_URL . 'dist/js/admin-autoloader.es6.js', false, false, true);
	wp_localize_script( 'data-sync-admin', 'DataSync', array(
		'strings' => array(
			'saved' => __( 'Settings Saved', 'text-domain' ),
			'error' => __( 'Error', 'text-domain' )
		),
		'api'     => array(
			'url'   => esc_url_raw( rest_url( 'data-sync-api/v1/settings' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' )
		)
	) );
	wp_enqueue_script( 'data-sync-admin' );

}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\enqueue');