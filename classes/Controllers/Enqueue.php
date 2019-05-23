<?php


namespace DataSync\Controllers;

/**
 * Class Enqueue
 * @package DataSync
 */
class Enqueue {

	/**
	 * Enqueues scripts and styles
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
	}

	/**
	 * Enqueues scripts
	 */
	public function scripts() {
		wp_register_script( 'data-sync-admin', DATA_SYNC_URL . 'views/dist/js/admin-autoloader.es6.js', false, 1, true );
		wp_localize_script(
			'data-sync-admin',
			'DataSync',
			array(
				'strings' => array(
					'saved' => __( 'Options Saved', 'text-domain' ),
					'error' => __( 'Error', 'text-domain' ),
				),
				'api'     => array(
					'url'   => esc_url_raw( rest_url( DATA_SYNC_API_BASE_URL ) ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				),
			)
		);
		wp_enqueue_script( 'data-sync-admin' );
	}

	/**
	 * Enqueues styles
	 */
	public function styles() {
		wp_register_style( 'data-sync-admin', DATA_SYNC_URL . 'views/dist/styles/data-sync.css', false, 1 );
		wp_enqueue_style( 'data-sync-admin' );
	}
}
