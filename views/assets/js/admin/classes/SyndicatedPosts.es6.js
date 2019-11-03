import AJAX from '../../AJAX.es6.js'
import Message from './Message.es6.js'
import EnabledPostTypes from './EnabledPostTypes.es6'
import Logs from './Logs.es6'
import ConnectedSites from './ConnectedSites.es6'

class SyndicatedPosts {

    constructor () {
        this.refresh_view()
        // this.init() // so you don't have to wait for load to test bulk push
    }

    init () {
        $ = jQuery
        let self = this

        $('.expand_post_details').unbind().click(
            function () {
                let id = $(this).data('id')
                $('#post-' + id).toggle()
            }
        )

        if (document.getElementById('bulk_data_push')) {
            document.getElementById('bulk_data_push').onclick = function (e) {
                e.preventDefault()

                let admin_message = {}
                admin_message.success = true
                admin_message.message = '<i class="dashicons dashicons-networking"></i> SYNDICATING'
                Message.admin_message(admin_message)

                self.bulk_push()
            }
        }

        if (document.getElementById('refresh_syndicated_posts')) {
            document.getElementById('refresh_syndicated_posts').onclick = function (e) {
                document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.
                self.refresh_view()
            }
        }

    }

    refresh_view () {

        document.getElementById('syndicated_posts_data').innerHTML = ''

        this.data = {}


        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Refreshing syndicated posts.'
        Message.admin_message(admin_message)

        this.get_source_data()
            .then(() => this.get_receiver_data())
            .then(() => this.show_posts())
            .then(() => this.finish_refresh())
            .catch(message => Message.handle_error(message))

    }

    finish_refresh () {
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.add('hidden')
        this.init()
        SyndicatedPosts.single_post_actions_init()
        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Ready.'
        Message.admin_message(admin_message)
    }

    async show_posts () {

        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Building posts table. . .'
        Message.admin_message(admin_message)

        for (const [index, post] of this.data.source_data.posts.entries()) {
            this.post = post
            this.index = index
            this.data.post_to_get = post
            await this.get_syndicated_post_details()
                .then(retrieved_post => this.display_refreshed_post(retrieved_post))
        }
    }

    async get_syndicated_post_details () {
        const response = await fetch(
            DataSync.api.url + '/syndicated_post/' + this.post.ID, {
                method: 'POST',
                headers: {
                    'Content-Type': 'text/html; charset=utf-8',
                    'X-WP-Nonce': DataSync.api.nonce
                },
                body: JSON.stringify(this.data)
            }
        )
        return await response.text()
    }

    display_refreshed_post (retrieved_post) {
        let result_array = retrieved_post.split('null')
        result_array.slice(-1)[0]
        let html = result_array.join(' ')
        $ = jQuery
        $('#syndicated_posts_data').append(html)
    }

    async get_source_data () {
        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Getting data from source. . .'
        Message.admin_message(admin_message)
        const response = await fetch(DataSync.api.url + '/source_data/load')
        this.data.source_data = await response.json()
    }

    async get_receiver_data () {

        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Getting data from receivers. . .'
        Message.admin_message(admin_message)

        this.data.receiver_data = []

        for (const [index, site] of this.data.source_data.connected_sites.entries()) {
            this.site = site
            this.index = index
            const response = await fetch(this.site.url + '/wp-json/data-sync/v1/receiver/get_data')
            this.data.receiver_data[index] = await response.json()
        }

    }

    async get_all_posts () {
        const response = await fetch(DataSync.api.url + '/posts/all')
        return await response.json()
    }

    bulk_push () {

        document.getElementById('syndicated_posts_data').innerHTML = ''
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Prevalidating receiver site compatibility.'
        Message.admin_message(admin_message)

        this.prevalidate()
            .then(prevalidation => {
                if (!prevalidation.success) {
                    prevalidation.message = 'Prevalidation failed.'

                    Message.admin_message(prevalidation)
                    if (DataSync.options.debug) {
                        let logs = new Logs()
                        logs.refresh_log()
                    }
                } else {
                    prevalidation.message = 'Prevalidation successful. Gathering source posts. . .'
                    Message.admin_message(prevalidation)

                    this.consolidate()
                        .then(() => this.send_posts_to_receivers())
                        .then(() => Logs.process_receiver_logs(this.receiver_data))
                        .then(() => this.process_receiver_synced_posts())
                        .then(() => this.consolidate_media())
                        .then((consolidated_media_packages) => this.send_media_to_receivers(consolidated_media_packages))
                        .then(() => Logs.process_receiver_logs(this.media_sync_responses))
                        .then(() => this.process_receiver_synced_posts())
                        .then(() => {
                            this.refresh_view()
                            new EnabledPostTypes()
                            let admin_message = {}
                            admin_message.success = true
                            admin_message.message = '<span class="dashicons dashicons-yes-alt"></span> Syndication complete!'
                            Message.admin_message(admin_message)
                        })
                        .catch(message => Message.handle_error(message))
                }
            })
            .catch(message => Message.handle_error(message))

        // TODO: SEND MEDIA, GET LOGS, GET SYNCED POSTS

    }

