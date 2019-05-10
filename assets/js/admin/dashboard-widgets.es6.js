jQuery(function($) {

  $.ajax({
    method: 'GET',
    url: WPDS.api.url,
    beforeSend: function ( xhr ) {
      xhr.setRequestHeader( 'X-WP-Nonce', WPDS.api.nonce );
    }
  }).then( function ( r ) {
    if( r.hasOwnProperty( 'industry' ) ){
      $( '#industry' ).val( r.industry );
    }

    if( r.hasOwnProperty( 'amount' ) ){
      $( '#amount' ).val( r.amount );
    }
  });

  // $( '#apex-form' ).on( 'submit', function (e) {
  //   e.preventDefault();
  //   var data = {
  //     amount: $( '#amount' ).val(),
  //     industry: $( '#industry' ).val()
  //   };
  //
  //   $.ajax({
  //     method: 'POST',
  //     url: WPDS.api.url,
  //     beforeSend: function ( xhr ) {
  //       xhr.setRequestHeader('X-WP-Nonce', WPDS.api.nonce);
  //     },
  //     data:data
  //   }).then( function (r) {
  //     $( '#feedback' ).html( '<p>' + WPDS.strings.saved + '</p>' );
  //   }).error( function (r) {
  //     var message = WPDS.strings.error;
  //     if( r.hasOwnProperty( 'message' ) ){
  //       message = r.message;
  //     }
  //     $( '#feedback' ).html( '<p>' + message + '</p>' );
  //
  //   })
  // })

  $(document).ready(function() {
    $('#save_enabled_post_types').unbind().click(function(e) {
      e.preventDefault();

      data = $('#enabled_post_types').val();

      $.ajax({
        method: 'POST',
        url: WPDS.api.url,
        beforeSend: function ( xhr ) {
          xhr.setRequestHeader('X-WP-Nonce', WPDS.api.nonce);
        },
        data:data
      }).then( function (r) {
        $( '#feedback' ).html( '<p>' + WPDS.strings.saved + '</p>' );
      }).error( function (r) {
        var message = WPDS.strings.error;
        if( r.hasOwnProperty( 'message' ) ){
          message = r.message;
        }
        $( '#feedback' ).html( '<p>' + message + '</p>' );

      })
    });
  });
});