<?php namespace DataSync;

add_action('admin_menu', __NAMESPACE__ . '\admin_menu');

/**
 * Adds admin menu for plugin's settings page
 */
function admin_menu()
{
    add_options_page(
        'Data Sync',
        'Data Sync',
        'manage_options',
        'data-sync-settings',
        __NAMESPACE__ . '\data_sync_settings'
    );
}
