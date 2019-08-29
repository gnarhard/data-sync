window.onload = function(e){
  let publishButton = document.getElementsByClassName('editor-post-publish-button' )[0];

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
      return;
    }
  }
}