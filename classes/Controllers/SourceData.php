<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Logs;
use DataSync\Controllers\Options;
use DataSync\Controllers\ConnectedSites;
use DataSync\Models\ConnectedSite;
use WP_REST_Request;
use WP_REST_Server;
use ACF_Admin_Tool_Export;
use stdClass;
use DataSync\Models\DB;
use DataSync\Controllers\ACFs;
use WP_Http_Cookie;

/**
 * Class SourceData
 * @package DataSync\Controllers
 */
class SourceData {

    /**
     * @var
     */
    public $source_data;
    /**
     * @var
     */
    public $receiver_logs;
    /**
     * @var
     */
    public $receiver_synced_posts;

    /**
     * SourceData constructor.
     * Instantiate RESTful Route
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register RESTful routes for Data Sync API
     *
     */
    public function register_routes() {

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/source_data/(?P<action>[a-zA-Z-_]+)/(?P<source_post_id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_source_data' ),
                'args'     => array(
                    'action'         => array(
                        'description' => 'Action to tell backend which content to provide.',
                        'type'        => 'string',
                    ),
                    'source_post_id' => array(
                        'description' => 'Source Post ID',
                        'type'        => 'int',
                    ),
                ),
            ),
        ) );

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/source/start_fresh', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'start_fresh' ),
            ),
        ) );


        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/source_data/prep/(?P<source_post_id>\d+)/(?P<receiver_site_id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'prep' ),
                'args'     => array(
                    'source_post_id'   => array(
                        'description' => 'Source Post ID',
                        'type'        => 'int',
                    ),
                    'receiver_site_id' => array(
                        'description' => 'Receiver Site ID',
                        'type'        => 'int',
                    ),
                ),
            ),
        ) );

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/prevalidate', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'prevalidate' ),
            ),
        ) );

    }


    public function prepare_single_overwrite( $request ) {

        $this->consolidate();

        $request_body = json_decode( $request->get_body() );

        $post                                                            = (object) Posts::get_single( $request->get_url_params()['source_post_id'] );
        $this->source_data->options->overwrite_receiver_post_on_conflict = (bool) $request_body->overwrite;
        $this->source_data->posts                                        = new stdClass(); // CLEAR ALL OTHER POSTS.
        $this->source_data->posts->$post->post_type                      = [ $post ]; // CREATE POSTS ARRAY WITH POST_TYPE FOR RECEIVER COMPATIBILITY.

        $this->validate();
        $this->configure_canonical_urls();
    }

    public function prep( WP_REST_Request $request ) {

        $this->consolidate();

        $post_id = (int) $request->get_url_params()['source_post_id'];

        if ( 0 !== $post_id ) {
            // NOT BULK PACKAGE, CLEAR AND SET FOR ONE POST.
            $post                                                            = (object) Posts::get_single( $post_id );
            $post_type                                                       = $post->post_type;
            $this->source_data->options->overwrite_receiver_post_on_conflict = true;
            $this->source_data->posts                                        = new stdClass(); // CLEAR ALL OTHER POSTS.
            $this->source_data->posts->$post_type                            = [ $post ]; // CREATE POSTS ARRAY WITH POST_TYPE FOR RECEIVER COMPATIBILITY.
        } else {
            // LOAD ALL POSTS DUE TO BULK REQUEST.
            $this->source_data->posts = (object) Posts::get_all( array_keys( $this->source_data->options->push_enabled_post_types ) );
        }

        $this->validate();
        $this->configure_canonical_urls();


        $this->source_data->receiver_site_id  = (int) $request->get_url_params()['receiver_site_id'];
        $args                                 = [ 'id' => $this->source_data->receiver_site_id ];
        $connected_site                       = ConnectedSite::get_where( $args );
        $connected_site                       = $connected_site[0];
        $this->source_data->receiver_site_url = $connected_site->url;

        $auth = new Auth();
        $json = $auth->prepare( $this->source_data, $connected_site->secret_key );

        return $json;
    }


    /**
     * Manually overwrite receiver post via API call
     *
     * @param WP_REST_Request $request
     */
    public function overwrite_post_on_single_receiver( WP_REST_Request $request ) {

        $this->prepare_single_overwrite( $request );

        if ( ! empty( $this->source_data ) ) {

            $args           = [ 'id' => (int) $request->get_url_params()['receiver_site_id'] ];
            $connected_site = ConnectedSite::get_where( $args );
            $connected_site = $connected_site[0];

            $this->source_data->receiver_site_id = (int) $request->get_url_params()['receiver_site_id'];

            $auth     = new Auth();
            $json     = $auth->prepare( $this->source_data, $connected_site->secret_key );
            $url      = trailingslashit( $connected_site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/receive';
            $response = wp_remote_post( $url, [
                'body'        => $json,
                'httpversion' => '1.0',
                'sslverify'   => false,
                'timeout'     => 10,
                'blocking'    => true,
            ] );

            if ( is_wp_error( $response ) ) {
                $logs = new Logs();
                $logs->set( 'Error in SourceData->overwrite_post_on_single_receiver() received from ' . $connected_site->url . '. ' . $response->get_error_message(), true );

                return $response;
            }

//		print_r( wp_remote_retrieve_body( $response ) );
            $this->finish_push( wp_remote_retrieve_body( $response ) );

        } else {
            wp_send_json_error( 'Validation failed.' );
        }

    }


    public function push_post_to_all_receivers( WP_REST_Request $request ) {

        $this->prepare_single_overwrite( $request );

        // COULD BE EMPTY FROM VALIDATION.
        if ( ! empty( $this->source_data ) ) {

            foreach ( $this->source_data->connected_sites as $site ) {

                $this->source_data->receiver_site_id = (int) $site->id;

                $post_data = $this->create_request_data( $site );

                $response = wp_remote_post( $post_data->url, [
                    'body'        => $post_data->json,
                    'httpversion' => '1.0',
                    'sslverify'   => false,
                    'timeout'     => 10,
                    'blocking'    => true,
                ] );

                if ( is_wp_error( $response ) ) {
                    $logs = new Logs();
                    $logs->set( 'Error in SourceData->overwrite_post_on_all_receivers() received from ' . $site->url . '. ' . $response->get_error_message(), true );

                    return $response;
                }

            }

            $this->finish_push( wp_remote_retrieve_body( $response ) );

        } else {
            wp_send_json_error( 'Validation failed.' );
        }
    }


    public function create_request_data( $site ) {

        $post_data                           = new stdClass();
        $this->source_data->receiver_site_id = (int) $site->id;
        $post_data->url                      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/receive';
        $auth                                = new Auth();
        $post_data->json                     = $auth->prepare( $this->source_data, $site->secret_key );

        return $post_data;
    }


    /**
     * Send data to all authorized connected sites
     *
     */
    public function push() {

        // COULD BE EMPTY FROM VALIDATION.
        if ( ! empty( $this->source_data ) ) {

            foreach ( $this->source_data->connected_sites as $site ) {

                $post_data = $this->create_request_data( $site );

                $response = wp_remote_post( $post_data->url, [
                    'body'        => $post_data->json,
                    'httpversion' => '1.0',
                    'sslverify'   => false,
                    'timeout'     => 10,
                    'blocking'    => true,
                ] );

                if ( is_wp_error( $response ) ) {
                    $logs = new Logs();
                    $logs->set( 'Error in SourceData->push() received from ' . $site->url . '. ' . $response->get_error_message(), true );

                    return $response;
                }

            }

            $this->finish_push( wp_remote_retrieve_body( $response ) );

        } else {
            wp_send_json_error( 'Validation failed.' );
        }

    }


    public function finish_push( $response ) {

        // GET NEW SYNCED POSTS AND LOGS BEFORE MEDIA.
        $this->get_receiver_data();
        $this->save_receiver_data();

        // DON'T MOVE!!! NEED NEW SYNCED POSTS FOR THIS TO WORK BUG-FREE.
        new Media( $this->source_data->posts );

        $this->get_receiver_data();
        $this->save_receiver_data();

        wp_send_json_success( json_decode( $response ) );
    }


    /**
     *
     * Truncate source tables for a fresh testing start
     *
     */
    public function start_fresh() {

        global $wpdb;
        $db               = new DB();
        $connected_sites  = (array) ConnectedSite::get_all();
        $sql_statements   = array();
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_posts';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_log';

        foreach ( $sql_statements as $sql ) {
            $db->query( $sql );
        }

        foreach ( $connected_sites as $site ) {

            $url      = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/start_fresh';
            $response = wp_remote_get( $url );

            if ( is_wp_error( $response ) ) {
                $logs = new Logs();
                $logs->set( 'Error in SourceData->start_fresh() received from ' . $site->url . '. ' . $response->get_error_message(), true );

                return $response;
            }

        }

        wp_send_json_success( 'Source and receiver table truncations complete.' );

    }

    /**
     *
     * Pull receiver logs and synced posts
     */
    private function get_receiver_data() {
        $this->receiver_logs         = Logs::retrieve_receiver_logs( $this->source_data->start_time );
        $this->receiver_synced_posts = SyncedPosts::retrieve_from_receiver( $this->source_data->start_time );
    }

    /**
     * Save pulled receiver logs and synced posts to source database
     *
     */
    private function save_receiver_data() {
        Logs::save_to_source( $this->receiver_logs );
        SyncedPosts::save_all_to_source( $this->receiver_synced_posts );
        $logs = new Logs();
        $logs->set( 'Synced receiver error logs to source.' );
        $logs->set( 'Added receiver synced posts to source.' );
    }

    /**
     * Organize all source data before push
     *
     */
    private function consolidate() {

        $synced_posts = new SyncedPosts();
        $options      = Options::source();
        $upload_dir   = wp_get_upload_dir();

        $this->source_data                  = new stdClass();
        $this->source_data->media_package   = false;
        $this->source_data->upload_path     = $upload_dir['path'];
        $this->source_data->upload_url      = $upload_dir['url'];
        $this->source_data->start_time      = (string) current_time( 'mysql', 1 );
        $this->source_data->options         = (object) $options;
        $this->source_data->url             = (string) get_site_url();
        $this->source_data->connected_sites = (array) ConnectedSite::get_all();
        $this->source_data->synced_posts    = (array) $synced_posts->get_all()->get_data();
        $this->source_data->canonical_urls  = array();
        $this->source_data->users           = get_users();

        // PLUGINS ARE CHECKED TO EXIST BY AJAX PREVALIDATION.
        $this->source_data->acf               = (array) ACFs::get_acf_fields();
        $this->source_data->custom_taxonomies = (array) cptui_get_taxonomy_data();

    }


    /**
     *
     * Set up canonical urls that point to the permalink set in the canonical site
     */
    private function configure_canonical_urls() {
        if ( ! empty( $this->source_data ) ) {
            foreach ( $this->source_data->posts as $post_type => $post_data ) {

                foreach ( $post_data as $key => $post ) {

                    $canonical_site_id = (int) $post->post_meta['_canonical_site'][0];
                    $connected_site    = ConnectedSite::get( $canonical_site_id )[0];

                    if ( ! empty( $connected_site ) ) {
                        $permalink      = get_permalink( $post->ID );
                        $canonical_link = str_replace( get_site_url(), $connected_site->url, $permalink );

                        $post->post_meta['_yoast_wpseo_canonical'][0] = $canonical_link;
                    } else {
                        $logs = new Logs();
                        $logs->set( 'Canonical site url could not connect to ' . $post->post_title . ' because a previously connected site must have been deleted.', true );
                    }

                }

            }
        }

    }


    public function prevalidate() {

        // TODO: CHECK IF DATA SYNC PLUGIN ITSELF IS OUT OF DATE BEFORE SYNC.

        $connected_sites = (array) ConnectedSite::get_all();
        $plugin_info     = Options::get_required_plugins_info();

        if ( ! empty( $plugin_info ) ) {
            foreach ( $connected_sites as $site ) {
                $validated = Options::validate_required_plugins_info( (int) $site->id, $plugin_info );
                if ( ! $validated ) {
                    wp_send_json_error( 'Required plugins are out of date or missing. Please check source and receiver sites.' );
                }
            }
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Required plugins are out of date or missing. Please check source and receiver sites.' );
        }

    }


    /**
     *
     * Validate specific settings before sending to receiver
     * Unset posts if they don't meet the criteria and send error
     */
    private function validate() {
        $connected_sites = (array) ConnectedSite::get_all();
        $site_ids        = [];

        foreach ( $connected_sites as $site ) {
            $site_ids[] = (int) $site->id;
        }

        foreach ( $this->source_data->options->push_enabled_post_types_array as $post_type_slug ) {
            if ( ( ! isset( $this->source_data->posts->$post_type_slug ) ) || ( empty( $this->source_data->posts->$post_type_slug ) ) ) {
                continue; // SKIPS EMPTY DATA.
            } else {
                // LOOP THROUGH ALL POSTS THAT ARE IN A SPECIFIC POST TYPE.
                foreach ( $this->source_data->posts->$post_type_slug as $key => $post ) {

                    // VALIDATE IF CANONICAL SETTING IS SET.
                    if ( ! isset( $post->post_meta['_canonical_site'] ) ) {
                        unset( $this->source_data->posts->$post_type_slug[ $key ] );
                        $logs = new Logs();
                        $logs->set( 'SKIPPING: Canonical site not set in post: ' . $post->post_title, true );
                    } else {
                        // VALIDATE CANONICAL SITE ID CORRELATES TO EXISTING SITE.
                        $orphaned = ConnectedSites::is_orphaned( $post, $site_ids );
                        if ( $orphaned ) {
                            // REMOVE POST FROM SYNDICATION BECAUSE IT HAS FAULTY DATA.
                            unset( $this->source_data->posts->$post_type_slug[ $key ] );
                            $logs = new Logs();
                            $logs->set( $post->post_title . ' (ID: ' . $post->ID . ') in ' . $post->post_type . ' has a canonical site orphan.', true, 'orphaned_site' );
                        }
                    }

                    // VALIDATE IF EXCLUDED SITES HAVE BEEN SAVED.
                    if ( ! isset( $post->post_meta['_excluded_sites'] ) ) {
                        unset( $this->source_data->posts->$post_type_slug[ $key ] );
                        $logs = new Logs();
                        $logs->set( 'SKIPPING: Excluded sites not set in post: ' . $post->post_title, true );
                    }
                }
            }
        }
    }

    public function get_source_data( WP_REST_Request $request ) {

        if ( 'load' === $request->get_url_params()['action'] ) {

            $source_data = new stdClass();

            $source_data->source_options  = Options::source();
            $source_data->connected_sites = (array) ConnectedSite::get_all();
            if ( empty( $source_data->source_options->push_enabled_post_types ) ) {
                $source_data->error_msg = '<span>Required plugins not installed. Please turn on debugging and view error log for more details.</span>';
            }

            $post_id = (int) $request->get_url_params()['source_post_id'];

            if ( 0 === $post_id ) {
                // GET BULK
                $source_data->post_types = array_keys( $source_data->source_options->push_enabled_post_types );
                $source_data->posts      = Posts::get_wp_posts( $source_data->post_types, true );
            } else {
                // GET SINGLE
                $source_data->post_types = array_keys( $source_data->source_options->push_enabled_post_types );
                $source_data->posts[]    = (object) Posts::get_single( $post_id );
            }


        } elseif ( 'push' === $request->get_url_params()['action'] ) {

            $this->consolidate();
            $this->source_data->posts = (object) Posts::get_all( array_keys( $this->source_data->options->push_enabled_post_types ) );
            $this->validate();
            $this->configure_canonical_urls();

            $source_data = $this->source_data;

        }

        wp_send_json( $source_data );
    }

}