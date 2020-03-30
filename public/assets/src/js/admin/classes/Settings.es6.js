import AJAX from '../../AJAX.es6.js'
import Message from './Message.es6.js'

class Settings {

    constructor () {
        this.refresh_awareness_messages()
    }

    init () {
        document.querySelector('#settings').classList.remove('hidden')
    }

    refresh_awareness_messages () {
        let self = this
        if (document.getElementById('awareness_message_wrap')) {
            AJAX.get_html(DataSync.api.url + 'settings_tab/awareness_messages').then(result => {
                    if (result === 'null') {
                        result = '<span>Plugins up to date on all receivers.</span>' + result
                    }

                    Message.display_html(result, 'awareness_message', 'Awareness messages')
                    self.init()
                }
            )
        }
    }
}

export default Settings