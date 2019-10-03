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

    if (typeof result.success !== 'undefined') {
      Success.show_success_message(result, topic)
    }
  }

  static show_success_message (result, topic) {

    console.log(result)

    let data = {}
    data.result = result.success

    if (typeof result.data !== 'undefined') {
      data.message = result.data
    }

    data.topic = topic

    AJAX.post(DataSync.api.url + '/admin_notice', data).then(function (result) {
      console.log(result)
      let node = document.createRange().createContextualFragment(result.data)
      document.querySelector('#wpbody-content .wrap').appendChild(node)
      Success.dismiss_button_init()
    })

  }

  static dismiss_button_init () {
    $ = jQuery
    $('.notice-dismiss').unbind().click(function () {
      $(this).parent().remove()
    })
  }
}

export default Success