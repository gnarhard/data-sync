class Helpers
{

	static getSelectValues( select )
	{
		let result = [];
		let options = select && select.options;
		let opt;

		for ( let i = 0, iLen = options.length; i < iLen; i++ ) {
			opt = options[ i ];

			if ( opt.selected ) {
				result.push( opt.value.replace( /\s/g, '' ) || opt.text.replace( /\s/g, '' ) );
			}
		}
		return result;
	}

	static trailingslashit( url )
	{
		var lastChar = url.substr( -1 ); // Selects the last character
		if ( lastChar != '/' ) {         // If the last character is not a slash
			url = url + '/';            // Append a slash to it.
		}

		if ( url.substr( -1 ) != '/' ) url += '/';

		url = url.replace( /\/?$/, '/' );

		return url;
	}

}

export default Helpers;