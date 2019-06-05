import AJAX from './AJAX.es6.js';

class ConnectedSites {

    static save() {
        let data = [];
        data[0] = {};
        data[0].name = document.getElementById( 'site_name' ).value;
        data[0].url = document.getElementById( 'site_url' ).value;
        data[0].secret_key = document.getElementById( 'site_secret_key' ).value;

        AJAX.post( DataSync.api.url + '/connected_sites', data );

        $=jQuery;
        $( '.settings_page_data-sync-settings .lightbox_wrap' ).removeClass( 'display' );

        window.location.reload();
    }

    static get() {
// AJAX.get( DataSync.api.url + '/connected_sites' ).then(function( response ) {

        // let data = {};
        //
        // if ( response.length ) {
        //
        //     data.connected_sites = response;
        //     data.connected_sites.push({
        //         name: $( '#name' ).val(),
        //         url: $( '#url' ).val(),
        //         date_connected: new Date().toLocaleString()
        //     });
        //
        // } else {
        //
        //
        //
        // }


        // });
    }

    static delete( site_id ) {
        AJAX.delete( DataSync.api.url + '/connected_sites/' + site_id ).then(   function( response ) {
            if ( response.success ) {
                document.getElementById('site-'+site_id).remove();
            }
        });
    }
}

export default ConnectedSites;