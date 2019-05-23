import AJAX from './AJAX.es6.js';

class ConnectedSites {

    static save() {
        $=jQuery;
        let data = [];
        data[0] = {};
        data[0].name = $( '#site_name' ).val();
        data[0].url = $( '#site_url' ).val();
        // data[0].date_connected = new Date().toLocaleString();

        AJAX.post( DataSync.api.url + '/connected_sites', data );

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