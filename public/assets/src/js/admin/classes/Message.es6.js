import SyndicatedPosts from './SyndicatedPosts.es6.js';
import Processes from './Processes.es6.js';

class Message
{

    constructor()
    {

    }

    static display_html( result, selector, topic )
    {
        let result_array = result.split('null');
        result_array.slice(-1)[ 0 ];
        let html = result_array.join(' ');

        if ( document.querySelector('#' + selector + ' .loading_spinner') ) {
            document.querySelector('#' + selector + ' .loading_spinner').
                classList.add('hidden');
        }
        document.getElementById(selector + '_wrap').innerHTML = html;

    }

    static admin_message( message )
    {
        if ( document.querySelector('#wpbody-content .notice') ) {
            Message.update_message(message);
        }
    }

    static update_message( result )
    {

        let html = '';

        if ( result.success ) {
            html = '<div class="notice updated notice-success process_' +
                result.process_id + '">';
        } else {
            html = '<div class="notice notice-error process_' +
                result.process_id + '">';
        }

        html += result.topic + ': ';
        html += result.message;

        html += '</div';

        $ = jQuery;
        $('#wpbody-content .notice .message').prepend(html);
        document.querySelector('#wpbody-content .notice').style.visibility = 'visible';
        Message.buttons_init();

    }

    static update_status_count( process )
    {
        let current_count = parseInt(document.getElementById('process_' +
            process.id).innerText);
        document.getElementById('process_' +
            process.id).innerText = current_count + 1;
    }

    static handle_error( error, process )
    {

        if ( '' == error.message ) {
            return error;
        }

        let result = {};
        result.success = false;
        result.message = error.message;
        result.topic = process.topic;

        if ( 'Unexpected token < in JSON at position 0' === result.message ) {
            result.message = 'Server error encountered.';
        }

        Message.admin_message(result);

        $ = jQuery;

        if ( process.source_post_id ) {
            $('#synced_post-' + process.source_post_id).removeClass('loading');
            $('#synced_post-' + process.source_post_id).addClass('flash_error');
            $('#post-' + process.source_post_id).toggle();
            $('#' + 'push_post_now_' + process.source_post_id).
                attr('disabled', false);
        } else {
            document.getElementById('bulk_data_push').
                removeAttribute('disabled');
            $('#bulk_data_push').removeClass('loading');
        }

        Processes.delete(process.id);

        if ( !error.ok ) {
            console.log(error);
            throw new Error(error.statusText);
            console.log(error.stack);
        }

        return error;

    }

    static buttons_init()
    {
        $ = jQuery;
        $('.notice-dismiss').unbind().click(function() {
            document.querySelector('#wpbody-content .notice').style.visibility = 'hidden';
            $(this).parent().removeClass('expand');
        });

        $('.show_more').unbind().click(function() {
            $(this).parent().toggleClass('expand');
            $('.show_more .dashicons').toggleClass('dashicons-arrow-down-alt2');
            $('.show_more .dashicons').toggleClass('dashicons-arrow-up-alt2');
        });
    }
}

export default Message;