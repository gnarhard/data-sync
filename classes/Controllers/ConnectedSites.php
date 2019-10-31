<?php


namespace DataSync\Controllers;

use WP_REST_Request;
use DataSync\Models\ConnectedSite;
use DataSync\Controllers\Logs;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class ConnectedSites
{
    public function __construct()
    {
        add_action('rest_api_init', [ $this, 'register_routes' ]);
    }

    public function register_routes()
    {
        $registered = register_rest_route(
            DATA_SYNC_API_BASE_URL,
            '/connected_sites',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( 'DataSync\Models\ConnectedSite', 'get_all' ),
                    'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'save' ),
                    'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
                ),
            )

        );

        $registered = register_rest_route(
            DATA_SYNC_API_BASE_URL,
            '/connected_sites/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get' ),
                    'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => 'ID of connected_site',
                            'type'              => 'int',
                            'validate_callback' => 'is_numeric',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete' ),
                    'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => 'ID of connected_site',
                            'type'              => 'int',
                            'validate_callback' => 'is_numeric',
                        ),
                    ),
                ),
            )
        );
    }

    public function get(WP_REST_Request $request)
    {
        $connected_site_id   = $request->get_param('id');
        $connected_site_data = ConnectedSite::get($connected_site_id);

        return $connected_site_data[0];
    }

    public function save(WP_REST_Request $request)
    {
        $new_data = array();

        foreach ($request->get_params() as $data) {
            if (in_array('id', array_keys($data))) {
                ConnectedSite::update($data);
            } else {
                $new_id = ConnectedSite::create($data);
                if (is_numeric($new_id)) {
                    $data['id'] = $new_id;
                }
            }
            $new_data[] = $data;
        }

        return wp_send_json_success();
    }

    public function delete(WP_REST_Request $request)
    {
        $id = (int) $request->get_url_params()['id'];
        if ($id) {
            $response = ConnectedSite::delete($id);
            if ($response) {
                wp_send_json_success('Connected site deleted.');
            } else {
                $logs = new Logs();
                $logs->set('Connected site was not deleted.', true);
                return new WP_Error('database_error', 'DB Logs: Connected site was not deleted.', array( 'status' => 501 ));
            }
        } else {
            $logs = new Logs();
            $logs->set('Connected site was not deleted. No ID present in URL.', true);
            return new WP_Error('database_error', 'DB Logs: Connected site was not deleted. No ID in URL.', array( 'status' => 501 ));
        }
    }

    public static function check_sync_date($post, $source_data)
    {
        $site_id = (int) get_option('data_sync_receiver_site_id');

        foreach ($source_data->connected_sites as $site) {
            if ((int) $site->id === $site_id) {
                $connected_site_sync_date   = strtotime($site->sync_start);
                $source_modified_date = strtotime($post->post_modified_gmt);
                if ($source_modified_date > $connected_site_sync_date) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function is_orphaned($post, $site_ids) {
        $canonical_site_id = (int) $post->post_meta['_canonical_site'][0];
        $excluded_site_ids = unserialize( $post->post_meta['_excluded_sites'][0] ) ;

        foreach( $excluded_site_ids as $key => $excluded_site_id ) {
            if (! in_array( $excluded_site_id, $site_ids ) ) {
                unset( $excluded_site_ids[$key]);
                if ( 0 === count($excluded_site_ids)) {
                    $excluded_site_ids[0] = 0;
                }
                update_post_meta( $post->ID, '_excluded_sites', $excluded_site_ids );
            }
        }


        if ( in_array( $canonical_site_id, $site_ids ) ) {
            // SITE ID EXISTS IN ARRAY AND ISN'T ORPHANED.
            return false;
        } else {
            // SITE ID DOESN'T EXIST IN ARRAY AND IS ORPHANED.
            return true;
        }
    }
}
