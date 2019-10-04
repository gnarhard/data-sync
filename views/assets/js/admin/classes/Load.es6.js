import AJAX from '../../AJAX.es6.js'
import ConnectedSites from './ConnectedSites.es6.js'
import SyndicatedPosts from './SyndicatedPosts.es6.js';
import SyndicatedTemplates from './SyndicatedTemplates.es6';
import EnabledPostTypes from './EnabledPostTypes.es6';
import Logs from './Logs.es6';
import Settings from './Settings.es6';

class Load {

	constructor() {
		this.init();
	}

	init() {

		document.addEventListener( "DOMContentLoaded", function () {
			$=jQuery;
			if ( document.getElementById('data_sync_tabs') ) {
				$('#data_sync_tabs').tabs();

				document.querySelector('#data_sync_tabs').classList.remove('hidden');

				new SyndicatedPosts();
				new SyndicatedTemplates();
				new ConnectedSites();
				new EnabledPostTypes();
				new Logs();
				new Settings();
			}

		})

	}

}

new Load();