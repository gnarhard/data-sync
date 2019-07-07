import AJAX from '../AJAX.es6.js';

document.addEventListener( "DOMContentLoaded", function () {

  if ( document.getElementById( 'bulk_data_push' ) ) {
    document.getElementById( 'bulk_data_push' ).onclick = function ( e ) {
      e.preventDefault();

      let sync_start_time = Date.now();
      let syncs_to_complete = document.querySelectorAll('.wp_data_synced_post_status_icons').length;

      AJAX.get(DataSync.api.url + '/source_data/push' );

      document.querySelectorAll('.wp_data_synced_post_status_icons').forEach(function( node ) {
        node.innerHTML = '<i class="dashicons dashicons-update"></i>';
      });

      if ( typeof post_status_interval !== 'undefined' ) {
        clearInterval( post_status_interval );
      }

      let post_status_interval = setInterval( function() {
        AJAX.get(DataSync.api.url + '/synced_posts/all' ).then( function( result ) {
          result.forEach( function( synced_post ) {
            // console.log(synced_post);
            let last_updated_time = new Date( synced_post.date_modified ).getTime();

            if ( last_updated_time > sync_start_time ) {
              let synced_post_id = parseInt( synced_post.source_post_id );

              document.querySelectorAll('#wp_data_sync_status tbody tr').forEach(function( synced_post_row ) {

                if ( synced_post_id == synced_post_row.getAttribute( 'data-id' ) ) {
                  // TODO: CHECK THAT IT'S UPDATED ON ALL CONNECTED SITES - TEST WITH TWO RECEIVERS


                  document.getElementById('synced_post-' + synced_post_id ).getElementsByClassName('wp_data_synced_post_status_icons')[0].innerHTML = '<i class="dashicons dashicons-yes" title="Synced on all connected sites."></i>';

                  syncs_to_complete--;
                  if ( 0 === syncs_to_complete ) {
                    clearInterval( post_status_interval );
                  }
                }
              });
            }
          });

        });
      }, 5000 );

    }
  }



} );