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
use DataSync\Models\SyncedTaxonomy;
use DataSync\Controllers\TemplateSync;
use DataSync\Models\SyncedTerm;


class Load {

	public $source = null;
	public $no_site_type_setting = true;

	public function __construct() {

		if ( '1' === get_option( 'source_site' ) ) {
			$this->source               = true;
			$this->no_site_type_setting = false;
		} elseif ( '0' === get_option( 'source_site' ) ) {
			$this->source               = false;
			$this->no_site_type_setting = false;
		}

		$this->check_multisite();

	}

	public function check_multisite() {

		if ( is_multisite() ) {

			$blog_ids        = get_sites();
			$network_blog_id = (int) $blog_ids[0]->blog_id;

			if ( $network_blog_id !== get_current_blog_id() ) {
				$this->include();
			}

			foreach ( $blog_ids as $index => $blog_id ) {

				// NEVER INSTALL ON MASTER NETWORK SITE.
				if ( $network_blog_id === get_current_blog_id() ) {
					continue;
				}

				switch_to_blog( $blog_id );
				$this->activate();
				restore_current_blog();

			}

		} else {
			// not a multi-site.
			$this->include();
			$this->activate();
		}
	}


	public function activate() {

		// PLUGIN WON'T WORK WITHOUT POSTNAME PERMALINK STRUCTURE. SO THIS NEEDS TO BE HARDCODED.
		update_option( 'permalink_structure', '/%postname%/' );

		// FLUSH REWRITE RULES TO PREPARE FOR NEW WP API ROUTES
		register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', 'flush_rewrite_rules' );

	}


	public function include() {
		$this->load_generics();

		if ( $this->source ) {
			new Posts();
		}

		if ( ! $this->no_site_type_setting ) {
			new Logs();
			new Log();
			$this->create_db_tables();
		}

	}


	public function create_db_tables() {

		$synced_post = new SyncedPost();

		if ( $this->source ) {

			// TODO: WHAT IF SETTING DOESN'T EXIST BEFORE REGISTRATION?
			$connected_site = new ConnectedSite();
			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [ $connected_site, 'create_db_table' ] );
			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [
				$synced_post,
				'create_db_table_source'
			] );

		} else {

			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [
				$synced_post,
				'create_db_table_receiver'
			] );

			$synced_term = new SyncedTerm();
			new SyncedTerms();
			register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', [ $synced_term, 'create_db_table' ] );

			$post_type = new PostType();
			$post_type->create_db_table();
			new PostTypes();

			$synced_taxonomy = new SyncedTaxonomy();
			$synced_taxonomy->create_db_table();
			new SyncedTaxonomies();

		}

	}

	public function load_generics() {
		new Enqueue();
		new Options();
		new Widgets();
		new ConnectedSites();
		new SourceData();
		new Receiver();
		new SyncedPosts();
		new TemplateSync();
		new Media();
	}

}
