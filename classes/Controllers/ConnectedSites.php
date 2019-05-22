<?php


namespace DataSync;

use WP_REST_Request;
use DataSync\Models\ConnectedSite;


class ConnectedSites {

	public function get( WP_REST_Request $request ) {

	}

	public function save( WP_REST_Request $request ) {
		ConnectedSite::create();
		ConnectedSite::save();
	}

	public function delete( WP_REST_Request $request ) {
		ConnectedSite::delete();
	}

}