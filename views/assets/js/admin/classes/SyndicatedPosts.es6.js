import Message from './Message.es6.js'
import Sync from './Sync.es6.js'
import Processes from './Processes.es6'

class SyndicatedPosts {

    constructor () {

    }

    init () {
        $ = jQuery
        let self = this

        if (document.getElementById('bulk_data_push')) {
            document.getElementById('bulk_data_push').onclick = function (e) {
                e.preventDefault()

                document.getElementById('syndicated_posts_data').innerHTML = ''
                document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

                let process = {
                    id: btoa(Math.random().toString()),
                    source_post_id: false,
                    receiver_site_id: false,
                    topic: 'Bulk push',
                    running: true,
                }
                Processes.create(process)

                let sync = new Sync()
                sync.start(process.id)
            }
        }

        if (document.getElementById('refresh_syndicated_posts')) {
            document.getElementById('refresh_syndicated_posts').onclick = function (e) {
                document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.
                self.refresh_view()
            }
        }

    }

    refresh_view (process_id = false) {

        let process = false

        if (false === process_id) {
            process = {
                id: btoa(Math.random().toString()),
                source_post_id: false,
                receiver_site_id: false,
                topic: 'Loading',
                running: false,
            }
            Processes.create(process)
        } else {
            process = Processes.get(process_id)
        }

        let sync = new Sync()

        if (false === process.source_post_id) {
            document.getElementById('syndicated_posts_data').innerHTML = ''
        }

        let admin_message = {}
        admin_message.process_id = process.id

        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = 'starting.'
        Message.admin_message(admin_message)

        sync.get_source_data(process.id)
            .then(source_data => {
                process.data = {}
                process.data.source_data = source_data
                Processes.set(process)
            })
            .then(() => sync.get_receiver_data(process.id))
            .then(receiver_data => {
                console.log(receiver_data)
                process.data.receiver_data = receiver_data
                Processes.set(process)
            })
            .then(() => sync.show_posts(process.id))
            .then(() => this.finish_refresh(process.id))
            .catch(message => Message.handle_error(message))

    }

    finish_refresh (process_id) {
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.add('hidden')

        let process = Processes.get(process_id)

        this.init()
        let admin_message = {}
        admin_message.process_id = process_id

        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = 'Ready.'
        Message.admin_message(admin_message)
    }

    display_refreshed_post (retrieved_post, process_id) {
        let result_array = retrieved_post.split('null')
        result_array.slice(-1)[0]
        let html = result_array.join(' ')
        $ = jQuery

        let process = Processes.get(process_id)

        if (false === process.source_post_id) {
            // BULK LOAD ALL
            $('#syndicated_posts_data').append(html)
        } else {
            // REFRESH SINGLE POST
            $('#post-' + process.source_post_id).remove()
            $('#synced_post-' + process.source_post_id).replaceWith(html)
            $('#synced_post-' + process.source_post_id).addClass('flash_success')
        }

        this.single_post_actions_init()
    }

    single_post_actions_init () {
        let self = this
        $ = jQuery

        $('.expand_post_details').unbind().click(e => $('#post-' + e.target.dataset.id).toggle())

        $('.push_post_now').unbind().click(e => {
                e.preventDefault()
                $('#synced_post-' + e.target.dataset.sourcePostId).addClass('loading')
                $('#post-' + e.target.dataset.sourcePostId).addClass('loading')

                let process = {
                    id: btoa(e.target.dataset.sourcePostId),
                    source_post_id: parseInt(e.target.dataset.sourcePostId),
                    receiver_site_id: false,
                    topic: 'Post ' + parseInt(e.target.dataset.sourcePostId),
                    running: true,
                }
                Processes.create(process)

                let sync = new Sync()
                sync.start(process.id)

            }
        )

        $('.overwrite_single_receiver').unbind().click(e => {
                e.preventDefault()
                $('#synced_post-' + e.target.dataset.sourcePostId).addClass('loading')
                $('#post-' + e.target.dataset.sourcePostId).addClass('loading')

                let process = {
                    id: btoa(e.target.dataset.sourcePostId),
                    source_post_id: parseInt(e.target.dataset.sourcePostId),
                    receiver_site_id: parseInt(e.target.dataset.receiverSiteId),
                    topic: 'Post ' + parseInt(e.target.dataset.sourcePostId),
                    running: true,
                }
                Processes.create(process)

                let sync = new Sync()
                sync.start(process.id)

            }
        )

    }

}

export default SyndicatedPosts