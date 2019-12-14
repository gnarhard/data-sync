import Message from './Message.es6.js';
import EnabledPostTypes from './EnabledPostTypes.es6.js';
import Logs from './Logs.es6.js';
import ConnectedSites from './ConnectedSites.es6.js';
import Settings from './Settings.es6.js';
import SyndicatedPosts from './SyndicatedPosts.es6.js';
import Processes from './Processes.es6.js';

class Sync
{

	constructor()
	{

	}

	async show_posts( process_id )
	{

		let process = Processes.get( process_id );

		let admin_message = {};
		admin_message.process_id = process_id;
		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Building posts table. . .';
		Message.admin_message( admin_message );

		let syndicated_posts = new SyndicatedPosts();

		// BULK REFRESH
		for ( const [ index, post ] of process.data.source_data.posts.entries() ) {
			process.data.post_to_get = post;
			Processes.set( process );

			await this.get_syndicated_post_details( post.ID, process.data )
				.then( retrieved_post => syndicated_posts.display_refreshed_post( retrieved_post, process_id ) );
		}

	}

	async get_syndicated_post_details( post_id, data )
	{
		const response = await fetch(
			DataSync.api.url + '/syndicated_post/' + post_id, {
				method: 'POST',
				headers: {
					'Content-Type': 'text/html; charset=utf-8',
					'X-WP-Nonce': DataSync.api.nonce
				},
				body: JSON.stringify( data )
			}
		);
		return await response.text();
	}

	async get_source_data( process_id )
	{

		let data = {};

		let process = Processes.get( process_id );

		let admin_message = {};
		admin_message.process_id = process_id;
		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Getting data from source. . .';
		Message.admin_message( admin_message );

		let response = {};

		if ( false === process.source_post_id ) {
			response = await fetch( DataSync.api.url + '/source_data/load/0' ); // LOAD BULK
		} else {
			response = await fetch( DataSync.api.url + '/source_data/load/' + process.source_post_id );
		}

		return await response.json()
			.catch( message => Message.handle_error( message, process ) );
	}

	get_receiver_data( process_id )
	{

		let admin_message = {};
		admin_message.process_id = process_id;
		let process = Processes.get( process_id );

		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Getting data from receivers. . .';
		Message.admin_message( admin_message );

		let receiver_data_requests = [];

		for ( const [ index, site ] of process.data.source_data.connected_sites.entries() ) {
			receiver_data_requests.push( fetch( site.url + '/wp-json/data-sync/v1/receiver/get_data' ) );
		}

		return Promise.all( receiver_data_requests ) // send all requests for package
			.then( responses => {return responses;} ) // all responses are resolved successfully
			.then( responses => Promise.all( responses.map( r => r.json() ) ) )// map array of responses into array of response.json() to read their content
			.then( receiver_packages => {return receiver_packages;} )
			.catch( message => Message.handle_error( message, process ) );
	}

	async get_posts( process_id )
	{

		let process = Processes.get( process_id );
		// console.log(process)

		if ( false === process.source_post_id ) {
			const response = await fetch( DataSync.api.url + '/posts/all' );
			return await response.json()
				.catch( message => Message.handle_error( message, process ) );
		} else {
			const response = await fetch( DataSync.api.url + '/posts/' + process.source_post_id );
			return await response.json()
				.catch( message => Message.handle_error( message, process ) );
		}
	}

