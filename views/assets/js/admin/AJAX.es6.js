class AJAX {

    static async post( data ) {

        const response = await fetch( DataSync.api.url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-WP-Nonce': DataSync.api.nonce
            },
            body: JSON.stringify( data )
        } );
        let result = await response.json();
        console.log(result);

        jQuery( '#feedback' ).html( '<p>' + DataSync.strings.saved + '</p>' );

    }

    static async get( setting ) {
        let response = await fetch( DataSync.api.url + '/' + setting, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-WP-Nonce': DataSync.api.nonce
            },
        } );
        return await response.json();
    }


}

export default AJAX;