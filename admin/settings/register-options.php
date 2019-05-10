<?php namespace DataSync;

add_action('admin_init', __NAMESPACE__ . '\add_options');

function add_options() {

	// SECTIONS
	add_settings_section( "data_sync_settings", "", false, 'data-sync-settings' );

	///////////// OPTIONS /////////////////////////////

	/// SOURCE OR RECEIVER
	add_settings_field( "source_site", "Source or Receiver?", __NAMESPACE__ . "\display_source_input", 'data-sync-settings', "data_sync_settings" );
	register_setting( "data_sync_settings", "source_site" );


  $source = get_option( 'source_site' );

  if ($source === '1') {

    /////// SOURCE OPTIONS ///////


    // Connected Sites
    // blogname, Site ID, URL, date connected, remove button, connect new

    // Push new template file - cpt-templates.php
    add_settings_field( "push_template", "Push Template to Receivers", __NAMESPACE__ . "\display_push_template_button", 'data-sync-settings', "data_sync_settings" );

    // Select which Post Types syndicate to which Receiver sites.
    add_settings_field( "push_enabled_post_types", "Push-Enabled Post Types", __NAMESPACE__ . "\display_push_enabled_post_types", 'data-sync-settings', "data_sync_settings" );
    register_setting( "data_sync_settings", "display_post_types_with_push_perm" );

    //	A “manual re-push” function will allow content to be selected at the Source and re-pushed to all receiving sites.  This is necessary to push out bulk uploaded / updated content.
    add_settings_field( "bulk_data_push", "Push All Data to Receivers", __NAMESPACE__ . "\display_bulk_data_push_button", 'data-sync-settings', "data_sync_settings" );

    // Error log
    add_settings_field( "error_log", "Error Log", __NAMESPACE__ . "\display_error_log", 'data-sync-settings', "data_sync_settings" );
  } else if ($source === '0') {

    /////////// RECEIVER ///////////////////////////////

    // SECURITY TOKEN
//	add_settings_field( "security_token", "Security Token", "display_token", 'data-sync-settings', "data_sync_settings" );
//	register_setting( "data_sync_settings", "security_token" );

    /// NOTIFIED USERS
    add_settings_field( "notified_users", "Notified Users", __NAMESPACE__ . "\display_notified_users", 'data-sync-settings', "data_sync_settings" );
    register_setting( "data_sync_settings", "notified_users" );

    /// POST TYPES TO ACCEPT
    register_setting( "data_sync_settings", "enabled_post_types");
    add_settings_field(
        'enabled_post_types', // id
        'Enabled Post Types', // title
        __NAMESPACE__ . '\display_post_types_to_accept', // callback
        'data-sync-settings', // page
        'data_sync_settings' // section
    );

    // POST TYPE PERMISSIONS
    $enabledPostTypes = get_option('enabled_post_types');
    if (($enabledPostTypes !== false) && ($enabledPostTypes !== '')) {
      if (count($enabledPostTypes) > 0) {
        foreach($enabledPostTypes as $post_type) {

          $post_type_object = get_post_type_object( $post_type );

          add_settings_field( $post_type_object->name."_perms", $post_type_object->label . " Permissions", __NAMESPACE__ . "\display_post_type_permissions_settings", 'data-sync-settings', "data_sync_settings", array($post_type_object) );
          register_setting( "data_sync_settings", $post_type_object->name."_perms");

        }
      }
    }

    // PULL DATA BUTTON
    add_settings_field( "pull_data", "Pull All Data From Source", __NAMESPACE__ . "\display_pull_data_button", 'data-sync-settings', "data_sync_settings" );

  }







}