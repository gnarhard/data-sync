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
use DataSync\Models\Log;
use DataSync\Models\SyncedPost;
use DataSync\Models\PostType;
use DataSync\Models\Taxonomy;


class Load {

	public function __construct() {

		new Logs();
		new Enqueue();
		new Options();
		new Widgets();
		new ConnectedSites();
		new SourceData();
		new Receiver();
		new SyncedPosts();

		if ( get_option( 'source_site' ) ) {
			new Posts();
			new Log();
		} else {
			$post_type = new PostType();
			$post_type->create_db_table();
			$register_cpts = new PostTypes();

			$taxonomy = new Taxonomy();
			$taxonomy->create_db_table();
			new Taxonomies();

		}

		// TODO: hook into all cpts' capabilites and add them into administrators' capabilities dynamically
	}

}