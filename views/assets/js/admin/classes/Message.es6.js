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

    static admin_message (result) {

        if (document.querySelector('#wpbody-content .notice')) {
            Message.update_message(result)
        }
    }

    static update_message (result) {

        let html = ''

        if (result.success) {
            html = '<div class="notice updated notice-success">'
        } else {
            html = '<div class="notice notice-warning">'
        }

        html += result.message

        html += '</div'

        $ = jQuery
        $('#wpbody-content .notice .message').prepend(html)
        document.querySelector('#wpbody-content .notice').style.visibility = 'visible'
        Message.buttons_init()

    }

    static handle_error (error) {
        let result = {}
        result.success = false
        result.message = error.message

        console.log(result)

        Message.admin_message(result)

        return result
    }

    static buttons_init () {
        $ = jQuery
        $('.notice-dismiss').unbind().click(function () {
            document.querySelector('#wpbody-content .notice').style.visibility = 'hidden'
            $(this).parent().removeClass('expand')
        })

        $('.show_more').unbind().click(function () {
            $(this).parent().toggleClass('expand')
            $('.show_more .dashicons').toggleClass('dashicons-arrow-down-alt2')
            $('.show_more .dashicons').toggleClass('dashicons-arrow-up-alt2')
        })
    }
}

export default Message