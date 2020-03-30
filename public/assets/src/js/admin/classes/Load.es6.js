import ConnectedSites from './ConnectedSites.es6.js';
import SyndicatedPosts from './SyndicatedPosts.es6.js';
import SyndicatedTemplates from './SyndicatedTemplates.es6.js';
import EnabledPostTypes from './EnabledPostTypes.es6.js';
import Logs from './Logs.es6.js';
import Settings from './Settings.es6.js';
import AppStore from '../../AppStore.es6.js';


class Load {

	constructor() {
		document.addEventListener( 'DOMContentLoaded', function() {
			$ = jQuery;
			if ( document.getElementById( 'data_sync_tabs' ) ) {
				$( '#data_sync_tabs' ).tabs();

				document.querySelector( '#data_sync_tabs' ).classList.remove( 'hidden' );

				if ( DataSync.options.source_site ) {
					new AppStore();
					let syndicated_posts = new SyndicatedPosts();
					syndicated_posts.refresh_view();
					new SyndicatedTemplates();
					new ConnectedSites();
					new EnabledPostTypes();
					new Logs();
				}

				new Settings();
			}

		} );
	}

}


new Load();