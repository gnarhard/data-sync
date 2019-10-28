import AJAX from '../../AJAX.es6.js'
import Success from './Success.es6'
import EnabledPostTypes from './EnabledPostTypes.es6'
import Logs from './Logs.es6'

class SyndicatedPosts {

    constructor () {
        this.refresh_view()
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
                SyndicatedPosts.bulk_push(e)
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

        this.refresh_data = {}

        this.get_source_data()
            .then(source_data => this.get_receiver_data())
            .then(receiver_data => this.get_all_posts())
            .then(() => this.finish_refresh())

    }

    finish_refresh () {
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.add('hidden')
        this.init()
        SyndicatedPosts.single_post_actions_init()
    }

    async get_all_posts () {

        for (const [index, post] of this.refresh_data.source_data.posts.entries()) {
            this.post = post
            this.index = index
            this.refresh_data.post_to_get = post;
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
                body: JSON.stringify(this.refresh_data)
            }
        )
        return await response.text()
    }

    display_refreshed_post (retrieved_post) {
        let result_array = retrieved_post.split('null')
        result_array.slice(-1)[0]
        let html = result_array.join(' ')
        $ = jQuery
        $('#syndicated_posts_data').prepend(html)
    }

    async get_source_data () {
        const response = await fetch(DataSync.api.url + '/source_data/load')
        this.refresh_data.source_data = await response.json()
    }

    async get_receiver_data () {

        this.refresh_data.receiver_data = [];

        for (const [index, site] of this.refresh_data.source_data.connected_sites.entries()) {
            this.site = site
            this.index = index
            const response = await fetch(this.site.url + '/wp-json/data-sync/v1/receiver/get_data')
            this.refresh_data.receiver_data[index] = await response.json();
        }

        console.log(this);

    }

    static bulk_push (e) {
        e.preventDefault()

        document.getElementById('syndicated_posts_data').innerHTML = ''
        document.querySelector('#syndicated_posts_wrap .loading_spinner').classList.remove('hidden') // SHOW LOADING SPINNER.


        // BUILD REQUESTS FOR PROMISE.ALL().
        // let requests = this.refresh_data.source_data.posts.map(
        //     post => fetch(
        //         DataSync.api.url + '/syndicated_post/' + post.ID, {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'text/html; charset=utf-8',
        //                 'X-WP-Nonce': DataSync.api.nonce
        //             },
        //             body: JSON.stringify(this.refresh_data)
        //         }
        //     )
        // )

        // Promise.all(requests)
        //     .then(
        //         responses => {
        //             // all responses are resolved successfully
        //             return responses
        //         }
        //     )
        //     // map array of responses into array of response.text() to read their content
        //     .then(responses => Promise.all(responses.map(r => r.text())))
        //     // all TEXT answers are parsed: "retrived_posts" is the array of them
        //     .then(users => users.forEach(retrieved_post => this.display_refreshed_post(retrieved_post)))
        //     // FINISH.
        //     .then(() => this.finish_refresh())


        AJAX.get(DataSync.api.url + '/source_data/push').then(
            function (source_data) {

                console.log(source_data)
                source_data.overwrite = false
                let postPushes = []
                let fetchInit = {
                    method: 'POST',
                    body: JSON.stringify(source_data)
                }

                source_data.connected_sites.forEach(
                    (site, index) => {
                        source_data.options.push_enabled_post_types_array.forEach(
                            (post_type) => {
                                source_data.posts[post_type].forEach(
                                    (post, idx) => {
                                        let url = site.url + '/wp-json/data-sync/v1' + '/source_data/push/' + post.ID + '/' + site.id

                                        postPushes[idx] = fetch(url, fetchInit)

                                    }
                                )
                            }
                        )

                    }
                )

                Promise.all(postPushes)
                    .then(
                        response => {
                            console.log(response)
                        }
                    )
                    .catch(
                        error => {
                            console.log(error)
                        }
                    )

            }
        )

        // AFTER EVERYTHING IS LOADED

        //   SyndicatedPosts.refresh_view()
        //   Success.show_success_message(result, 'Posts')
        //   new EnabledPostTypes()
        //   if ( DataSync.options.debug ) {
        //     let logs = new Logs()
        //     logs.refresh_log();
        //   }
        // })
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