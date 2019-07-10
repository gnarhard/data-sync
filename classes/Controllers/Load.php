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
use DataSync\Controllers\Logs;
use DataSync\Models\Log;
use DataSync\Models\SyncedPost;
use DataSync\Models\PostType;
use DataSync\Models\Taxonomy;
use DataSync\Controllers\TemplateSync;


class Load {

	public function __construct() {

		// TODO: CREATE DOCBLOCKS FOR EVERYTHING

		register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', 'flush_rewrite_rules' );

		update_option( 'permalink_structure', '/%postname%/' );

		new Logs();
		new Enqueue();
		new Options();
		new Widgets();
		new ConnectedSites();
		new SourceData();
		new Receiver();
		new SyncedPosts();
		new TemplateSync();
		new Log();

		$synced_post = new SyncedPost();

		if ( '1' === get_option( 'source_site' ) ) {
			new Posts();

			$connected_site = new ConnectedSite();
			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [ $connected_site, 'create_db_table' ] );
			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [ $synced_post, 'create_db_table_source' ] );
		} elseif ( '0' === get_option( 'source_site' ) ) {

			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [ $synced_post, 'create_db_table_receiver' ] );

			$post_type = new PostType();
			$post_type->create_db_table();
			new PostTypes();

			$taxonomy = new Taxonomy();
			$taxonomy->create_db_table();
			new Taxonomies();
		}

	}

}
