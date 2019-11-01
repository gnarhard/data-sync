import AJAX from '../../AJAX.es6.js'

class Message {

    constructor () {

    }

    static display_html (result, selector, topic) {
        let result_array = result.split('null')
        result_array.slice(-1)[0]
        let html = result_array.join(' ')

        document.querySelector('#' + selector + ' .loading_spinner').classList.add('hidden')
        document.getElementById(selector + '_wrap').innerHTML = html

        if (typeof result.success !== 'undefined') {
            Message.show_success_message(result, topic)
        }
    }

    static show_message (result) {

        AJAX.post(DataSync.api.url + '/admin_notice', result)
            .then(result => {
            let node = document.createRange().createContextualFragment(result.data)
            document.querySelector('#wpbody-content').prepend(node)
            Message.dismiss_button_init()
        })

    }

    static handle_error (error) {
        let result = {}
        result.success = false
        result.message = error.message

        console.log(result)

        Message.show_message(result);

        return result
    }

    static dismiss_button_init () {
        $ = jQuery
        $('.notice-dismiss').unbind().click(function () {
            $(this).parent().remove()
        })
    }
}

export default Message