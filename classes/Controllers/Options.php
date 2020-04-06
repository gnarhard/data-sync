<?php

namespace DataSync\Controllers;

use DataSync\Routes\OptionsRoutes;
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
		require_once DATA_SYNC_PATH . 'public/views/admin/options/page.php';
		require_once DATA_SYNC_PATH . 'public/views/admin/options/fields.php';
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register' ] );
		add_action( 'cptui_after_delete_post_type', [ $this, 'delete_post_type_option_data' ] );
		new OptionsRoutes( $this );
	}

	/**
	 * Get saved options
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public static function get( WP_REST_Request $request ) {
		$key = $request->get_url_params()[ self::$option_key ];

		if ( 'secret_key' === $key ) {
			$response = new WP_REST_Response();
			$response->set_status( 401 );

			return $response;
		}

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
				if ( 'post' === $post_type ) {
					$options->push_enabled_post_types['post'] = array( 'post' => array() );
				} else {
					$options->push_enabled_post_types[ $post_type ] = $cpt_data[ $post_type ];
				}
			}
		}

		$options->push_enabled_post_types_array       = get_option( 'push_enabled_post_types' );
		$options->enable_new_cpts                     = (bool) get_option( 'enable_new_cpts' );
		$options->overwrite_receiver_post_on_conflict = (bool) get_option( 'overwrite_receiver_post_on_conflict' );
		$options->debug                               = (bool) get_option( 'debug' );
		$options->show_body_responses                 = (bool) get_option( 'show_body_response' );

		return $options;
	}

	public static function receiver() {
		$option_keys = array(
			'notified_users',
			'enabled_post_types',
		);

		return Options::get_all( $option_keys );
	}

	public static function get_all( array $option_keys ) {
		$options = new stdClass();

		foreach ( $option_keys as $key ) {
			$options->$key = get_option( $key );
		}

		return $options;
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
			wp_send_json_success( $data );
		} else {
			$logs = new Logs();
			$logs->set( 'ERROR: Options not saved.', true );
			wp_send_json_error();
		}
	}

	/**
	 * Add admin menu
	 */
	public function admin_menu() {
		add_menu_page( 'Data Sync', 'Data Sync', 'manage_options', 'data-sync-options', $this->view_namespace . '\data_sync_options_page', 'dashicons-networking' );
	}

	public function get_settings_tab_html( WP_REST_Request $request ) {
		$settings_request = $request->get_param( 'tab' );
		$settings_content = new stdClass();

		if ( 'syndicated_posts' === $settings_request ) {
			include_once DATA_SYNC_PATH . 'public/views/admin/options/synced-posts-table.php';
			\DataSync\display_syndicated_posts_table();
		} elseif ( 'connected_sites' === $settings_request ) {
			include_once DATA_SYNC_PATH . 'public/views/admin/options/connected-sites.php';
			\DataSync\display_connected_sites();
		} elseif ( 'enabled_post_types' === $settings_request ) {
			include_once DATA_SYNC_PATH . 'public/views/admin/options/enabled-post-types-dashboard.php';
			\DataSync\display_enabled_post_types();
		} elseif ( 'templates' === $settings_request ) {
			include_once DATA_SYNC_PATH . 'public/views/admin/options/template-sync.php';
			\DataSync\display_synced_templates();
		} elseif ( 'awareness_messages' === $settings_request ) {
			include_once DATA_SYNC_PATH . 'public/views/admin/options/fields.php';
			\DataSync\display_awareness_messages();
		}
	}


	public static function prevalidate( $receiver_prevalidation_data ) {
		$prevalidation_data         = new stdClass();
		$prevalidation_data->source = new stdClass();
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! \is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
			$prevalidation_data->source->acf = false;
		} else {
			$prevalidation_data->source->acf = true;
		}

		if ( ! \is_plugin_active( 'custom-post-type-ui/custom-post-type-ui.php' ) ) {
			$prevalidation_data->source->cptui = false;
		} else {
			$prevalidation_data->source->cptui = true;
		}

		$plugins = get_plugins();

		$source_acf_version   = $plugins['advanced-custom-fields-pro/acf.php']['Version'];
		$source_cptui_version = $plugins['custom-post-type-ui/custom-post-type-ui.php']['Version'];
		$source_wp_version    = get_bloginfo( 'version' );

		if ( ! is_wp_error( $receiver_prevalidation_data ) ) {
			$prevalidation_data->receiver = array();
			$index                        = 0;

			foreach ( $receiver_prevalidation_data as $site_plugin_data ) {
				$prevalidation_data->receiver[ $index ]          = new stdClass();
				$prevalidation_data->receiver[ $index ]->site_id = (int) $site_plugin_data->site_id;
				$prevalidation_data->receiver[ $index ]->data    = $site_plugin_data;

				if ( $source_wp_version !== $site_plugin_data->versions->wp ) {
					$prevalidation_data->receiver[ $index ]->wp_version_synced = false;
				} else {
					$prevalidation_data->receiver[ $index ]->wp_version_synced = true;
				}

				if ( $source_acf_version !== $site_plugin_data->versions->acf ) {
					$prevalidation_data->receiver[ $index ]->acf_version_synced = false;
				} else {
					$prevalidation_data->receiver[ $index ]->acf_version_synced = true;
				}

				if ( $source_cptui_version !== $site_plugin_data->versions->cptui ) {
					$prevalidation_data->receiver[ $index ]->cptui_version_synced = false;
				} else {
					$prevalidation_data->receiver[ $index ]->cptui_version_synced = true;
				}

				$index ++;
			}

			return $prevalidation_data;
		} else {
			$logs = new Logs();
			$logs->set( $receiver_prevalidation_data->get_error_message(), true );
			wp_send_json_error( $receiver_prevalidation_data->get_error_message() );
		}

	}

	public static function validate_required_plugins_info( $site, $prevalidation_data ) {
		if ( ! $prevalidation_data->source->acf ) {
			$logs = new Logs();
			$logs->set( 'ACF needs to be installed or activated on this site.', true );

			return wp_send_json_error( 'ACF needs to be installed or activated on this site.' );
		}

		if ( ! $prevalidation_data->source->cptui ) {
			$logs = new Logs();
			$logs->set( 'CPTUI needs to be installed or activated on this site.', true );

			return wp_send_json_error( 'CPTUI needs to be installed or activated on this site.' );
		}

		foreach ( $prevalidation_data->receiver as $receiver_plugin_info ) {
			if ( (int) $site->id === $receiver_plugin_info->site_id ) {
				if ( ! $receiver_plugin_info->cptui_version_synced ) {
					$logs = new Logs();
					$logs->set( 'CPTUI\'s plugin version is different on <a target="_blank" href="' . $receiver_plugin_info->data->site_admin_url . '">' . $site->name . '</a>. Update receiver or source site to resolve this error.', true );

					return wp_send_json_error( 'CPTUI\'s plugin version is different on <a target="_blank" href="' . $receiver_plugin_info->data->site_admin_url . '">' . $site->name . '</a>. Update receiver or source site to resolve this error.' );
				}

				if ( ! $receiver_plugin_info->acf_version_synced ) {
					$logs = new Logs();
					$logs->set( 'ACF\'s plugin version is different on <a target="_blank" href="' . $receiver_plugin_info->data->site_admin_url . '">' . $site->name . '</a>. Update receiver or source site to resolve this error.', true );

					return wp_send_json_error( 'ACF\'s plugin version is different on <a target="_blank" href="' . $receiver_plugin_info->data->site_admin_url . '">' . $site->name . '</a>. Update receiver or source site to resolve this error.' );
				}

				if ( ! $receiver_plugin_info->wp_version_synced ) {
					$logs = new Logs();
					$logs->set( 'The WordPress core version is different on <a target="_blank" href="' . $receiver_plugin_info->data->site_admin_url . '">' . $site->name . '</a>. Update receiver or source site to resolve this error.', true );
					print_r( $receiver_plugin_info); die();
					return wp_send_json_error( 'The WordPress core version is different on <a target="_blank" href="' . $receiver_plugin_info->data->site_admin_url . '">' . $site->name . '</a>. Update receiver or source site to resolve this error.' );
				}
			}
		}

		return true;
	}


	public function delete_post_type_option_data( $data ) {
		$post_types                   = get_option( 'push_enabled_post_types' );
		$cleaned_post_types           = $post_types;
		$registered_post_types        = get_post_types( [
			'public' => true,
		], 'names', 'and' );
		$deleted_post_type            = $data['cpt_custom_post_type']['name'];
		$registered_custom_post_types = cptui_get_post_type_data();

		foreach ( $registered_custom_post_types as $registered_custom_post_type ) {
			$registered_post_types[] = $registered_custom_post_type['name'];
		}

		foreach ( $post_types as $key => $post_type ) {
			if ( $deleted_post_type === $post_type ) {
				unset( $cleaned_post_types[ $key ] );
			} elseif ( ! in_array( $post_type, $registered_post_types ) ) {
				unset( $cleaned_post_types[ $key ] );
			}
		}

		update_option( 'push_enabled_post_types', $cleaned_post_types );

		return $data;
	}


	/**
	 * Add sections and options to Data Sync WordPress admin options page.
	 * This also registers all options for updating.
	 */
	public function register() {
		add_settings_section( 'data_sync_options', '', null, 'data-sync-options' );

		add_settings_field( 'source_site', 'Source or Receiver?', $this->view_namespace . '\display_source_input', 'data-sync-options', 'data_sync_options' );
		register_setting( 'data_sync_options', 'source_site' );

		register_setting( 'data_sync_options', 'debug' );

		add_settings_field( 'awareness_messages', '', $this->view_namespace . '\awareness_messages', 'data-sync-options', 'data_sync_options' );

		$source = get_option( 'source_site' );

		if ( '1' === $source ) :

			add_settings_field( 'enable_new_cpts', 'Automatically Enable New Custom Post Types On Receiver', $this->view_namespace . '\display_auto_add_cpt_checkbox', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'enable_new_cpts' );

			add_settings_field( 'overwrite_receiver_post_on_conflict', 'Overwrite Receiver Post if Receiver Post Was More Recently Edited', $this->view_namespace . '\display_overwrite_receiver_post_checkbox', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'overwrite_receiver_post_on_conflict' );

			add_settings_field( 'debug', 'Debug', $this->view_namespace . '\display_debug_checkbox', 'data-sync-options', 'data_sync_options' );

			if ( '1' === get_option( 'debug' ) ) :

				add_settings_field( 'start_fresh', 'Start Fresh', $this->view_namespace . '\display_start_fresh_link', 'data-sync-options', 'data_sync_options' );
			endif;
		elseif ( '0' === $source ) :

			add_settings_field( 'secret_key', 'Secret Key', $this->view_namespace . '\display_secret_key', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'secret_key' );

			add_settings_field( 'notified_users', 'Notified Users', $this->view_namespace . '\display_notified_users', 'data-sync-options', 'data_sync_options' );
			register_setting( 'data_sync_options', 'notified_users' );

			register_setting( 'data_sync_options', 'enabled_post_types' );
			add_settings_field( 'enabled_post_types', 'Enabled Post Types', $this->view_namespace . '\display_post_types_to_accept', 'data-sync-options', 'data_sync_options' );

		endif;
	}
}
