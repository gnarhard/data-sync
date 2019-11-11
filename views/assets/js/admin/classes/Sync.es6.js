import Message from './Message.es6.js'
import EnabledPostTypes from './EnabledPostTypes.es6'
import Logs from './Logs.es6'
import ConnectedSites from './ConnectedSites.es6'
import Settings from './Settings.es6'
import SyndicatedPosts from './SyndicatedPosts.es6'
import Constants from '../../Constants.es6'
import Processes from './Processes.es6'

class Sync {

    constructor () {

    }

    async show_posts (process_id) {

        let process = Processes.get(process_id)

        let admin_message = {}
        admin_message.process_id = process_id

        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = 'Building posts table. . .'
        Message.admin_message(admin_message)

        let syndicated_posts = new SyndicatedPosts()

        // BULK REFRESH
        for (const [index, post] of process.data.source_data.posts.entries()) {
            process.data.post_to_get = post
            Processes.set(process)

            await this.get_syndicated_post_details(post.ID, process.data)
                .then(retrieved_post => syndicated_posts.display_refreshed_post(retrieved_post, process_id))
        }

    }

    async get_syndicated_post_details (post_id, data) {
        const response = await fetch(
            DataSync.api.url + '/syndicated_post/' + post_id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'text/html; charset=utf-8',
                    'X-WP-Nonce': DataSync.api.nonce
                },
                body: JSON.stringify(data)
            }
        )
        return await response.text()
    }

    async get_source_data (process_id) {

        let data = {}

        let process = Processes.get(process_id)

        let admin_message = {}
        admin_message.process_id = process_id
        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = 'Getting data from source. . .'
        Message.admin_message(admin_message)

        let response = {}

        if (false === process.source_post_id) {
            response = await fetch(DataSync.api.url + '/source_data/load/0') // LOAD BULK
        } else {
            response = await fetch(DataSync.api.url + '/source_data/load/' + process.source_post_id)
        }

        data.source_data = await response.json()
    }

    async get_receiver_data (process_id) {

        let admin_message = {}
        admin_message.process_id = process_id
        let process = Processes.get(process_id)

        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = 'Getting data from receivers. . .'
        Message.admin_message(admin_message)

        let data = {}
        data.receiver_data = []

        for (const [index, site] of process.data.source_data.connected_sites.entries()) {
            const response = await fetch(site.url + '/wp-json/data-sync/v1/receiver/get_data')
            data.receiver_data[index] = await response.json()
        }

    }

    async get_posts (process_id) {

        let process = Processes.get(process_id)

        if (false === process.source_post_id) {
            const response = await fetch(DataSync.api.url + '/posts/all')
            return await response.json()
        } else {
            const response = await fetch(DataSync.api.url + '/posts/' + process.source_post_id)
            return await response.json()
        }
    }

    start (process_id) {

        let process = Processes.get(process_id)

        process.process_running = true
        Processes.set(process);

        let admin_message = {}
        admin_message.process_id = process.id
        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = '<i class="dashicons dashicons-networking"></i> SYNDICATING'
        Message.admin_message(admin_message)

        admin_message.process_id = process.id
        admin_message.topic = process.topic
        admin_message.success = true
        admin_message.message = 'Pre-validating receiver site compatibility.'
        Message.admin_message(admin_message)

        this.prevalidate()
            .then(prevalidation => {
                if (!prevalidation.success) {
                    prevalidation.topic = process.topic
                    prevalidation.message = 'pre-validation failed.'
                    prevalidation.process_id = process.id

                    Message.admin_message(prevalidation)
                    new Settings()
                    if (DataSync.options.debug) {
                        let logs = new Logs()
                        logs.refresh_log()
                    }
                } else {
                    prevalidation.message = 'pre-validation successful. Gathering source posts. . .'
                    prevalidation.process_id = process.id
                    prevalidation.topic = process.topic
                    Message.admin_message(prevalidation)

                    this.consolidate()
                        .then(() => this.send_posts_to_receivers())
                        .then(() => Logs.process_receiver_logs(this.receiver_data, process.id, process.topic))
                        .then(() => this.process_receiver_synced_posts())
                        .then(() => this.consolidate_media())
                        .then((consolidated_media_packages) => this.send_media_to_receivers(consolidated_media_packages))
                        .then(() => Logs.process_receiver_logs(this.media_sync_responses, process.id, process.topic))
                        .then(() => this.process_receiver_synced_posts())
                        .then(() => {
                            this.process_running = false
                            let syndicated_posts = new SyndicatedPosts()
                            syndicated_posts.refresh_view(process.id)
                            new EnabledPostTypes()
                            let admin_message = {}
                            admin_message.process_id = process.id

                            admin_message.topic = process.topic
                            admin_message.success = true
                            admin_message.message = '<span class="dashicons dashicons-yes-alt"></span> Syndication complete!'
                            Message.admin_message(admin_message)
                        })
                        .catch(message => Message.handle_error(message, process.topic))
                }
            })
            .catch(message => Message.handle_error(message, process.topic))

    }

    consolidate () {
        return this.get_posts()
            .then(posts => {
                this.posts = posts
                let admin_message = {}
                admin_message.process_id = this.process_id

                admin_message.topic = this.topic
                admin_message.success = true
                if (false === this.source_post_id) {
                    admin_message.message = 'Source posts consolidated. Gathering connected sites. . .'
                } else {
                    admin_message.message = 'Source post consolidated. Gathering connected sites. . .'
                }
                Message.admin_message(admin_message)

            })

            // GET ALL SITES
            .then(() => ConnectedSites.get_all())
            .then(connected_sites => {
                this.connected_sites = connected_sites
                let admin_message = {}
                admin_message.process_id = this.process_id

                admin_message.topic = this.topic
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
                admin_message.process_id = this.process_id

                admin_message.topic = this.topic
                admin_message.success = true
                admin_message.message = 'All data from source ready to be sent, sending now. . .'
                Message.admin_message(admin_message)
            })
    }

    consolidate_media () {

        let admin_message = {}
        admin_message.process_id = this.process_id

        admin_message.topic = this.topic
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
                admin_message.process_id = this.process_id

                admin_message.topic = this.topic
                admin_message.success = true
                admin_message.message = 'Media packages ready. Sending out ' + consolidated_packages.length + ' media sync requests. Please be patient.'
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
                    admin_message.process_id = this.process_id

                    admin_message.topic = this.topic
                    admin_message.success = true
                    admin_message.message = single_receiver_data.data.message
                    Message.admin_message(admin_message)
                })
            })

    }

    prepare_packages () {

        let requests = []
        this.receiver_sync_requests = [] // will be compiled in create_send_requests().

        if (false === this.receiver_site_id) {
            for (const site of this.connected_sites) {
                if (false === this.source_post_id) {
                    requests.push(fetch(DataSync.api.url + '/source_data/prep/0/' + site.id))
                } else {
                    requests.push(fetch(DataSync.api.url + '/source_data/prep/' + this.source_post_id + '/' + site.id))
                }
            }
        } else {
            requests.push(fetch(DataSync.api.url + '/source_data/prep/' + this.source_post_id + '/' + this.receiver_site_id))
        }

        return requests
    }

    prepare_media_packages () {

        let data = {}
        let requests = []
        this.receiver_sync_requests = [] // will be compiled in create_send_requests().

        for (const site of this.connected_sites) {

            if ((false === this.receiver_site_id) || (parseInt(site.id) === this.receiver_site_id)) {

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
        admin_message.process_id = this.process_id

        admin_message.topic = this.topic
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
                admin_message.process_id = this.process_id

                admin_message.topic = this.topic
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

}

export default Sync