	start( process_id )
	{

		let process = Processes.get( process_id );

		process.running = true;
		Processes.set( process );

		let admin_message = {};
		admin_message.process_id = process.id;
		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = '<i class="dashicons dashicons-networking"></i> SYNDICATING';
		Message.admin_message( admin_message );

		this.consolidate( process.id )
			.then( () => this.prevalidate( process ) )
			.then( prevalidation => {
				if ( !prevalidation.success ) {
					let admin_message = {};
					admin_message.process_id = process.id;
					admin_message.topic = process.topic;
					admin_message.success = false;
					admin_message.message = prevalidation.data;

					Message.handle_error( admin_message, process );
					new Settings();
					if ( DataSync.options.debug ) {
						let logs = new Logs();
						logs.refresh_log();
					}
				} else {

					let admin_message = {};
					admin_message.process_id = process.id;
					admin_message.topic = process.topic;
					admin_message.success = true;
					admin_message.message = 'Pre-validation successful. Gathering source posts. . .';

					Message.admin_message( admin_message );

					this.send_posts_to_receivers( process.id )
						.then( () => {return Processes.get( process.id );} ) // refresh data
						.then( ( process ) => Logs.process_receiver_logs( process.receiver_data, process.id, process.topic ) )
						.then( () => this.process_receiver_synced_posts( process.id ) )
						.then( () => this.consolidate_media( process.id ) )
						.then( ( consolidated_media_packages ) => this.send_media_to_receivers( consolidated_media_packages, process.id ) )
						.then( () => {return Processes.get( process.id );} ) // refresh data
						.then( ( process ) => Logs.process_receiver_logs( process.media_sync_responses, process.id, process.topic ) )
						.then( () => this.process_receiver_synced_posts( process.id ) )
						.then( () => this.update_post_settings( process.id ) )
						.then( () => {
							process.running = false;
							Processes.set( process );

							let syndicated_posts = new SyndicatedPosts();
							syndicated_posts.refresh_view( process.id );

							$ = jQuery;

							if ( false === process.source_post_id ) {
								$( '#' + process.button_id ).removeAttr( 'disabled' );
								// $('#' + process.button_id).removeClass('loading');
							}

							new EnabledPostTypes();

							let admin_message = {};
							admin_message.process_id = process.id;
							admin_message.topic = process.topic;
							admin_message.success = true;
							admin_message.message = '<span class="dashicons dashicons-yes-alt"></span> Syndication complete!';
							Message.admin_message( admin_message );

						} )
						.catch( message => Message.handle_error( message, process ) );
				}
			} )
			.catch( message => Message.handle_error( message, process ) );

	}

	async update_post_settings( process_id )
	{
		let process = Processes.get( process_id );
		let admin_message = {};
		admin_message.process_id = process.id;
		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Turning off "Override Receiver Yoast" setting.';
		Message.admin_message( admin_message );

		const response = await fetch( DataSync.api.url + '/posts/update_post_settings', {
			method: 'POST',
			body: JSON.stringify( process )
		} );
		return await response.json()
			.catch( message => Message.handle_error( message, process ) );
	}

	consolidate( process_id )
	{

		let process = Processes.get( process_id );

		return this.get_posts( process_id )
			.then( posts => {

				process = Processes.get( process_id );
				process.posts = [];
				if ( false === process.source_post_id ) {
					process.posts = posts;
				} else {
					process.posts[ 0 ] = posts;
				}

				Processes.set( process );

				let admin_message = {};
				admin_message.process_id = process.id;
				admin_message.topic = process.topic;
				admin_message.success = true;
				if ( false === process.source_post_id ) {
					admin_message.message = 'Source posts consolidated. Gathering connected sites. . .';
				} else {
					admin_message.message = 'Source post consolidated. Gathering connected sites. . .';
				}
				Message.admin_message( admin_message );

			} )

			// GET ALL SITES
			.then( () => ConnectedSites.get_all() )
			.then( connected_sites => {

				process = Processes.get( process_id );
				process.connected_sites = connected_sites;
				Processes.set( process );

				let admin_message = {};
				admin_message.process_id = process.id;
				admin_message.topic = process.topic;
				admin_message.success = true;
				admin_message.message = 'Connected sites consolidated. Preparing data packages. . .';
				Message.admin_message( admin_message );
			} )

			// PREPARE AND CONSOLIDATE SOURCE PACKAGES
			.then( () => this.prepare_packages( process.id ) ) // prep requests for creating bulk packages to send to receivers
			.then( requests => {
				// console.log('Prepared post, options, and meta packages: ',requests)
				return requests;
			} )
			.then( requests => Promise.all( requests ) )// send all requests for package
			.then( responses => {return responses;} ) // all responses are resolved successfully
			.then( responses => Promise.all( responses.map( r => r.json() ) ) )// map array of responses into array of response.json() to read their content
			.then( prepped_source_packages => {
				process.prepped_source_packages = prepped_source_packages;
				Processes.set( process );

				let admin_message = {};
				admin_message.process_id = process.id;
				admin_message.topic = process.topic;
				admin_message.success = true;
				admin_message.message = 'All data from source ready to be sent, sending now. . .';
				Message.admin_message( admin_message );
			} );
	}

