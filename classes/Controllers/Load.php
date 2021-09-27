<?php

namespace DataSync\Controllers;

use DataSync\Models\ConnectedSite;
use DataSync\Models\Log;
use DataSync\Models\SyncedPost;
use DataSync\Models\PostType;
use DataSync\Models\SyncedTaxonomy;
use DataSync\Models\SyncedTerm;

class Load {
	public $source = null;
	public bool $site_type_set = false;

	public function __construct() {
		if ( '1' === get_option( 'source_site' ) ) {
			$this->source        = true;
			$this->site_type_set = true;
		} elseif ( '0' === get_option( 'source_site' ) ) {
			$this->source        = false;
			$this->site_type_set = true;
		}

		// ALLOW CORS. Has to be init if you want headers changed globally for any request!
		add_action( 'init', function () {
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: *' );
			header( 'Access-Control-Allow-Credentials: true' );
			// This allows us to discover wp-json or ?rest_route= with CORS.
			header( 'Access-Control-Expose-Headers: Link', false );
		} );

		$this->check_multisite();
	}

	public function load_once() {
		new Options();
		new Enqueue();
	}

	public function check_multisite() {
		if ( is_multisite() ) {
			$blog_ids        = get_sites();
			$network_blog_id = (int) $blog_ids[0]->blog_id;

			if ( $network_blog_id !== get_current_blog_id() ) {
				$this->instantiate();
				$this->load_once();
			}

			foreach ( $blog_ids as $index => $wp_site ) {

				// NEVER INSTALL ON MASTER NETWORK SITE.
				if ( $network_blog_id === get_current_blog_id() ) {
					continue;
				}

				switch_to_blog( $wp_site->blog_id );
				$this->activate();
				restore_current_blog();
			}
		} else {
			// not a multi-site.
			$this->instantiate();
			$this->activate();
			$this->load_once();
		}
	}


	public function activate() {
		// FLUSH REWRITE RULES TO PREPARE FOR NEW WP API ROUTES
		register_activation_hook( DATA_SYNC_PATH . 'data-sync.php', 'flush_rewrite_rules' );
	}


	public function instantiate() {
		new ConnectedSites();
		new SourceData();
		new Receiver();
		new SyncedPosts();
		new Media();
		new TemplateSync();
		new Posts();

		$logs = new Logs();
		new Log();
		$this->create_db_tables();
	}


	public function create_db_tables() {
		$synced_post = new SyncedPost();

		global $wpdb;

		if ( $this->source ) {
			$connected_site = new ConnectedSite();
			$connected_site->create_db_table();

			$result = $wpdb->get_results( 'show tables like "' . $wpdb->prefix . 'data_sync_posts"' );

			if ( empty( $result ) ) {
				$synced_post->create_db_table_source();
			}
		} else {
			$synced_post->create_db_table_receiver();

			$synced_term = new SyncedTerm();
			new SyncedTerms();
			$result = $wpdb->get_results( 'show tables like "' . $wpdb->prefix . 'data_sync_terms"' );

			if ( empty( $result ) ) {
				$synced_term->create_db_table();
			}


			$post_type = new PostType();
			$post_type->create_db_table();
			new PostTypes();

			$synced_taxonomy = new SyncedTaxonomy();
			$synced_taxonomy->create_db_table();
			new SyncedTaxonomies();
		}
	}
}
