<?php namespace DataSync;

use DataSync;

if ( file_exists( WP_DATA_SYNC_PATH . 'vendor/autoload.php')) {
	require_once (WP_DATA_SYNC_PATH . 'vendor/autoload.php');
}

require_once 'enqueue.php';
require_once (WP_DATA_SYNC_PATH. 'admin/admin-require.php');

$api = new API();
//new Settings();