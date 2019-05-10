<?php namespace DataSync;

if ( file_exists( DATA_SYNC_PATH . 'vendor/autoload.php')) {
	require_once (DATA_SYNC_PATH . 'vendor/autoload.php');
}

require_once 'enqueue.php';
require_once (DATA_SYNC_PATH. 'admin/admin-require.php');

$api = new API();
//$api->add_routes();
//new Settings();