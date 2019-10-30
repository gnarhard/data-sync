import AJAX from '../../AJAX.es6.js'
import Success from './Success.es6'
import EnabledPostTypes from './EnabledPostTypes.es6'
import Logs from './Logs.es6'
import ConnectedSites from './ConnectedSites.es6'

class SyndicatedPosts {

    constructor () {
        // this.refresh_view()
        this.init() // so you don't have to wait for load to test bulk push
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
                self.bulk_push(e)
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

        this.get_source_data()
            .then(() => this.get_receiver_data())
            .then(() => this.show_posts())
            .then(() => this.finish_refresh())

    }

    finish_refresh () {
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.add('hidden')
        this.init()
        SyndicatedPosts.single_post_actions_init()
    }

    async show_posts () {

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
        const response = await fetch(DataSync.api.url + '/source_data/load')
        this.data.source_data = await response.json()
    }

    async get_receiver_data () {

        this.data.receiver_data = []

        for (const [index, site] of this.data.source_data.connected_sites.entries()) {
            this.site = site
            this.index = index
            const response = await fetch(this.site.url + '/wp-json/data-sync/v1/receiver/get_data')
            this.data.receiver_data[index] = await response.json()
        }

        console.log(this)

    }

    async get_all_posts () {
        const response = await fetch(DataSync.api.url + '/posts/all')
        return await response.json()
    }

    handle_error (message) {
        let result = {}
        result.success = false

        if (message === 'SyntaxError: Unexpected token < in JSON at position 0') {
            result.data = 'Server errors encountered.'
        } else {
            result.data = message
        }

        console.log(result)
        return result
    }

    bulk_push (e) {
        e.preventDefault()

        document.getElementById('syndicated_posts_data').innerHTML = ''
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.

        this.prevalidate()
            .then(prevalidation => {
                if (!prevalidation.success) {
                    Success.show_success_message(prevalidation, 'Prevalidation')
                    if (DataSync.options.debug) {
                        let logs = new Logs()
                        logs.refresh_log()
                    }
                } else {
                    // PREVALIDATED.
                    this.get_all_posts()
                        .then(posts => {this.posts = posts})

                        .then(() => ConnectedSites.get_all())
                        .then(connected_sites => {this.connected_sites = connected_sites })

                        .then(() => this.prepare_packages(true)) // prep requests for creating bulk packages to send to receivers
                        .then(requests => Promise.all(requests))// send all requests for package
                        .then(responses => {return responses}) // all responses are resolved successfully
                        .then(responses => Promise.all(responses.map(r => r.json())))// map array of responses into array of response.json() to read their content

                        .then(prepped_source_packages => prepped_source_packages.forEach(prepped_source_package => this.create_remote_request(prepped_source_package, false))) // create send requests with packages
                        .then(requests => Promise.all(requests))// send all packages to receivers
                        .then(responses => {return responses}) // all responses are resolved successfully
                        .then(responses => Promise.all(responses.map(r => r.json())))// map array of responses into array of response.json() to read their content

                        .then(receiver_responses => {
                            console.log(receiver_responses)
                            // this.refresh_view()
                            // Success.show_success_message(result, 'Posts')
                            // new EnabledPostTypes()
                            // if (DataSync.options.debug) {
                            //     let logs = new Logs()
                            //     logs.refresh_log()
                            // }
                        })
                        .catch(message => this.handle_error(message))
                }
            })

        // TODO: SEND MEDIA, GET LOGS, GET SYNCED POSTS

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

    async prevalidate () {
        const response = await fetch(DataSync.api.url + '/prevalidate')
        return await response.json()
    }

    create_remote_request (prepped_source_package, options_and_meta) {

        let source_package = JSON.parse(prepped_source_package)
        // source_package.options_and_meta = options_and_meta;

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
                Success.show_success_message(result, 'Post')
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
                Success.show_success_message(result, 'Post')
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