<?php

namespace DataSync\Controllers;

use DataSync\Controllers\Enqueue;
use DataSync\Controllers\ConnectedSites;
use DataSync\Controllers\Options;
use DataSync\Controllers\SourceData;
use DataSync\Controllers\Widgets;
use DataSync\Controllers\Receiver;
use DataSync\Controllers\PostTypes;
use DataSync\Controllers\Posts;
use DataSync\Models\ConnectedSite;
use DataSync\Models\Post;


class Load {

	public function __construct() {
		new Enqueue();
		new Options();
		new Widgets();
		new ConnectedSites();
		new SourceData();
		new Receiver();
		$register_cpts = new PostTypes();

		if ( get_option( 'source_site' ) ) {
			new Posts();
			ConnectedSite::create_db_table();
			Post::create_db_table(); // Create post sync table.
		}

		// TODO: hook into all cpts' capabilites and add them into administrators' capabilities dynamically
	}

}