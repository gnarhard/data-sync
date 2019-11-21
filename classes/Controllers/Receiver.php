<?php


namespace DataSync\Controllers;

use DataSync\Models\SyncedPost;
use DataSync\Models\ConnectedSite;
use WP_REST_Request;
use WP_REST_Server;
use DataSync\Models\DB;
use DataSync\Controllers\Users;
use DataSync\Models\Log;

/**
 * Class Receiver
 * @package DataSync\Controllers
 */
class Receiver {

    /**
     * @var string
     */
    public $response = '';

    /**
     * Receiver constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        if ( '0' === get_option( 'source_site' ) ) {
            add_action( 'rest_pre_dispatch', [ $this, 'authorize_source_cors_http_header' ] );
        }
    }


    public function authorize_source_cors_http_header() {
        if ( get_option( 'data_sync_source_site_url' ) ) {
            header( "Access-Control-Allow-Origin: " . get_option( 'data_sync_source_site_url' ) );
        }
    }

    /**
     *
     */
    public function start_fresh() {
        global $wpdb;
        $db               = new DB();
        $sql_statements   = array();
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_custom_post_types';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_custom_taxonomies';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_log';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_posts';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'data_sync_terms';

        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'posts';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'postmeta';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'terms';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'termmeta';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'term_taxonomy';
        $sql_statements[] = 'TRUNCATE TABLE ' . $wpdb->prefix . 'term_relationships';

        foreach ( $sql_statements as $sql ) {
            $db->query( $sql );
        }


        $upload_dir   = wp_upload_dir();
        $template_dir = DATA_SYNC_PATH . 'templates';

        if ( is_multisite() ) {
            $blog_ids        = get_sites();
            $network_blog_id = (int) $blog_ids[0]->blog_id;

            if ( $network_blog_id !== get_current_blog_id() ) {
                File::delete_media( $upload_dir['basedir'] ); // DELETE ALL MEDIA.
                mkdir( $upload_dir['basedir'], 0755 );

                // DELETE TEMPLATES
                File::delete_media( $template_dir );
                mkdir( $template_dir, 0755 );
            }
        } else {
            File::delete_media( $upload_dir['basedir'] );
            mkdir( $upload_dir['basedir'], 0755 );

            // DELETE TEMPLATES
            File::delete_media( $template_dir );
            mkdir( $template_dir, 0755 );
        }


        wp_send_json_success( 'Receiver table truncation completed.' );
    }

    /**
     * @return mixed
     */
    public function prevalidate() {
        $plugins = get_plugins();

        $prevalidation_data                    = new \stdClass();
        $prevalidation_data->site_id           = (int) get_option( 'data_sync_receiver_site_id' );
        $prevalidation_data->site_admin_url    = get_site_url() . '/wp-admin/plugins.php';
        $prevalidation_data->versions          = array();
        $prevalidation_data->versions['acf']   = $plugins['advanced-custom-fields-pro/acf.php']['Version'];
        $prevalidation_data->versions['cptui'] = $plugins['custom-post-type-ui/custom-post-type-ui.php']['Version'];
        $prevalidation_data->versions['wp']    = bloginfo( 'version' );

        return wp_json_encode( $prevalidation_data );
    }

    /**
     *
     */
    public function sync() {
        $this->source_data = (object) json_decode( file_get_contents( 'php://input' ) );

        $response = new \stdClass();

        if ( $this->source_data->media_package ) {
            $media = new Media();
            $media->update( $this->source_data );

            $response->synced_posts = SyncedPost::get_all_and_sort( [ 'date_modified' => 'DESC' ], $this->source_data->start_time );
            $response->logs         = Log::get_all_and_sort( [ 'datetime' => 'DESC' ], $this->source_data->start_time );
            $response->message      = $this->source_data->filename . ' synced to ' . $this->source_data->receiver_site_url;

        } elseif ( false === $this->source_data->media_package ) {
            $this->sync_options_and_meta();
            $logs = new Logs();
            $logs->set( 'OPTIONS AND META SYNCED.' );

            $this->sync_posts();

            //		$email = new Email();
            //		unset( $email );

            $logs = new Logs();
            $logs->set( 'POSTS SYNCED.' );

            $response->synced_posts = SyncedPost::get_all_and_sort( [ 'date_modified' => 'DESC' ], $this->source_data->start_time );
            $response->logs         = Log::get_all_and_sort( [ 'datetime' => 'DESC' ], $this->source_data->start_time );
            $response->message      = 'Source post data synced to ' . $this->source_data->receiver_site_url;
        } else {
            $response->message = 'Missing media package boolean in ' . $this->source_data->receiver_site_url . ' sync request.';
        }

        wp_send_json_success( $response );

    }


