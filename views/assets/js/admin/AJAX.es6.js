class AJAX {

    static async post( url, data ) {

        const response = await fetch( url, {
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

    static async get( url ) {
        let response = await fetch( url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-WP-Nonce': DataSync.api.nonce
            },
        } );
        return await response.json();
    }

    static async delete( url ) {
        let response = await fetch( url, {
            method: 'DELETE',
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