<?php

namespace DataSync;

use DataSync\Models\ConnectedSite;

/**
 *
 */
function display_connected_sites() {
    $connected_sites = (array) ConnectedSite::get_all(); ?>
    <table id="connected_sites">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>URL</th>
            <th>Sync Start</th>
            <th>Remove</th>
        </tr>
        </thead>

        <tbody>
        <?php
        if ( is_array( $connected_sites ) ) {
            foreach ( $connected_sites as $site ) {
                $time = strtotime( $site->sync_start ); ?>
                <tr id="site-<?php echo esc_html( $site->id ); ?>">
                    <td id="id"><?php echo esc_html( $site->id ); ?></td>
                    <td id="name"><?php echo esc_html( $site->name ); ?></td>
                    <td id="url"><?php echo esc_url( $site->url ); ?></td>
                    <td id="sync_start"><?php echo esc_html( date( 'g:i a - F j, Y', $time ) ); ?></td>
                    <td id="site-<?php echo esc_html( $site->id ); ?>">
                        <span class="dashicons dashicons-trash remove_site"></span>
                    </td>
                </tr>
                <?php
            }
        } ?>
        </tbody>
    </table>
    <button class="btn button-primary" id="add_site">Add Site</button>
    <?php
    include_once DATA_SYNC_PATH . 'views/admin/options/connected-sites.php';
    \DataSync\display_connected_sites_modal();
}


/**
 *
 */
function display_connected_sites_modal() {
    ?>
    <div class="lightbox_wrap">
        <div class="add_site_modal">
            <a id="close">X</a>
            <h2>Add New Site</h2>
            <form>
                <div class="input_wrap">
                    <label for="name">Site Name</label>
                    <input type="text" name="name" value="" id="site_name"/>
                </div>
                <div class="input_wrap">
                    <label for="url">Site URL</label>
                    <input type="text" name="url" value="" id="site_url"/>
                </div>
                <div class="input_wrap">
                    <label for="url">Secret Key</label>
                    <input name="secret_key" id="site_secret_key" value=""/>
                </div>
                <div class="input_wrap">
                    <label for="sync_start">Sync Start</label>
                    <input name="sync_start" id="site_sync_start" value=""/>
                </div>
                <p class="submit">
                    <input type="submit" name="submit_site" id="submit_site" class="button button-primary" value="Add Site">
                </p>
            </form>
        </div>
    </div>
    <?php
}