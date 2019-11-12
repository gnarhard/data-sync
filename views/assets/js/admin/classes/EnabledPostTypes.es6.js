import AJAX from '../../AJAX.es6.js'
import Helpers from '../../Helpers.es6.js'
import Message from './Message.es6.js'
import Processes from './Processes.es6'

class EnabledPostTypes {
    constructor () {
        this.refresh_view()
        this.init()
    }

    init () {

        let self = this

        if (document.getElementById('save_push_enabled_post_types')) {
            document.getElementById('save_push_enabled_post_types').onclick = function (e) {
                e.preventDefault()

                let data = {}
                let input_name = document.getElementById('push_enabled_post_types').getAttribute('name').replace(/[^a-z0-9_]/gi, '')
                data = Helpers.getSelectValues(document.getElementById('push_enabled_post_types'))
                // console.log(data);
                AJAX.post(DataSync.api.url + '/options/push_enabled_post_types', data).then(function (result) {

                    let process = {
                        id: btoa(Math.random().toString()),
                        topic: 'Push-enabled post types',
                        running: true,
                    }
                    Processes.create(process)

                    let admin_message = {}
                    admin_message.process_id = process.id
                    admin_message.success = true
                    admin_message.topic = process.topic

                    if (result) {
                        admin_message.message = 'saved.'
                    } else {
                        admin_message.message = 'No data changed or there was an error.'
                    }

                    Message.admin_message(admin_message)
                })

            }
        }

    }

    refresh_view () {
        let self = this
        if (document.getElementById('enabled_post_types_wrap')) {
            AJAX.get_html(DataSync.api.url + '/settings_tab/enabled_post_types').then(function (result) {
                Message.display_html(result, 'enabled_post_types', 'Enabled post types')
                self.init()
            })
        }
    }
}

export default EnabledPostTypes