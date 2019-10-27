import AJAX from '../../AJAX.es6.js'
import Success from './Success.es6'
import EnabledPostTypes from './EnabledPostTypes.es6'
import Logs from './Logs.es6'

class SyndicatedPosts {

  constructor () {
    SyndicatedPosts.refresh_view()
  }

  static init () {
    $ = jQuery

    $('.expand_post_details').unbind().click(function () {
      let id = $(this).data('id')
      $('#post-' + id).toggle()
    })

    if (document.getElementById('bulk_data_push')) {
      document.getElementById('bulk_data_push').onclick = function (e) {
        SyndicatedPosts.bulk_push(e)
      }
    }

    if (document.getElementById('refresh_syndicated_posts')) {
      document.getElementById('refresh_syndicated_posts').onclick = function (e) {
        document.getElementById('syndicated_posts_wrap').classList.add('hidden') // REMOVE TABLE FOR LOADING.
        document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.
        SyndicatedPosts.refresh_view()
      }
    }

  }

  static refresh_view () {
    AJAX.get_html(DataSync.api.url + '/settings_tab/syndicated_posts').then(function (result) {
      Success.display_html(result, 'syndicated_posts', 'Syndicated posts')
      document.querySelector('#syndicated_posts_wrap').classList.remove('hidden')
      SyndicatedPosts.init()
      SyndicatedPosts.single_post_actions_init()
    })
  }

  static bulk_push (e) {
    e.preventDefault()

    document.getElementById('syndicated_posts_wrap').classList.add('hidden') // REMOVE TABLE FOR LOADING.
    document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

    AJAX.get(DataSync.api.url + '/source_data/bulk_push').then(function (result) {
      SyndicatedPosts.refresh_view()
      Success.show_success_message(result, 'Posts')
      new EnabledPostTypes()
      if ( DataSync.options.debug ) {
        let logs = new Logs()
        logs.refresh_log();
      }
    })
  }

  static single_post_actions_init () {
    jQuery(function ($) {
      $('.wp_data_synced_post_status_icons .dashicons-editor-unlink').unbind().click(function () {

        let source_post_id = $(this).data('source-post-id')

        SyndicatedPosts.push_single_post_to_all_receivers(source_post_id)

      })

      $('.push_post_now').unbind().click(function (e) {

        e.preventDefault()

        let source_post_id = $(this).data('source-post-id')

        SyndicatedPosts.push_single_post_to_all_receivers(source_post_id)

      })

      $('.overwrite_single_receiver').unbind().click(function (e) {

        e.preventDefault()

        let receiver_site_id = $(this).data('receiver-site-id')
        let source_post_id = $(this).data('source-post-id')

        SyndicatedPosts.push_single_post_to_single_receiver(receiver_site_id, source_post_id)

      })

    })

  }

  static push_single_post_to_all_receivers (source_post_id) {
    document.getElementById('syndicated_posts_wrap').classList.add('hidden') // REMOVE TABLE FOR LOADING.
    document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

    AJAX.get(DataSync.api.url + '/source_data/overwrite/' + source_post_id).then(function (result) {
      SyndicatedPosts.refresh_view()
      Success.show_success_message(result, 'Post')
      new EnabledPostTypes()
      if ( DataSync.options.debug ) {
        let logs = new Logs()
        logs.refresh_log();
      }
    })
  }

  static push_single_post_to_single_receiver (receiver_site_id, source_post_id) {
    document.getElementById('syndicated_posts_wrap').classList.add('hidden') // REMOVE TABLE FOR LOADING.
    document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

    AJAX.get(DataSync.api.url + '/source_data/overwrite/' + source_post_id + '/' + +receiver_site_id).then(function (result) {
      SyndicatedPosts.refresh_view()
      Success.show_success_message(result, 'Post')
      new EnabledPostTypes()
      if ( DataSync.options.debug ) {
        let logs = new Logs()
        logs.refresh_log();
      }
    })
  }

}

export default SyndicatedPosts