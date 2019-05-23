import AJAX from './AJAX.es6.js';
import ConnectedSites from './ConnectedSites.es6.js';

jQuery( function ( $ ) {

    $( document ).ready( function () {

        connected_sites();
        bulk_data_push();

    } );

    function bulk_data_push() {
        document.getElementById('bulk_data_push').addEventListener('click', function( e ) {
            e.preventDefault();
            AJAX.get(DataSync.api.url + '/source_data/push' );
        }, false);
    }

    function connected_sites() {
        // ADD SITE
        $( '#add_site' ).unbind().click( function ( e ) {
            e.preventDefault();

            $( '.settings_page_data-sync-settings .lightbox_wrap' ).addClass( 'display' );

            $( '#close' ).unbind().click( function () {
                $( '.settings_page_data-sync-settings .lightbox_wrap' ).removeClass( 'display' );
            } );

            $( '#submit_site' ).unbind().click( function ( e ) {
                e.preventDefault();
                ConnectedSites.save();
            } );

        } );

        $( '.remove_site' ).unbind().click( function ( e ) {
            let site_id = parseInt($(this).parent().attr('id').split('site-')[1]);
            ConnectedSites.delete( site_id );
        });
    }

} );

