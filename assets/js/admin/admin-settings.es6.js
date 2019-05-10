import AJAX from './AJAX.es6.js';

jQuery(function($) {

  $(document).ready(function() {
    $('#add_site').unbind().click(function(e) {
      e.preventDefault();

      $('.settings_page_data-sync-settings .lightbox_wrap').addClass('display');

      $('#close').unbind().click(function() {
        $('.settings_page_data-sync-settings .lightbox_wrap').removeClass('display');
      });

      $('#submit_site').unbind().click(function(e) {
        e.preventDefault();

        let data = {};
        data.connected_sites = [];
        data.connected_sites[0] = {};
        data.connected_sites[0].name = $('#name').val();
        data.connected_sites[0].url = $('#url').val();
        data.connected_sites[0].token = $('#token').val();
        data.connected_sites[0].date_connected = new Date().toLocaleString();
        console.log(data);

        AJAX.post(data);

      });


    });
  });
});