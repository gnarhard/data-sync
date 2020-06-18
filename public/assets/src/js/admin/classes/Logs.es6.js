import AJAX from '../../AJAX.es6.js';
import Message from './Message.es6.js';


class Logs {
	constructor() {
		this.init();
	}


	init() {
		let self = this;
		document.querySelector( '#debug_log .loading_spinner' ).classList.add( 'hidden' ); // hide spinner
		document.querySelector( '#debug_log' ).classList.remove( 'hidden' ); // show table

		if ( document.getElementById( 'error_log' ) ) {

			document.getElementById( 'refresh_error_log' ).onclick = function( e ) {
				self.refresh_log();
			};

			document.getElementById( 'delete_error_log' ).onclick = function( e ) {
				let confirmed = confirm( 'Are you sure you want to delete all log entries?' );
				if ( confirmed ) {
					self.delete_log();
				}
			};
		}
	}


	refresh_log() {

		let self = this;
		document.querySelector( '#debug_log' ).classList.add( 'hidden' ); // hide table
		document.querySelector( '#debug_log .loading_spinner' ).classList.remove( 'hidden' ); // show spinner

		AJAX.get( DataSync.api.url + '/log/get' ).then( function( result ) {
			document.querySelector( '#debug_log .loading_spinner' ).classList.add( 'hidden' ); // hide spinner
			if ( JSON.parse( result.html ) !== document.getElementById( 'error_log' ).innerHTML ) {
				document.getElementById( 'error_log' ).innerHTML = JSON.parse( result.html );
				self.init();
			}
		} );
	}


	delete_log() {

		let self = this;
		document.querySelector( '#debug_log' ).classList.add( 'hidden' ); // hide table
		document.querySelector( '#debug_log .loading_spinner' ).classList.remove( 'hidden' ); // show spinner

		AJAX.delete( DataSync.api.url + '/log/delete' ).then( function( response ) {
			let result     = {};
			result.success = response;
			self.refresh_log();

			let admin_message        = {};
			admin_message.success    = true;
			admin_message.process_id = btoa( Math.random().toString() );
			admin_message.topic      = 'Logs';
			admin_message.message    = 'purged.';
			Message.admin_message( admin_message );

			self.init();
		} );
	}


	static process_receiver_logs( receiver_data, process_id, topic ) {
		// console.log(receiver_data)

		let receiver_logs = [];
		receiver_data.forEach( single_receiver_data => receiver_logs.push( single_receiver_data.data.logs ) );

		let logs = new Logs();
		return logs.save( receiver_logs )
			.then( () => {
				// REFRESH LOGS
				if ( DataSync.options.debug ) {
					let logs = new Logs();
					logs.refresh_log();
				}

				let admin_message        = {};
				admin_message.success    = true;
				admin_message.process_id = process_id;
				admin_message.topic      = topic;
				admin_message.message    = 'Receiver logs retrieved and saved to source. Saving receiver synced posts. . .';
				Message.admin_message( admin_message );
			} );
	}


	async save( logs ) {
		const response = await fetch( DataSync.api.url + '/log/create', {
			method:  'POST', headers: {
				'X-WP-Nonce': DataSync.api.nonce
			}, body: JSON.stringify( logs )
		} );
		return await response.json();
	}

}


export default Logs;