    consolidate () {
        return this.get_all_posts()
            .then(posts => {
                this.posts = posts
                let admin_message = {}
                admin_message.success = true
                admin_message.message = 'Source posts consolidated. Gathering connected sites. . .'
                Message.admin_message(admin_message)

            })

            // GET ALL SITES
            .then(() => ConnectedSites.get_all())
            .then(connected_sites => {
                this.connected_sites = connected_sites
                let admin_message = {}
                admin_message.success = true
                admin_message.message = 'Connected sites consolidated. Preparing data packages. . .'
                Message.admin_message(admin_message)
            })

            // PREPARE AND CONSOLIDATE SOURCE PACKAGES
            .then(() => this.prepare_packages(true)) // prep requests for creating bulk packages to send to receivers
            .then(requests => {
                // console.log('Prepared post, options, and meta packages: ',requests)
                return requests
            })
            .then(requests => Promise.all(requests))// send all requests for package
            .then(responses => {return responses}) // all responses are resolved successfully
            .then(responses => Promise.all(responses.map(r => r.json())))// map array of responses into array of response.json() to read their content
            .then(prepped_source_packages => {
                this.prepped_source_packages = prepped_source_packages

                let admin_message = {}
                admin_message.success = true
                admin_message.message = 'All data from source ready to be sent, sending now. . .'
                Message.admin_message(admin_message)
            })
    }

    consolidate_media () {

        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Preparing media items. . .'
        Message.admin_message(admin_message)

        let requests = this.prepare_media_packages() // prep requests for creating bulk packages to send to receivers
        return Promise.all(requests) // send all requests for package
            .then(responses => {return responses}) // all responses are resolved successfully
            .then(responses => Promise.all(responses.map(r => r.json())))// map array of responses into array of response.json() to read their content
            .then(prepared_site_packages => {
                let consolidated_packages = []
                prepared_site_packages.forEach(site_packages => {
                    site_packages.forEach(media_package => {consolidated_packages.push(media_package)})
                })
                return consolidated_packages
            })
            .then(consolidated_packages => {
                let admin_message = {}
                admin_message.success = true
                admin_message.message = 'Media packages ready. Sending out ' . consolidated_packages.length + ' media sync requests. Please be patient.'
                Message.admin_message(admin_message)
                return consolidated_packages
            })
    }

    send_posts_to_receivers () {
        this.prepped_source_packages.forEach(prepped_source_package => this.create_remote_request(prepped_source_package)) // create send requests with packages

        return Promise.all(this.receiver_sync_requests)// send all packages to receivers
            .then(responses => { return responses}) // all responses are resolved successfully
            .then(responses => Promise.all(responses.map(r => r.json())))// map array of responses into array of response.json() to read their content
            .then(receiver_data => {
                this.receiver_data = receiver_data
                this.receiver_data.forEach(single_receiver_data => {
                    let admin_message = {}
                    admin_message.success = true
                    admin_message.message = single_receiver_data.data.message
                    Message.admin_message(admin_message)
                })
            })

    }

    prepare_packages (bulk) {

        let requests = []
        this.receiver_sync_requests = [] // will be compiled in create_send_requests().

        for (const site of this.connected_sites) {
            if (bulk) {
                requests.push(fetch(DataSync.api.url + '/source_data/prep/0/' + site.id))
            } else {
                // TODO: IMPORT POST SOMEHOW
                // requests.push(fetch(DataSync.api.url + '/source_data/prep/' + post.ID + '/' + site.id))
            }

        }

        return requests
    }

    prepare_media_packages () {

        let data = {}
        let requests = []
        this.receiver_sync_requests = [] // will be compiled in create_send_requests().

        for (const site of this.connected_sites) {

            this.prepped_source_packages.forEach(prepped_source_package => {
                let decoded_package = JSON.parse(prepped_source_package)
                // console.log('Prepared media source packages: ',decoded_package)
                if (parseInt(site.id) === parseInt(decoded_package.receiver_site_id)) {
                    data.site = site
                    data.posts = decoded_package.posts
                    requests.push(fetch(DataSync.api.url + '/media/prep', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    }))
                }
            })

        }

