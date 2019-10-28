<?php

namespace DataSync;

use DataSync\Controllers\Load;

/**
 * Plugin Name: Data Sync
 * Version:     1.0.0
 * Description: Multi-site compatible WordPress data syndicator. Syndicates post data, post meta, custom post types, custom ACF fields and data, and Yoast data with multiple authenticated sites.
 * Author:      Copper Leaf Creative
 * Author URI:  https://copperleafcreative.com
 * Text Domain: data-sync
 * Domain Path: /languages/
 * License:     GPL v3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (! function_exists('add_filter')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

/**
 * @param $links
 *
 * Adds Settings link to the plugin on the plugin page
 *
 * @return array
 */
function add_settings_link($links)
{
    $my_links = array(
        '<a href="' . admin_url('options-general.php?page=data-sync-options') . '">Settings</a>',
    );

    return array_merge($links, $my_links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__ . '\add_settings_link');

if (! defined('DATA_SYNC_PATH')) {
    define('DATA_SYNC_PATH', plugin_dir_path(__FILE__));
}

if (! defined('DATA_SYNC_URL')) {
    define('DATA_SYNC_URL', plugin_dir_url(__FILE__));
}

if (! defined('DATA_SYNC_BASENAME')) {
    define('DATA_SYNC_BASENAME', 'data-sync');
}

if (! defined('DATA_SYNC_API_BASE_URL')) {
    define('DATA_SYNC_API_BASE_URL', 'data-sync/v1');
}

// Load the plugin classes.
if (file_exists(DATA_SYNC_PATH . 'vendor/autoload.php')) {
    require_once DATA_SYNC_PATH . 'vendor/autoload.php';
}

add_filter('https_local_ssl_verify', '__return_true');

new Load();