	consolidate_media( process_id )
	{

		let process = Processes.get( process_id );

		let admin_message = {};
		admin_message.process_id = process.id;

		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Preparing media items. . .';
		Message.admin_message( admin_message );

		let requests = this.prepare_media_packages( process_id ); // prep requests for creating bulk packages to send to receivers
		return Promise.all( requests ) // send all requests for package
			.then( responses => {return responses;} ) // all responses are resolved successfully
			.then( responses => Promise.all( responses.map( r => r.json() ) ) )// map array of responses into array of response.json() to read their content
			.then( prepared_site_packages => {
				let consolidated_packages = [];
				prepared_site_packages.forEach( site_packages => {
					site_packages.forEach( media_package => {consolidated_packages.push( media_package );} );
				} );
				return consolidated_packages;
			} )
			.then( consolidated_packages => {
				let admin_message = {};
				admin_message.process_id = process.id;

				admin_message.topic = process.topic;
				admin_message.success = true;
				admin_message.message = 'Media packages ready. <span id="process_' + process.id + '">0</span>/' + consolidated_packages.length + ' media items synced.';
				Message.admin_message( admin_message );
				return consolidated_packages;
			} );
	}

	send_posts_to_receivers( process_id )
	{

		let process = Processes.get( process_id );
		process.prepped_source_packages.forEach( prepped_source_package => this.create_remote_request( prepped_source_package, process_id ) ); // create send requests with packages

		process = Processes.get( process_id ); // get new data

		return Promise.all( process.receiver_sync_requests )// send all packages to receivers
			.then( responses => { return responses;} ) // all responses are resolved successfully
			.then( responses => Promise.all( responses.map( r => r.json() ) ) )// map array of responses into array of response.json() to read their content
			.then( receiver_data => {
				process.receiver_data = receiver_data;
				Processes.set( process );

				process.receiver_data.forEach( single_receiver_data => {
					let admin_message = {};
					admin_message.process_id = process.id;

					admin_message.topic = process.topic;
					admin_message.success = true;
					admin_message.message = single_receiver_data.data.message;
					Message.admin_message( admin_message );
				} );
			} )
			.catch( message => Message.handle_error( message, process ) );

	}

	prepare_packages( process_id )
	{

		let process = Processes.get( process_id );

		let requests = [];
		process.receiver_sync_requests = []; // will be compiled in create_send_requests().
		Processes.set( process );

		if ( false === process.receiver_site_id ) {
			for ( const site of process.connected_sites ) {
				if ( false === process.source_post_id ) {
					requests.push( fetch( DataSync.api.url + '/source_data/prep/0/' + site.id ) );
				} else {
					requests.push( fetch( DataSync.api.url + '/source_data/prep/' + process.source_post_id + '/' + site.id ) );
				}
			}
		} else {
			requests.push( fetch( DataSync.api.url + '/source_data/prep/' + process.source_post_id + '/' + process.receiver_site_id ) );
		}

		return requests;
	}

	prepare_media_packages( process_id )
	{

		let process = Processes.get( process_id );
		let data = {};
		let requests = [];
		process.receiver_sync_requests = []; // will be compiled in create_send_requests().

		for ( const site of process.connected_sites ) {

			if ( ( false === process.receiver_site_id ) || ( parseInt( site.id ) === process.receiver_site_id ) ) {

				process.prepped_source_packages.forEach( prepped_source_package => {
					let decoded_package = JSON.parse( prepped_source_package );
					// console.log('Prepared media source packages: ',decoded_package)
					if ( parseInt( site.id ) === parseInt( decoded_package.receiver_site_id ) ) {
						data.site = site;
						data.posts = decoded_package.posts;
						requests.push( fetch( DataSync.api.url + '/media/prep', {
							method: 'POST',
							body: JSON.stringify( data )
						} ) );
					}
				} );
			}

		}

		return requests;
	}

