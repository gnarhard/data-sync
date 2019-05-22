import AJAX from './AJAX.es6.js';

jQuery( function ( $ ) {

    $( document ).ready( function () {

        // ADD SITE
        $( '#add_site' ).unbind().click( function ( e ) {
            e.preventDefault();

            $( '.settings_page_data-sync-settings .lightbox_wrap' ).addClass( 'display' );

            $( '#close' ).unbind().click( function () {
                $( '.settings_page_data-sync-settings .lightbox_wrap' ).removeClass( 'display' );
            } );

            $( '#submit_site' ).unbind().click( function ( e ) {
                e.preventDefault();

                AJAX.get( 'connected_sites' ).then(function( response ) {

                    let data = {};

                    if ( response.length ) {

                        data.connected_sites = response;
                        data.connected_sites.push({
                            name: $( '#name' ).val(),
                            url: $( '#url' ).val(),
                            date_connected: new Date().toLocaleString()
                        });

                    } else {

                        data.connected_sites = [];
                        data.connected_sites[0] = {};
                        data.connected_sites[0].name = $( '#site_name' ).val();
                        data.connected_sites[0].url = $( '#site_url' ).val();
                        data.connected_sites[0].date_connected = new Date().toLocaleString();

                    }

                    AJAX.post( data );

                    $( '.settings_page_data-sync-settings .lightbox_wrap' ).removeClass( 'display' );
                });



            } );


        } );

        $( '.remove_site' ).unbind().click( function ( e ) {
            let site_id = parseInt($(this).parent().attr('id').split('site-')[1]);
            console.log(site_id);
        });

    } );
} );