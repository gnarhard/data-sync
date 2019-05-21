<?php
/**
 * @file
 * Registers and displays sections and options for the Data Sync WordPress admin settings page
 * @package DataSync
 */

namespace DataSync;

add_action( 'admin_init', __NAMESPACE__ . '\add_options' );

/**
 * Add sections and options to Data Sync WordPress admin settings page.
 * This also registers all options for updating.
 */
function add_options() {
	add_settings_section( 'data_sync_settings', '', null, 'data-sync-settings' );

	add_settings_field( 'source_site', 'Source or Receiver?', __NAMESPACE__ . '\display_source_input', 'data-sync-settings', 'data_sync_settings' );
	register_setting( 'data_sync_settings', 'source_site' );

	$source = get_option( 'source_site' );

	if ( '1' === $source ) :

		add_settings_field( 'connected_sites', 'Connected Sites', __NAMESPACE__ . '\display_connected_sites', 'data-sync-settings', 'data_sync_settings' );
		register_setting( 'data_sync_settings', 'connected_sites' );

		add_settings_field( 'push_template', 'Push Template to Receivers', __NAMESPACE__ . '\display_push_template_button', 'data-sync-settings', 'data_sync_settings' );

		add_settings_field( 'bulk_data_push', 'Push All Data to Receivers', __NAMESPACE__ . '\display_bulk_data_push_button', 'data-sync-settings', 'data_sync_settings' );

		add_settings_field( 'push_enabled_post_types', 'Push-Enabled Post Types', __NAMESPACE__ . '\display_push_enabled_post_types', 'data-sync-settings', 'data_sync_settings' );
		register_setting( 'data_sync_settings', 'push_enabled_post_types' );

		add_settings_field( 'error_log', 'Error Log', __NAMESPACE__ . '\display_error_log', 'data-sync-settings', 'data_sync_settings' );
	elseif ( '0' === $source ) :

		add_settings_field( 'notified_users', 'Notified Users', __NAMESPACE__ . '\display_notified_users', 'data-sync-settings', 'data_sync_settings' );
		register_setting( 'data_sync_settings', 'notified_users' );

		register_setting( 'data_sync_settings', 'enabled_post_types' );
		add_settings_field(
			'enabled_post_types',
			'Enabled Post Types',
			__NAMESPACE__ . '\display_post_types_to_accept',
			'data-sync-settings',
			'data_sync_settings'
		);

		$enabled_post_types = get_option( 'enabled_post_types' );
		if ( ( $enabled_post_types ) && ( '' !== $enabled_post_types ) ) {
			if ( count( $enabled_post_types ) > 0 ) {
				foreach ( $enabled_post_types as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );

					add_settings_field( $post_type_object->name . '_perms', $post_type_object->label . ' Permissions', __NAMESPACE__ . '\display_post_type_permissions_settings', 'data-sync-settings', 'data_sync_settings', array( $post_type_object ) );
					register_setting( 'data_sync_settings', $post_type_object->name . '_perms' );
				}
			}
		}

		add_settings_field( 'pull_data', 'Pull All Data From Source', __NAMESPACE__ . '\display_pull_data_button', 'data-sync-settings', 'data_sync_settings' );

	endif;
}
