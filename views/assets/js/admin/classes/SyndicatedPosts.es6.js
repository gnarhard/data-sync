import AJAX from '../../AJAX.es6.js'
import Success from './Success.es6';
import EnabledPostTypes from './EnabledPostTypes.es6'

class SyndicatedPosts {

  constructor() {
    this.refresh_view();
  }

  init() {
    $=jQuery;
    let self = this;

    $('.expand_post_details').unbind().click( function() {
      let id = $(this).data('id');
      $('#post-' + id).toggle();
    });

    if (document.getElementById('bulk_data_push')) {
      document.getElementById('bulk_data_push').onclick = function (e) {
        self.bulk_push(e);
      }
    }


  }

  refresh_view() {
    let self = this;
    if (document.getElementById('syndicated_posts_wrap')) {
      AJAX.get_html(DataSync.api.url + '/settings_tab/syndicated_posts' ).then(function( result) {
        Success.display_html( result, 'syndicated_posts', 'Syndicated posts' );
        self.init();
        self.single_post_actions_init()
      });
    }
  }

  bulk_push(e) {
    e.preventDefault()

    document.getElementById('syndicated_posts_wrap').remove(); // REMOVE TABLE FOR LOADING.
    document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden'); // SHOW LOADING SPINNER.

    AJAX.get(DataSync.api.url + '/source_data/bulk_push').then(function ( response ) {
      new SyndicatedPosts();
      Success.show_success_message( result, 'Posts');
      new EnabledPostTypes();
    })
  }

  single_post_actions_init() {
    let self = this;
    jQuery(function ($) {
      $('.wp_data_synced_post_status_icons .dashicons-editor-unlink').unbind().click(function () {

        // CHANGE ICON TO SPINNING UPDATE ICON
        let receiver_site_id = $(this).data('receiver-site-id')
        let source_post_id = $(this).data('source-post-id')

        self.push_single_post_to_all_receivers( receiver_site_id, source_post_id )

      })

      $('.push_post_now').unbind().click(function (e) {

        e.preventDefault();

        let source_post_id = $(this).data('source-post-id')

        self.push_single_post_to_all_receivers( source_post_id );

      })

      $('.overwrite_single_receiver').unbind().click(function (e) {

        e.preventDefault();

        let receiver_site_id = $(this).data('receiver-site-id')
        let source_post_id = $(this).data('source-post-id')

        self.push_single_post_to_single_receiver( receiver_site_id, source_post_id );

      })

    })

  }

  push_single_post_to_all_receivers( source_post_id ) {
    document.getElementById('syndicated_posts_wrap').remove(); // REMOVE TABLE FOR LOADING.
    document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden'); // SHOW LOADING SPINNER.

    AJAX.get(DataSync.api.url + '/source_data/overwrite/' + source_post_id).then(function (result) {
        new SyndicatedPosts();
        Success.show_success_message( result, 'Post');
        new EnabledPostTypes();
    })
  }

  push_single_post_to_single_receiver( receiver_site_id, source_post_id ) {
    document.getElementById('syndicated_posts_wrap').remove(); // REMOVE TABLE FOR LOADING.
    document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden'); // SHOW LOADING SPINNER.

    AJAX.get(DataSync.api.url + '/source_data/overwrite/' + source_post_id + '/' + + receiver_site_id).then(function (result) {
        new SyndicatedPosts();
        Success.show_success_message( result, 'Post');
        new EnabledPostTypes();
    })
  }

}

export default SyndicatedPosts;