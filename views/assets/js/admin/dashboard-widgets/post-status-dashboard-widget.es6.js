import AJAX from '../AJAX.es6.js';

document.addEventListener( 'DOMContentLoaded', function () {
	
	if ( document.getElementById( 'bulk_data_push' ) ) {
		document.getElementById( 'bulk_data_push' ).onclick = function ( e ) {
			e.preventDefault();
			
			// CHANGE ICONS TO SPINNING UPDATE ICON
			document.querySelectorAll( '.wp_data_synced_post_status_icons' ).forEach( function ( node ) {
				node.innerHTML = '<i class="dashicons dashicons-update"></i>';
			} );
			
			let sync_start_time = parseInt( Date.now() );
			let syncs_to_complete = document.querySelectorAll( '.wp_data_synced_post_status_icons' ).length;
			let connected_site_ids_strings = document.getElementById( 'sites_connected_info' ).getAttribute( 'data-ids' ).split( ',' );
			let connected_site_ids_int = [];
			connected_site_ids_strings.forEach( function ( id ) {
				connected_site_ids_int.push( parseInt( id ) );
			});
			
			let connected_site_count = connected_site_ids_int.length;
			
			// BEGIN DATA PUSH
			AJAX.get( DataSync.api.url + '/source_data/push' ).then( function ( push_result ) {
				console.log( push_result );
				
				// CLEAR AUTO-REFRESH INTERVAL IF IT ALREADY EXISTS
				if ( typeof post_status_interval !== 'undefined' ) {
					clearInterval( post_status_interval );
				}
				
				// CREATE AUTO-REFRESH INTERVAL
				let post_status_interval = setInterval( function () {
					
					// GET ALL SYNCED POSTS
					AJAX.get( DataSync.api.url + '/synced_posts/all' ).then( function ( synced_posts ) {
						
						let sites_left_to_check = connected_site_count;
						
						// LOOP THROUGH CONNECTED SITE IDS
						connected_site_ids_strings.forEach( function( connected_site_id ) {
							
							// DECREASE IMMEDIATELY TO MAKE SURE WE SHOW THE CHECK ICON WHEN WE'RE CHECKING LAST SERVER
							sites_left_to_check--;
							
							// FIND INDEXES OF SYNCED POSTS THAT MATCH THE CONNECTED SITE ID
							let array_indexes = getAllIndexesWhere( synced_posts, 'receiver_site_id', connected_site_id );
							
							// LOOP THROUGH SYNCED POSTS WITH MATCHED CONNECTED SITE IDS
							array_indexes.forEach( function( i ) {
								
								let synced_post = synced_posts[i];
								let receiver_site_id = parseInt( synced_post.receiver_site_id );
								
								// SET LAST UPDATED DATE STRING
								let post_modified_date = new Date( synced_post.date_modified ).toLocaleString( 'en-US', { timeZone: 'America/Denver' } );
								
								// SET LAST UPDATED DATE TIMESTAMP TO COMPARE WITH START TIME TO MAKE SURE IT'S THE MOST UP-TO-DATE RESULT
								let post_modified_date_timestamp = parseInt( new Date( synced_post.date_modified ).getTime() );
								
								// IF MODIFIED DATE IS NEWER THAN THE TIME THE SYNC STARTED
								if ( post_modified_date_timestamp > sync_start_time ) {
									
									let synced_post_id = parseInt( synced_post.source_post_id );
									
									// IF RECEIVER SITE ID MATCHES A CONNECTED SITE ID
									if ( -1 !== connected_site_ids_int.indexOf( receiver_site_id ) ) {
										
										// INDICATE THAT A RECEIVER WAS SYNCED, BUT NOT ALL OF THEM
										document.getElementById( 'synced_post-' + synced_post_id ).getElementsByClassName( 'wp_data_synced_post_status_icons' )[0].innerHTML = '<i class="dashicons dashicons-info" title="Partially synced. Please allow time for the data to propogate. Some posts may have failed to sync with a connected site because the post type isn\'t enabled on the receiver or there was an error."></i>';
										
										if ( 0 === sites_left_to_check ) {
											
											// INDICATE THAT ALL RECEIVERS HAVE BEEN SYNCED
											document.getElementById( 'synced_post-' + synced_post_id ).getElementsByClassName( 'wp_data_synced_post_status_icons' )[0].innerHTML = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
											
											syncs_to_complete--;
											if ( 0 === syncs_to_complete ) {
												clearInterval( post_status_interval );
											}
										}
										
										// UPDATE SYNCED TIME
										document.getElementById( 'synced_post-' + synced_post_id ).getElementsByClassName( 'wp_data_synced_post_status_synced_time' )[0].innerHTML = synced_post.date_modified;
									}
									
								}
								
							});
							
						});
						
					} );
				}, 3000 );
				
			} );
			
		};
	}
	
	if ( document.getElementById( 'template_push' ) ) {
		document.getElementById( 'template_push' ).onclick = function ( e ) {
			e.preventDefault();
			AJAX.post( DataSync.api.url + '/templates/sync' ).then( function ( result ) {
				console.log( result );
			} );
		};
	}
	
} );

jQuery( function ( $ ) {
	$( document ).ready( function () {
		$( '#data_sync_status_tabs' ).tabs();
	} );
} );

function getAllIndexesWhere(arr, key, val) {
	var indexes = [], i;
	for(i = 0; i < arr.length; i++) {
		// console.log(arr[i], arr[i][key], val);
		if (arr[i][key] === val) {
			indexes.push( i );
		}
	}
	
	return indexes;
}
