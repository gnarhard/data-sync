import AJAX from '../AJAX.es6.js'
import ConnectedSites from './ConnectedSites.es6.js'

jQuery(function ($) {

	$(document).ready( function() {
		init();

		if (document.getElementById('error_log')) {
			document.getElementById( 'refresh_error_log' ).onclick = function ( e ) {
				refresh_log();
			}
		}
	});
} );


function refresh_log () {
	AJAX.get(DataSync.api.url + '/log/get').then(function (result) {
		if (JSON.parse(result.html) !== document.getElementById('error_log').innerHTML) {
			document.getElementById('error_log').innerHTML = JSON.parse(result.html)
		}
	})
}

function init() {
	$=jQuery;
	$('#data_sync_tabs').tabs();

	if (document.getElementById('syndicated_posts_wrap')) {
		AJAX.get_html(DataSync.api.url + '/settings_tab/syndicated_posts' ).then(function( result) {
			let result_array = result.split('null');
			result_array.slice(-1)[0];
			let html = result_array.join();
			document.getElementById('syndicated_posts_wrap').innerHTML = html;
			document.querySelector('#syndicated_posts .loading_spinner').classList.add('hidden');

			$('#bulk_data_push').unbind().click(function (e) {
				e.preventDefault()
				AJAX.get(DataSync.api.url + '/source_data/push')
			}, false)
		});
	}
	if (document.getElementById('connected_sites_wrap')) {
		AJAX.get_html(DataSync.api.url + '/settings_tab/connected_sites' ).then(function( result) {
			let result_array = result.split('null');
			result_array.slice(-1)[0];
			let html = result_array.join();
			document.getElementById('connected_sites_wrap').innerHTML = html;
			document.querySelector('#connected_sites .loading_spinner').classList.add('hidden');
			init_connected_sites();
		});
	}
	if (document.getElementById('enabled_post_types_wrap')) {
		AJAX.get_html(DataSync.api.url + '/settings_tab/enabled_post_types' ).then(function( result) {
			let result_array = result.split('null');
			result_array.slice(-1)[0];
			let html = result_array.join();
			document.getElementById('enabled_post_types_wrap').innerHTML = html;
			document.querySelector('#enabled_post_types .loading_spinner').classList.add('hidden');
		});
	}

}

function init_connected_sites() {
	// ADD SITE
	$('#add_site').unbind().click(function (e) {
		e.preventDefault()

		$('.lightbox_wrap').addClass('display')

		$('#close').unbind().click(function () {
			$('.lightbox_wrap').removeClass('display')
		})

		$('#submit_site').unbind().click(function (e) {
			e.preventDefault()
			ConnectedSites.save()
		})
	})

	$('.remove_site').unbind().click(function (e) {
		let site_id = parseInt($(this).parent().attr('id').split('site-')[1])
		ConnectedSites.delete(site_id)
	})
}
