import AJAX from '../../AJAX.es6.js'
import ConnectedSites from './ConnectedSites.es6.js'
import SyndicatedPosts from './SyndicatedPosts.es6.js';

class Load {

	constructor() {
		this.init();
	}

	init() {
		$=jQuery;

		document.addEventListener( "DOMContentLoaded", function () {
			$('#data_sync_tabs').tabs();

			new SyndicatedPosts();
			new SyndicatedTemplates();
			new ConnectedSites();
			new EnabledPostTypes();
			new ErrorLog();
		})

	}

}