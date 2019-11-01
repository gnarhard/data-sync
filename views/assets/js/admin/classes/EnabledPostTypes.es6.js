import AJAX from '../../AJAX.es6.js'
import Helpers from '../../Helpers.es6.js'
import Message from './Message.es6.js'

class EnabledPostTypes {
  constructor () {
    this.refresh_view()
    this.init()
  }

  init () {

    let self = this;

    if (document.getElementById('save_push_enabled_post_types')) {
      document.getElementById('save_push_enabled_post_types').onclick = function (e) {
        e.preventDefault()

        let data = {}
        let input_name = document.getElementById('push_enabled_post_types').getAttribute('name').replace(/[^a-z0-9_]/gi, '')
        data = Helpers.getSelectValues(document.getElementById('push_enabled_post_types'))
        // console.log(data);
        AJAX.post(DataSync.api.url + '/options/push_enabled_post_types', data).then(function( result ) {
          Message.show_success_message( result, 'Enabled post types')
        });


      }
    }

  }

  refresh_view () {
    let self = this;
    if (document.getElementById('enabled_post_types_wrap')) {
      AJAX.get_html(DataSync.api.url + '/settings_tab/enabled_post_types').then(function (result) {
        Message.display_html(result, 'enabled_post_types', 'Enabled post types');
        self.init();
      })
    }
  }
}

export default EnabledPostTypes