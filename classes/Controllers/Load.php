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
use DataSync\Models\SyncedPost;
use DataSync\Models\PostType;


class Load {

	public function __construct() {
		new Enqueue();
		new Options();
		new Widgets();
		new ConnectedSites();
		new SourceData();
		new Receiver();
		$post_type = new PostType();
		$post_type->create_db_table();
		$register_cpts = new PostTypes();

		if ( get_option( 'source_site' ) ) {
			new SyncedPosts();
		}

		// TODO: hook into all cpts' capabilites and add them into administrators' capabilities dynamically
	}

}