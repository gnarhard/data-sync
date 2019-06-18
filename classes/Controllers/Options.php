<?php

namespace DataSync\Controllers;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use stdClass;

/**
 * Class Options
 * @package DataSync\Controllers
 *
 * Controller class for Options
 *
 * Doesn't need model because model is abstracted by WordPress core functionality
 */
class Options {

	/**
	 * Table prefix to save custom options
	 *
	 * @var string
	 */
	protected static $table_prefix = 'data_sync_';
	/**
	 * Option key to save options
	 *
	 * @var string
	 */
	protected static $option_key = 'option';
	/**
	 * Default options
	 *
	 * @var array
	 */
	protected static $defaults = array();

	public $view_namespace = 'DataSync';

	/**
	 * Options constructor.
	 */
	public function __construct() {
		require_once DATA_SYNC_PATH . 'views/admin/options/page.php';
		require_once DATA_SYNC_PATH . 'views/admin/options/fields.php';
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register' ] );
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Get saved options
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public static function get( WP_REST_Request $request ) {
//TODO: MAKE SECRET KEY UNREACHABLE
		$key = $request->get_url_params()[ self::$option_key ];

		if ( ! isset( $key ) ) {
			return rest_ensure_response( $key );
		}

		$response = new WP_REST_Response( get_option( $key, array() ) );
		$response->set_status( 201 );

		return $response;
	}

	public static function source() {

		$options = new stdClass();

		if ( function_exists( 'cptui_get_post_type_data' ) ) {

			$cpt_data = cptui_get_post_type_data();

			foreach ( get_option( 'push_enabled_post_types' ) as $post_type ) {
				$options->push_enabled_post_types[ $post_type ] = $cpt_data[ $post_type ];
			}
		}

		$options->enable_new_cpts = get_option( 'enable_new_cpts' );

		$response = new WP_REST_Response( $options );
		$response->set_status( 201 );

		return $response;

	}

	public static function receiver() {
		$option_keys = array(
			'notified_users',
			'enabled_post_types',
		);

		$enabled_post_types = get_option( 'enabled_post_types' );
		if ( ( $enabled_post_types ) && ( '' !== $enabled_post_types ) ) {
			if ( count( $enabled_post_types ) > 0 ) {
				foreach ( $enabled_post_types as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );
					$option_keys[]    = $post_type_object->name . '_perms';
				}
			}
		}

		return Options::get_all( $option_keys );
	}

	public static function get_all( array $option_keys ) {
		$options = new stdClass();

		foreach ( $option_keys as $key ) {
			$request = new WP_REST_Request();
			$request->set_method( 'GET' );
			$request->set_route( '/' . DATA_SYNC_API_BASE_URL . '/options/' . $key );
			$request->set_url_params( array( self::$option_key => $key ) );
			$request->set_query_params( array( 'nonce' => wp_create_nonce( 'data_sync_api' ) ) );

			$response      = rest_do_request( $request );
			$options->$key = $response->get_data();
		}

		$response = new WP_REST_Response( $options );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Save options
	 *
	 *
	 * @param array $options
	 */
	public static function save( WP_REST_Request $request ) {

		$key  = $request->get_url_params()[ self::$option_key ];
		$data = $request->get_json_params();

		$success = update_option( $key, $data );

		if ( $success ) {
			return wp_send_json_success();
		} else {
			$error = new Error();
			( $error ) ? $error->log( 'Options NOT saved.' . "\n" ) : null;

			return wp_send_json_error();
		}
	}

	/**
	 * Add admin menu
	 */
	public function admin_menu() {
		add_options_page(
			'Data Sync',
			'Data Sync',
			'manage_options',
			'data-sync-options',
			$this->view_namespace . '\data_sync_options_page'
		);
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/options/(?P<option>[a-zA-Z-_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'option' => array(
							'description' => 'Option key',
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'save' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'option' => array(
							'description' => 'Option key',
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( __NAMESPACE__ . '\Auth', 'permissions' ),
					'args'                => array(
						'option' => array(
							'description' => 'Option key',
							'type'        => 'string',
//							'validate_callback' => function ( $param, $request, $key ) {
//								return true;
//							},
						),
					),
				),
			)
		);
	}

	/**
	 * Add sections and options to Data Sync WordPress admin options page.
	 * This also registers all options for updating.
	 */
	public function register() {
		add_settings_section( 'data_sync_options', '', null, 'data-sync-options' );

		add_settings_field( 'source_site', 'Source or Receiver?', $this->view_namespace . '\display_source_input', 'data-sync-options', 'data_sync_options' );
		register_setting( 'data_sync_options', 'source_site' );

		$source = get_option( 'source_site' );

		if ( '1' === $source ) :

			add_settings_field( 'connected_sites', 'Connected Sites', $this->view_namespace . '\display_connected_sites', 'data-sync-options', 'data_sync_options' );

			add_settings_field( 'enable_new_cpts', 'Automatically Enable New Custom Post Types On Receiver', $this->view_namespace . '\display_auto_add_cpt_checkbox', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'enable_new_cpts' );

			add_settings_field( 'push_enabled_post_types', 'Push-Enabled Post Types', $this->view_namespace . '\display_push_enabled_post_types', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'push_enabled_post_types' );

		elseif ( '0' === $source ) :

			add_settings_field( 'secret_key', 'Secret Key', $this->view_namespace . '\display_secret_key', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'secret_key' );

			add_settings_field( 'notified_users', 'Notified Users', $this->view_namespace . '\display_notified_users', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'notified_users' );

			register_setting( 'data_sync_options', 'enabled_post_types' );
			add_settings_field(
				'enabled_post_types',
				'Enabled Post Types',
				$this->view_namespace . '\display_post_types_to_accept',
				'data-sync-options',
				'data_sync_options'
			);

			// TODO: Which user permission out of all permissions can edit content
			$enabled_post_types = get_option( 'enabled_post_types' );
			if ( ( $enabled_post_types ) && ( '' !== $enabled_post_types ) ) {
				if ( count( $enabled_post_types ) ) {
					foreach ( $enabled_post_types as $post_type ) {
						$post_type_object = get_post_type_object( $post_type );

//						add_settings_field( $post_type_object->name . '_perms', $post_type_object->label . ' Permissions', $this->view_namespace . '\display_post_type_permissions_options', 'data-sync-options', 'data_sync_options', array( $post_type_object ) );
//						register_setting( 'data_sync_options', $post_type_object->name . '_perms' );
					}
				}
			}

		endif;
	}

}