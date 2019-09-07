<?php

namespace DataSync\Controllers;

/**
 * Class Widgets
 * @package DataSync\Controllers
 */
class Widgets {

	/**
	 * Widgets constructor.
	 */
	public function __construct() {
		if ( get_option( 'source_site' ) ) {
			add_action( 'wp_dashboard_setup', [ $this, 'dashboard' ] );
		}
	}

	/**
	 * Add widget view files and register widgets
	 */
	public function dashboard() {
		require_once DATA_SYNC_PATH . 'views/admin/widgets/status-dashboard.php';
		require_once DATA_SYNC_PATH . 'views/admin/widgets/enabled-post-types-dashboard.php';
		wp_add_dashboard_widget( 'wp_data_sync_status_dashboard', __( 'WP Data Sync - Status', 'data_sync' ), 'DataSync\status_widget' );
		wp_add_dashboard_widget( 'wp_data_sync_enabled_post_types_dashboard', __( 'WP Data Sync - Enabled Post Types', 'data_sync' ), 'DataSync\enabled_post_types_widget' );
	}
}