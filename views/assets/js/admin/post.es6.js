window.onload = function(e){

  if ( document.getElementsByClassName('edit-post-header__settings').length ) {
    if ( document.getElementsByClassName('edit-post-header__settings')[0].getElementsByTagName('button').length ) {

      let button_list = document.getElementsByClassName('edit-post-header__settings')[0].getElementsByTagName('button');

      for (let i = 0; i < button_list.length; i++) {

        if ( ( 'Publish…' === button_list[i].innerText ) || ( 'Update' === button_list[i].innerText ) ) {

          let publishButton = button_list[i];

          publishButton.onclick = function ( e ) {
            e.preventDefault();

            let radios = document.getElementsByName('canonical_site');

            for (let i = 0, length = radios.length; i < length; i++)
            {
              if (radios[i].checked)
              {
                // do whatever you want with the checked radio
                let canonical_setting_value = adios[i].value;

                // only one radio can be logically checked, don't check the rest
                break;
              }
            }

            if ( typeof canonical_setting_value === 'undefined' ) {
              alert( 'Please choose a canonical site before proceeding.');
              e.stopImmediatePropagation();
              return;
            }
          }

        }
      }
    }
  }

}