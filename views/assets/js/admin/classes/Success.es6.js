import AJAX from '../../AJAX.es6.js'

class Success {

    constructor () {

    }

    static display_html (result, selector, topic) {
        let result_array = result.split('null')
        result_array.slice(-1)[0]
        let html = result_array.join(' ')

        document.querySelector('#' + selector + ' .loading_spinner').classList.add('hidden')
        document.getElementById(selector + '_wrap').innerHTML = html

        if (typeof result.success !== 'undefined') {
            Success.show_success_message(result, topic)
        }
    }

    static show_message (result) {

        let data = {}

        if (typeof result.code !== 'undefined') {
            data = Success.show_error_message(result, topic)
        } else {
            data.success = result.success
            data.topic = topic

            if (typeof result.data !== 'undefined') {
                data.message = result.message // used to be .data
            }

        }

        AJAX.post(DataSync.api.url + '/admin_notice', data).then(function (result) {
            let node = document.createRange().createContextualFragment(result.data)
            document.querySelector('#wpbody-content').prepend(node)
            Success.dismiss_button_init()
        })

    }

    static show_error_message (result, topic) {
        let data = {}
        data.success = false
        data.message = result.message
        data.topic = topic
        return data
    }

    static dismiss_button_init () {
        $ = jQuery
        $('.notice-dismiss').unbind().click(function () {
            $(this).parent().remove()
        })
    }
}

export default Success