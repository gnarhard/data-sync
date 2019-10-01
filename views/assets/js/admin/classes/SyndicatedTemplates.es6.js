import AJAX from '../../AJAX.es6.js'
import Success from './Success.es6';

class SyndicatedTemplates {

  constructor () {
    this.click_listeners()
  }

  push_templates (e) {

    e.preventDefault()

    document.getElementById('status_wrap').innerHTML += 'Pushing template <i class="dashicons dashicons-update"></i>'

    AJAX.post(DataSync.api.url + '/templates/start_sync').then(function (result) {
      console.log(result)
      document.getElementById('template_push').innerHTML += 'Template pushed successfully <i class="dashicons yes"></i>'
    })

  }

  click_listeners () {
    // PUSH TEMPLATES BUTTON PUSH
    if (document.getElementById('template_push')) {
      document.getElementById('template_push').onclick = function (e) {
        this.push_templates(e)
      }
    }

    // LOAD TEMPLATE LIST
    if (document.getElementById('templates_wrap')) {
      AJAX.get_html(DataSync.api.url + '/settings_tab/templates' ).then(function( result) {
        Success.display_html( result, 'templates' )
      });
    }
  }

}

export default SyndicatedTemplates;