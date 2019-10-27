import AJAX from '../../AJAX.es6.js';
import Success from './Success.es6';
import SyndicatedPosts from './SyndicatedPosts.es6'
import EnabledPostTypes from './EnabledPostTypes.es6'
import Logs from './Logs.es6'

class ConnectedSites {

    constructor() {
        this.refresh_view();
    }

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


    static delete( site_id ) {

        confirmed = window.confirm( 'Are you sure you want to delete this connected site?');

        if ( confirmed ) {
            document.getElementById('site-'+site_id).remove();

            AJAX.delete( DataSync.api.url + '/connected_sites/' + site_id ).then(   function( response ) {
                if ( response.success ) {
                    Success.show_success_message( response, 'Connected sites')

                    new SyndicatedPosts();
                    new EnabledPostTypes();
                    if ( DataSync.options.debug ) {
                        let logs = new Logs()
                        logs.refresh_log();
                    }
                }
            });
        }

    }

    init() {
        let self = this;
        $=jQuery;

        // ADD SITE
        $('#add_site').unbind().click(function (e) {
            e.preventDefault()

            $('.lightbox_wrap').addClass('display')

            $('#close').unbind().click(function () {
                $('.lightbox_wrap').removeClass('display')
            })

            $('#submit_site').unbind().click(function (e) {
                e.preventDefault()
                ConnectedSites.save()
            })
        })

        $('.remove_site').unbind().click(function (e) {
            let site_id = parseInt($(this).parent().attr('id').split('site-')[1])
            ConnectedSites.delete(site_id)
        })


    }

    refresh_view() {
        let self = this;
        if (document.getElementById('connected_sites_wrap')) {
            AJAX.get_html(DataSync.api.url + '/settings_tab/connected_sites' ).then(function( result) {
                Success.display_html( result, 'connected_sites', 'Connected sites' );
                self.init();
            });
        }
    }
}

export default ConnectedSites;