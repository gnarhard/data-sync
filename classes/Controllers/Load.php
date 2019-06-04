<?php

namespace DataSync\Controllers;

use DataSync\Models\ConnectedSite;
use Enqueue;
use ConnectedSites;
use Options;
use SourceData;
use Widgets;
use Receiver;
use PostTypes;
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
			ConnectedSite::create_db_table();
			Post::create_db_table(); // Create post sync table.
		}
	}

}