import AJAX from '../AJAX.es6.js'

class SyndicatedPosts {

  constructor() {
    this.init();
    this.single_post_actions_init()
  }

  init() {
    $=jQuery;

    $('.expand_post_details').unbind().click( function() {
      let id = $(this).data('id');
      $('#post-' + id).toggle();
    });

    if (document.getElementById('bulk_data_push')) {
      document.getElementById('bulk_data_push').onclick = function (e) {
        e.preventDefault()

        document.getElementById('wp_data_sync_status').remove(); // REMOVE TABLE FOR LOADING.
        document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden'); // SHOW LOADING SPINNER.

        AJAX.get(DataSync.api.url + '/source_data/bulk_push').then(function ( response ) {
          console.log( response );
          AJAX.get_html(DataSync.api.url + '/settings_tab/syndicated_posts_table').then(function (result) {
            let result_array = result.split('null')
            result_array.slice(-1)[0]
            let html = result_array.join()
            document.getElementById('syndicated_posts_wrap').innerHTML = html
            document.querySelector('#syndicated_posts .loading_spinner').classList.add('hidden')
            this.single_post_actions_init();
          })
        })

      }
    }
  }

  single_post_actions_init() {
    let self = this;
    jQuery(function ($) {
      $('.wp_data_synced_post_status_icons .dashicons-editor-unlink').unbind().click(function () {

        // CHANGE ICON TO SPINNING UPDATE ICON
        $(this).parent()[0].innerHTML = '<i class="dashicons dashicons-update"></i>'
        let receiver_site_id = $(this).data('receiver-site-id')
        let source_post_id = $(this).data('source-post-id')

        self.push_single_post_to_all_receivers( receiver_site_id, source_post_id )

      })

      $('.push_post_now').unbind().click(function (e) {

        e.preventDefault();
        console.log('here');
        // CHANGE ICON TO SPINNING UPDATE ICON
        let row = $(this).parent().parent().parent()[0];

        // TODO: THIS WON'T WORK -- START
        // let status_column = row.getElementsByClassName('wp_data_synced_post_status_icons')[0]
        // status_column.innerHTML = '<i class="dashicons dashicons-update"></i>'
        // TODO: THIS WON'T WORK -- END

        let source_post_id = $(this).data('source-post-id')

        self.push_single_post_to_all_receivers( source_post_id );

      })

      $('.overwrite_single_receiver').unbind().click(function (e) {

        e.preventDefault();

        // CHANGE ICON TO SPINNING UPDATE ICON
        let row = $(this).parent().parent().parent()[0];
        // let status_column = row.getElementsByClassName('wp_data_synced_post_status_icons')[0]
        // status_column.innerHTML = '<i class="dashicons dashicons-update"></i>'

        let receiver_site_id = $(this).data('receiver-site-id')
        let source_post_id = $(this).data('source-post-id')

        self.push_single_post_to_single_receiver( receiver_site_id, source_post_id );

      })

    })

  }

  push_single_post_to_all_receivers( source_post_id ) {
    AJAX.get(DataSync.api.url + '/source_data/overwrite/' + source_post_id).then(function (result) {
      console.log(result)
      if (result) {
        this.show_success( result );
      }

    })
  }

  push_single_post_to_single_receiver( receiver_site_id, source_post_id ) {
    AJAX.get(DataSync.api.url + '/source_data/overwrite/' + source_post_id + '/' + + receiver_site_id).then(function (result) {
      console.log(result)
      if (result) {
        this.show_success( result );
      }

    })
  }

  show_success( result ) {

  }

}

export default SyndicatedPosts;