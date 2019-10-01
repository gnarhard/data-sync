import AJAX from '../../AJAX.es6.js'

class Success {

  constructor () {

  }

  static display_html (result, selector, topic) {
    let result_array = result.split('null')
    result_array.slice(-1)[0]
    let html = result_array.join()
    document.getElementById(selector + '_wrap').innerHTML = html
    document.querySelector('#' + selector + ' .loading_spinner').classList.add('hidden')

    console.log(result.success)
    if (typeof result.success !== 'undefined') {
      Success.show_success_message(result, topic)
    }
  }

  static show_success_message (result, topic) {

    console.log(result)

    let data = {}
    data.result = result.success
    data.topic = topic
    AJAX.post(DataSync.api.url + '/admin_notice', data).then(function ( result ) {
      console.log(result);
      let node = document.createRange().createContextualFragment( result.data );
      document.getElementById('message').appendChild( node )
      Success.dismiss_button_init();
    })

  }

  static dismiss_button_init() {
      document.querySelector('#message .notice-dismiss').onclick = function (e) {
        this.parentNode.remove();
      }
  }
}

export default Success