import AJAX from '../../AJAX.es6.js';
import Message from './Message.es6.js';
import SyndicatedPosts from './SyndicatedPosts.es6.js';
import EnabledPostTypes from './EnabledPostTypes.es6.js';
import Logs from './Logs.es6.js';
import Processes from './Processes.es6.js';
import Helpers from '../../Helpers.es6';


class ConnectedSites {

	constructor() {
		this.refresh_view();
	}


	static async get_all( process_id ) {

		let process = Processes.get( process_id );

		let sites = await ConnectedSites.get_sites();

		for ( const [ i, site ] of sites.entries() ) {
			const r = await ConnectedSites.get_rest_url( site );

			for ( const header of r.headers ) {
				if ( 'link' == header[0] ) {
					if ( header[1].includes( 'wp-json' ) ) {
						sites[ i ].api_url = Helpers.trailingslashit( site.url ) + 'wp-json/';
					} else if ( header[1].includes( '?rest_route=' ) ) {
						sites[ i ].api_url = Helpers.trailingslashit( site.url ) + 'index.php?rest_route=/';
					}
				}

			}
		}

		process.connected_sites = sites;
		Processes.set( process );

		let admin_message        = {};
		admin_message.process_id = process.id;
		admin_message.topic      = process.topic;
		admin_message.success    = true;
		admin_message.message    = 'Connected sites consolidated.';
		Message.admin_message( admin_message );

		return sites;

	}


	static async get_sites() {
		const r = await fetch( DataSync.api.url + '/connected_sites', {
			headers: {
				'X-WP-Nonce': DataSync.api.nonce
			},
		} );
		return await r.json();
	}


	static async get_rest_url( site ) {
		const r = await fetch( site.url, { method: 'HEAD' } );
		return await r;
	}


	static save() {

		let self             = this;
		var moment           = require( 'moment' );
		let data             = [];
		data[ 0 ]            = {};
		data[ 0 ].name       = document.getElementById( 'site_name' ).value;
		data[ 0 ].url        = document.getElementById( 'site_url' ).value;
		data[ 0 ].secret_key = document.getElementById( 'site_secret_key' ).value;

		let sync_start_date  = document.getElementById( 'site_sync_start' ).value + ' ' + moment().format( 'HH:mm' ) + ':00';
		data[ 0 ].sync_start = moment( sync_start_date ).utc().format();

		AJAX.post( DataSync.api.url + '/connected_sites', data ).then( ( result ) => {

			let process = {
				id: btoa( Math.random().toString() ), topic: 'Connected sites', running: true,
			};
			Processes.create( process );

			let admin_message        = {};
			admin_message.process_id = process.id;
			admin_message.success    = true;
			admin_message.topic      = process.topic;
			admin_message.message    = 'site connected.';
			Message.admin_message( admin_message );

			$ = jQuery;
			$( '.settings_page_data-sync-settings .lightbox_wrap' ).removeClass( 'display' );
			new ConnectedSites();
			new EnabledPostTypes();
			if ( DataSync.options.debug ) {
				let logs = new Logs();
				logs.refresh_log();
			}
		} );

		// window.location.reload()
	}


	static delete( site_id ) {

		let confirmed = window.confirm( 'Are you sure you want to delete this connected site?' );

		if ( confirmed ) {
			document.getElementById( 'site-' + site_id ).remove();

			AJAX.delete( DataSync.api.url + '/connected_sites/' + site_id ).then( function( response ) {
				if ( response.success ) {

					let process = {
						id: btoa( Math.random().toString() ), topic: 'Connected sites', running: true,
					};
					Processes.create( process );

					let admin_message        = {};
					admin_message.process_id = process.id;
					admin_message.success    = true;
					admin_message.topic      = process.topic;
					admin_message.message    = 'deleted.';
					Message.admin_message( admin_message );

					new SyndicatedPosts();
					new EnabledPostTypes();
					new Settings();
					if ( DataSync.options.debug ) {
						let logs = new Logs();
						logs.refresh_log();
					}
				}
			} );
		}

	}


	init() {
		let self = this;
		$        = jQuery;

		$( '#orphaned_site_toggle' ).unbind().click( () => {
			$( '.orphaned_sites' ).slideToggle( () => {
				$( '#orphaned_site_toggle .dashicons' ).toggleClass( 'dashicons-arrow-down-alt2' );
				$( '#orphaned_site_toggle .dashicons' ).toggleClass( 'dashicons-arrow-up-alt2' );
			} );
		} );

		// ADD SITE
		$( '#add_site' ).unbind().click( function( e ) {
			e.preventDefault();

			$( '#site_sync_start' ).datepicker( {
				dateFormat: 'yy-mm-dd'
			} );

			$( '.lightbox_wrap' ).addClass( 'display' );

			$( '#close' ).unbind().click( function() {
				$( '.lightbox_wrap' ).removeClass( 'display' );
			} );

			$( '#submit_site' ).unbind().click( function( e ) {
				e.preventDefault();
				ConnectedSites.save();
			} );
		} );

		$( '.remove_site' ).unbind().click( function( e ) {
			let site_id = parseInt( $( this ).parent().attr( 'id' ).split( 'site-' )[ 1 ] );
			ConnectedSites.delete( site_id );
		} );

	}


	refresh_view() {
		let self = this;
		if ( document.getElementById( 'connected_sites_wrap' ) ) {
			AJAX.get_html( DataSync.api.url + '/settings_tab/connected_sites' ).then( function( result ) {
				Message.display_html( result, 'connected_sites', 'Connected site' );
				self.init();
			} );
		}
	}
}


export default ConnectedSites;