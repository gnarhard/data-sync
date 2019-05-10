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

        data['name'] = $('#name').val();
        data['url'] = $('#url').val();
        data['token'] = $('#token').val();
        data['date_connected'] = new Date().toLocaleString();

        $.ajax({
          method: 'POST',
          url: DataSync.api.url,
          beforeSend: function ( xhr ) {
            xhr.setRequestHeader('X-WP-Nonce', DataSync.api.nonce);
          },
          data:data
        }).then( function (r) {
          $( '#feedback' ).html( '<p>' + DataSync.strings.saved + '</p>' );
        }).error( function (r) {
          var message = DataSync.strings.error;
          if( r.hasOwnProperty( 'message' ) ){
            message = r.message;
          }
          $( '#feedback' ).html( '<p>' + message + '</p>' );

        })

      });


    });
  });
});