	async send_media_to_receivers( prepared_media_packages, process_id )
	{

		let process = Processes.get( process_id );

		process.media_sync_responses = [];

		for ( const media_package of prepared_media_packages ) {
			await this.send_media( media_package )
				.then( media_sync_response => {
					process.media_sync_responses.push( media_sync_response );
					Processes.set( process );
					Message.update_status_count( process );
				} )
				.catch( message => Message.handle_error( message, process ) );
		}

		let admin_message = {};
		admin_message.process_id = process.id;

		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Media synced.';
		Message.admin_message( admin_message );

	}

	async send_media( media_package )
	{
		let source_package = JSON.parse( media_package );
		// console.log('Source package before create_remote_request(): ',source_package)

		const response = await fetch( source_package.receiver_site_url + '/wp-json/data-sync/v1/sync', {
			method: 'POST',
			body: JSON.stringify( source_package )
		} );
		return await response.json()
			.catch( message => Message.handle_error( message, process ) );
	}

	process_receiver_synced_posts( process_id )
	{

		let process = Processes.get( process_id );
		let receiver_synced_posts = [];

		// console.log('receiver_data for processing synced posts: ',this.receiver_data)
		process.receiver_data.forEach( single_receiver_data => receiver_synced_posts.push( single_receiver_data.data.synced_posts ) );
		return this.save_receiver_synced_posts( receiver_synced_posts )
			.then( synced_posts_response => {
				let admin_message = {};
				admin_message.process_id = process.id;

				admin_message.topic = process.topic;
				admin_message.success = true;
				admin_message.message = synced_posts_response.data.message;
				Message.admin_message( admin_message );
			} );
	}

	async save_receiver_synced_posts( synced_posts )
	{
		const response = await fetch( DataSync.api.url + '/sync_post', {
			method: 'POST',
			headers: {
				'X-WP-Nonce': DataSync.api.nonce
			},
			body: JSON.stringify( synced_posts )
		} );
		return await response.json()
			.catch( message => Message.handle_error( message, process ) );
	}

	async prevalidate( process )
	{

		let admin_message = {};
		admin_message.process_id = process.id;
		admin_message.topic = process.topic;
		admin_message.success = true;
		admin_message.message = 'Pre-validating receiver site compatibility.';
		Message.admin_message( admin_message );

		return this.prevalidate_receivers( process )
			.then( receiver_prevalidation_data => {
				process.receiver_prevalidation_data = receiver_prevalidation_data;
				Processes.set( process );
				return process;
			} )
			.then( ( process ) => this.verify_prevalidation( process ) )
			.catch( message => Message.handle_error( message, process ) );

	}

	async verify_prevalidation( process )
	{
		const response = await fetch( DataSync.api.url + '/prevalidate', {
			method: 'POST',
			body: JSON.stringify( process )
		} );
		return await response.json()
			.catch( message => Message.handle_error( message, process ) );
	}

	prevalidate_receivers( process )
	{

		let receiver_prevalidation_requests = [];

		process.connected_sites.forEach( site => {
			receiver_prevalidation_requests.push( fetch( site.url + '/wp-json/data-sync/v1/receiver/prevalidate' ) );
		} );

		return Promise.all( receiver_prevalidation_requests ) // send all requests for package
			.then( responses => {return responses;} ) // all responses are resolved successfully
			.then( responses => Promise.all( responses.map( r => r.json() ) ) )// map array of responses into array of response.json() to read their content
			.then( receiver_packages => {return receiver_packages;} );
	}

	create_remote_request( prepped_source_package, process_id )
	{

		let process = Processes.get( process_id );

		// DON'T ADD ANYTHING TO THIS FROM HERE ON OR IT WILL TRIP THE AUTH SIG CHECK.
		let source_package = JSON.parse( prepped_source_package );

		process.receiver_sync_requests.push(
			fetch( source_package.receiver_site_url + '/wp-json/data-sync/v1/sync', {
				method: 'POST',
				body: JSON.stringify( source_package )
			} )
		);

		Processes.set( process );
	}

}

export default Sync;