    /**
     */
    public function sync_posts() {

        // GET ALL CUSTOM RECEIVER OPTIONS THAT WOULD BE IN THE PLUGIN SETTINGS.
        $receiver_options = (object) Options::receiver();

        $this->source_data->synced_posts = (array) $this->source_data->synced_posts;

        // SAFEGUARD AGAINST SITES WITHOUT ANY ENABLED POST TYPES.
        if ( 'string' !== gettype( $receiver_options->enabled_post_types ) ) {

            // START PROCESSING ALL POSTS THAT ARE INCLUDED IN RECEIVER'S ENABLED POST TYPES.
            foreach ( $receiver_options->enabled_post_types as $post_type_slug ) {
                if ( ! isset( $this->source_data->posts->$post_type_slug ) ) {
                    continue; // SKIPS EMPTY DATA.
                }

                if ( empty( $this->source_data->posts->$post_type_slug ) ) {
                    $logs = new Logs();
                    $logs->set( 'No posts received in ' . $post_type_slug . '.', true );
                } else {
                    // LOOP THROUGH ALL POSTS THAT ARE IN A SPECIFIC POST TYPE.
                    foreach ( $this->source_data->posts->$post_type_slug as $post ) {
                        $this->filter_and_sync( $post );
                    }
                }
            }
        }
    }

    private function sync_options_and_meta() {

        // UPDATE LOCAL OPTIONS WITH FRESH SOURCE OPTION DATA.
        $this->update_wp_options( $this->source_data );

        // ADD ALL CUSTOM POST TYPES AND CHECK IF THEY ARE ENABLED BY DEFAULT. IF SO, SAVE THE OPTIONS, IF NOT, MOVE ON.
        $this->update_post_types( $this->source_data );

        // ADD AND SAVE ACF FIELDS
        ACFs::save_acf_fields( $this->source_data->acf );
        $logs = new Logs();
        $logs->set( 'ACF fields synced.' );

        // ADD AND SAVE ALL TAXONOMIES.
        $this->update_taxonomies( $this->source_data );
    }

    private function update_wp_options() {
        update_option( 'data_sync_receiver_site_id', (int) $this->source_data->receiver_site_id );
        update_option( 'data_sync_source_site_url', $this->source_data->url );
        update_option( 'debug', $this->source_data->options->debug );
        update_option( 'show_body_responses', $this->source_data->options->show_body_responses );
        update_option( 'overwrite_receiver_post_on_conflict', (bool) $this->source_data->options->overwrite_receiver_post_on_conflict );
    }

    private function update_post_types() {
        PostTypes::process( $this->source_data->options->push_enabled_post_types );
        if ( true === $this->source_data->options->enable_new_cpts ) {
            PostTypes::save_options();
        }
        $logs = new Logs();
        $logs->set( 'Post types synced.' );
    }

    private function update_taxonomies() {
        foreach ( $this->source_data->custom_taxonomies as $taxonomy ) {
            SyncedTaxonomies::save( $taxonomy );
        }
        $syncedTaxonomies = new SyncedTaxonomies(); // REGISTERS NEW TAXONOMIES.
        $syncedTaxonomies->register();
        $logs = new Logs();
        $logs->set( 'Custom taxonomies synced.' );
    }

    private function filter_and_sync( $post ) {

        // FILTER OUT POSTS THAT SHOULDN'T BE SYNCED.
        $filtered_post = SyncedPosts::filter( $post, $this->source_data, $this->source_data->synced_posts );

        if ( false !== $filtered_post ) {

            // UPDATE POST AUTHOR
            $filtered_post->post_author = Users::get_receiver_user_id( $post->post_author, $this->source_data->users );

            $receiver_post_id        = Posts::save( $filtered_post, $this->source_data->synced_posts );
            $filtered_post->diverged = 0;
            $synced_post_result      = SyncedPosts::save_to_receiver( $receiver_post_id, $filtered_post );

            $logs = new Logs();
            $logs->set( $filtered_post->post_title . ' (' . $filtered_post->post_type . ') synced.' );
        }
    }

    public function give_receiver_data() {
        $posts_obj      = new Posts();
        $post_types_obj = new PostTypes();
        $receiver_data  = new \stdClass();

        $receiver_data->site_id            = (int) get_option( 'data_sync_receiver_site_id' );
        $receiver_data->posts              = $posts_obj->get_all_posts();
        $receiver_data->enabled_post_types = $post_types_obj->get_enabled_post_types();

        return $receiver_data;
    }


    /**
     *
     */
    public function register_routes() {
        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/sync', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'sync' ),
                'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
            ),
        ) );

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/overwrite', array(
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'sync' ),
                'permission_callback' => array( __NAMESPACE__ . '\Auth', 'authorize' ),
            ),
        ) );

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/start_fresh', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'start_fresh' ),
            ),
        ) );

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/receiver/get_data', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'give_receiver_data' ),
            ),
        ) );

        $registered = register_rest_route( DATA_SYNC_API_BASE_URL, '/receiver/prevalidate', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'prevalidate' ),
            ),
        ) );
    }

}
