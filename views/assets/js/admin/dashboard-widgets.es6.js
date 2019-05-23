import AJAX from './AJAX.es6.js';

document.addEventListener( "DOMContentLoaded", function () {
    if ( document.getElementById( 'save_push_enabled_post_types' ) ) {
        document.getElementById( 'save_push_enabled_post_types' ).onclick = function ( e ) {
            e.preventDefault();

            let data = {};
            let input_name = document.getElementById( 'push_enabled_post_types' ).getAttribute( 'name' ).replace( /[^a-z0-9_]/gi, '' );
            data = getSelectValues( document.getElementById( 'push_enabled_post_types' ) );
            // console.log(data);
            AJAX.post( DataSync.api.url + '/settings/push_enabled_post_types', data );
        }
    }

} );

function getSelectValues( select ) {
    var result = [];
    var options = select && select.options;
    var opt;

    for ( var i = 0, iLen = options.length; i < iLen; i++ ) {
        opt = options[i];

        if ( opt.selected ) {
            result.push( opt.value.replace(/\s/g,'') || opt.text.replace(/\s/g,'') );
        }
    }
    return result;
}