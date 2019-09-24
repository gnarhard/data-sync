class Helpers {

  static getSelectValues( select ) {
    let result = [];
    let options = select && select.options;
    let opt;

    for ( let i = 0, iLen = options.length; i < iLen; i++ ) {
      opt = options[i];

      if ( opt.selected ) {
        result.push( opt.value.replace(/\s/g,'') || opt.text.replace(/\s/g,'') );
      }
    }
    return result;
  }

}

export default Helpers;