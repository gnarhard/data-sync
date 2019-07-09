import AJAX from '../AJAX.es6.js';

document.addEventListener( "DOMContentLoaded", function () {

  if ( document.getElementById( 'bulk_data_push' ) ) {
    document.getElementById( 'bulk_data_push' ).onclick = function ( e ) {
      e.preventDefault();

      let sync_start_time = Date.now();
      let syncs_to_complete = document.querySelectorAll('.wp_data_synced_post_status_icons').length;
      let connected_site_ids = document.getElementById( 'sites_connected_info' ).getAttribute( 'data-ids' ).split(',');
      let connected_site_count = connected_site_ids.length

      AJAX.get(DataSync.api.url + '/source_data/push' );

      document.querySelectorAll('.wp_data_synced_post_status_icons').forEach(function( node ) {
        node.innerHTML = '<i class="dashicons dashicons-update"></i>';
      });

      if ( typeof post_status_interval !== 'undefined' ) {
        clearInterval( post_status_interval );
      }

      let post_status_interval = setInterval( function() {
        AJAX.get( DataSync.api.url + '/synced_posts/all' ).then( function( result ) {
          result.forEach( function( synced_post ) {

            let last_updated_time = new Date( synced_post.date_modified ).getTime();

            if ( last_updated_time > sync_start_time ) {
              let synced_post_id = parseInt( synced_post.source_post_id );

              document.querySelectorAll('#wp_data_sync_status tbody tr').forEach(function( synced_post_row ) {

                if ( synced_post_id == synced_post_row.getAttribute( 'data-id' ) ) {
                  // TODO: CHECK THAT ALL CONNECTED SITE IDS ARE IN THE DATA - TEST WITH TWO RECEIVERS
                  let receiver_site_id = synced_post.receiver_site_id;
                  let receivers_to_check = connected_site_count;
                  console.log( receivers_to_check, 'id: '+receiver_site_id);

                  if ( -1 !== connected_site_ids.indexOf( receiver_site_id ) ) {
                    receivers_to_check--;
// TODO: DOESN'T WORK YET
                    document.getElementById('synced_post-' + synced_post_id ).getElementsByClassName('wp_data_synced_post_status_icons')[0].innerHTML = '<i class="dashicons dashicons-info" title="Partially synced. Some posts may have failed to sync with a connected site because the post type isn\'t enabled on the receiver or there was an error."></i>';

                    document.getElementById('synced_post-' + synced_post_id ).getElementsByClassName('wp_data_synced_post_status_synced_time')[0].innerHTML = synced_post.date_modified;

// TODO: UPDATE SYNCED TEXT IN TABLE
                    if ( 0 === receivers_to_check ) {
                      document.getElementById('synced_post-' + synced_post_id ).getElementsByClassName('wp_data_synced_post_status_icons')[0].innerHTML = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';
                      document.getElementById('synced_post-' + synced_post_id ).getElementsByClassName('wp_data_synced_post_status_synced_time')[0].innerHTML = synced_post.date_modified;

                      syncs_to_complete--;
                      if ( 0 === syncs_to_complete ) {
                        clearInterval( post_status_interval );
                      }
                    }
                  }
                }
              });
            }
          });

        });
      }, 5000 );

    }
  }


  if ( document.getElementById( 'template_push' ) ) {
    document.getElementById('template_push').onclick = function (e) {
      e.preventDefault();
      AJAX.post( DataSync.api.url + '/templates/sync' ).then( function( result ) {
        console.log( result );
      });
    }
  }



} );