        return requests
    }

    async send_media_to_receivers (prepared_media_packages) {

        this.media_sync_responses = []

        for (const media_package of prepared_media_packages) {
            await this.send_media(media_package)
                .then(media_sync_response => {
                    this.media_sync_responses.push(media_sync_response)
                })
                .catch(message => Message.handle_error(message))
        }

        let admin_message = {}
        admin_message.success = true
        admin_message.message = 'Media synced.'
        Message.admin_message(admin_message)

    }

    async send_media (media_package) {
        let source_package = JSON.parse(media_package)
        // console.log('Source package before create_remote_request(): ',source_package)

        const response = await fetch(source_package.receiver_site_url + '/wp-json/data-sync/v1/sync', {
            method: 'POST',
            body: JSON.stringify(source_package)
        })
        return await response.json()
    }

    process_receiver_synced_posts () {
        let receiver_synced_posts = []
        // console.log('receiver_data for processing synced posts: ',this.receiver_data)
        this.receiver_data.forEach(single_receiver_data => receiver_synced_posts.push(single_receiver_data.data.synced_posts))
        return this.save_receiver_synced_posts(receiver_synced_posts)
            .then(synced_posts_response => {
                let admin_message = {}
                admin_message.success = true
                admin_message.message = synced_posts_response.data.message
                Message.admin_message(admin_message)
            })
    }

    async save_receiver_synced_posts (synced_posts) {
        const response = await fetch(DataSync.api.url + '/sync_post', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': DataSync.api.nonce
            },
            body: JSON.stringify(synced_posts)
        })
        return await response.json()
    }

    async prevalidate () {
        const response = await fetch(DataSync.api.url + '/prevalidate')
        return await response.json()
    }

    create_remote_request (prepped_source_package) {

        // DON'T ADD ANYTHING TO THIS FROM HERE ON OR IT WILL TRIP THE AUTH SIG CHECK.
        let source_package = JSON.parse(prepped_source_package)
        // console.log('Source package before create_remote_request(): ',source_package)

        this.receiver_sync_requests.push(
            fetch(source_package.receiver_site_url + '/wp-json/data-sync/v1/sync', {
                method: 'POST',
                body: JSON.stringify(source_package)
            })
        )
    }

    static single_post_actions_init () {
        jQuery(
            function ($) {

                $('.push_post_now').unbind().click(
                    function (e) {

                        e.preventDefault()

                        let source_post_id = $(this).data('source-post-id')

                        SyndicatedPosts.push_single_post_to_all_receivers(source_post_id, true)

                    }
                )

                $('.overwrite_single_receiver').unbind().click(
                    function (e) {

                        e.preventDefault()

                        let receiver_site_id = $(this).data('receiver-site-id')
                        let source_post_id = $(this).data('source-post-id')

                        SyndicatedPosts.push_single_post_to_single_receiver(receiver_site_id, source_post_id)

                    }
                )

            }
        )

    }

    static push_single_post_to_all_receivers (source_post_id, overwrite) {
        document.getElementById('syndicated_posts_wrap').classList.add('hidden') // REMOVE TABLE FOR LOADING.
        document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

        let data = {}
        data.overwrite = overwrite

        AJAX.post(DataSync.api.url + '/source_data/push/' + source_post_id, data).then(
            function (result) {
                SyndicatedPosts.refresh_view()
                Message.admin_message(result, 'Post')
                new EnabledPostTypes()
                if (DataSync.options.debug) {
                    let logs = new Logs()
                    logs.refresh_log()
                }
            }
        )
    }

    static push_single_post_to_single_receiver (receiver_site_id, source_post_id) {
        document.getElementById('syndicated_posts_wrap').classList.add('hidden') // REMOVE TABLE FOR LOADING.
        document.querySelector('#syndicated_posts .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

        let data = {}
        data.overwrite = overwrite

        AJAX.post(DataSync.api.url + '/source_data/overwrite/' + source_post_id + '/' + receiver_site_id, data).then(
            function (result) {
                SyndicatedPosts.refresh_view()
                Message.admin_message(result, 'Post')
                new EnabledPostTypes()
                if (DataSync.options.debug) {
                    let logs = new Logs()
                    logs.refresh_log()
                }
            }
        )
    }

}

export default SyndicatedPosts